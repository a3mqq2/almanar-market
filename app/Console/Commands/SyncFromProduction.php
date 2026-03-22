<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SyncFromProduction extends Command
{
    protected $signature = 'sync:from-production
        {--table= : Sync specific table only}
        {--dry-run : Show differences without inserting}
        {--device= : Device ID to use}';

    protected $description = 'Compare all tables with production and insert missing records locally';

    protected string $serverUrl;
    protected string $apiToken;
    protected string $deviceId;

    protected array $tables = [
        'users',
        'units',
        'payment_methods',
        'expense_categories',
        'suppliers',
        'customers',
        'cashboxes',
        'products',
        'product_units',
        'product_barcodes',
        'inventory_batches',
        'purchases',
        'purchase_items',
        'shifts',
        'shift_cashboxes',
        'sales',
        'sale_items',
        'sale_payments',
        'sales_returns',
        'sale_return_items',
        'expenses',
        'stock_movements',
        'cashbox_transactions',
        'customer_transactions',
        'supplier_transactions',
        'inventory_counts',
        'inventory_count_items',
        'user_activity_logs',
        'user_cashboxes',
    ];

    public function handle(): int
    {
        $this->serverUrl = rtrim(config('desktop.server_url'), '/');

        $device = $this->resolveDevice();
        if (!$device) {
            return 1;
        }

        $this->deviceId = $device->device_id;
        $this->apiToken = $device->api_token;
        $this->info("Server: {$this->serverUrl}");
        $this->info("Device: {$device->device_name} ({$this->deviceId})");
        $this->newLine();

        $targetTable = $this->option('table');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
            $this->newLine();
        }

        $tables = $targetTable ? [$targetTable] : $this->tables;
        $summary = [];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("Table '{$table}' does not exist locally, skipping.");
                continue;
            }

            $result = $this->syncTable($table, $dryRun);
            if ($result !== null) {
                $summary[] = $result;
            }
        }

        $this->newLine();
        $this->warn('=== Summary ===');
        $this->table(
            ['Table', 'Production', 'Local', 'Missing', 'Inserted'],
            $summary
        );

        return 0;
    }

    protected function syncTable(string $table, bool $dryRun): ?array
    {
        $this->info("--- {$table} ---");

        $remoteIds = $this->fetchRemoteIds($table);
        if ($remoteIds === null) {
            $this->error("  Failed to fetch IDs for {$table}");
            return null;
        }

        $localIds = DB::table($table)->pluck('id')->toArray();

        $missingIds = array_values(array_diff($remoteIds, $localIds));
        $remoteCount = count($remoteIds);
        $localCount = count($localIds);
        $missingCount = count($missingIds);

        $this->info("  Production: {$remoteCount} | Local: {$localCount} | Missing: {$missingCount}");

        $inserted = 0;

        if ($missingCount > 0 && !$dryRun) {
            $inserted = $this->insertMissing($table, $missingIds);
            $this->info("  Inserted: {$inserted}");
        } elseif ($missingCount > 0 && $dryRun) {
            $this->comment("  Would insert {$missingCount} records");
            $preview = array_slice($missingIds, 0, 10);
            $this->comment("  IDs: " . implode(', ', $preview) . ($missingCount > 10 ? '...' : ''));
        }

        return [$table, $remoteCount, $localCount, $missingCount, $inserted];
    }

    protected function fetchRemoteIds(string $table): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Device-ID' => $this->deviceId,
                'X-API-Token' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(120)->get("{$this->serverUrl}/api/v1/sync/table-ids/{$table}");

            if (!$response->successful()) {
                return null;
            }

            return $response->json('ids', []);
        } catch (\Exception $e) {
            $this->error("  Connection error: {$e->getMessage()}");
            return null;
        }
    }

    protected function fetchRemoteRecords(string $table, array $excludeIds, int $page = 1): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Device-ID' => $this->deviceId,
                'X-API-Token' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(120)->get("{$this->serverUrl}/api/v1/sync/export-table/{$table}", [
                'exclude_ids' => $excludeIds,
                'page' => $page,
                'per_page' => 500,
            ]);

            if (!$response->successful()) {
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->error("  Fetch error: {$e->getMessage()}");
            return null;
        }
    }

    protected function insertMissing(string $table, array $missingIds): int
    {
        $localIds = DB::table($table)->pluck('id')->toArray();
        $inserted = 0;
        $page = 1;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            while (true) {
                $data = $this->fetchRemoteRecords($table, $localIds, $page);
                if ($data === null) {
                    break;
                }

                $records = $data['records'] ?? [];
                if (empty($records)) {
                    break;
                }

                $filtered = array_filter($records, fn($r) => in_array($r['id'] ?? null, $missingIds));

                foreach (array_chunk($filtered, 100) as $chunk) {
                    $rows = array_map(fn($r) => (array) $r, $chunk);
                    try {
                        DB::table($table)->insert($rows);
                        $inserted += count($rows);
                    } catch (\Exception $e) {
                        foreach ($rows as $row) {
                            try {
                                DB::table($table)->insert($row);
                                $inserted++;
                            } catch (\Exception $e2) {
                                $this->warn("  Failed ID {$row['id']}: {$e2->getMessage()}");
                            }
                        }
                    }
                }

                if (count($records) < ($data['per_page'] ?? 500)) {
                    break;
                }

                $page++;
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return $inserted;
    }

    protected function resolveDevice(): ?object
    {
        $deviceId = $this->option('device');

        if ($deviceId) {
            $device = DB::table('device_registrations')
                ->where('device_id', $deviceId)
                ->where('status', 'active')
                ->first();
        } else {
            $device = DB::table('device_registrations')
                ->where('status', 'active')
                ->whereNotNull('api_token')
                ->where('api_token', '!=', '')
                ->orderBy('last_seen_at', 'desc')
                ->first();
        }

        if (!$device) {
            $this->error('No active device found with API token.');
            return null;
        }

        if (empty($device->api_token)) {
            $this->error("Device {$device->device_name} has no API token.");
            return null;
        }

        return $device;
    }
}
