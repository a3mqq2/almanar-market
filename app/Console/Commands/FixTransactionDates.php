<?php

namespace App\Console\Commands;

use App\Models\DeviceRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FixTransactionDates extends Command
{
    protected $signature = 'sync:fix-dates
        {--dry-run : Show differences without fixing}
        {--table= : Fix specific table only (customer_transactions, supplier_transactions, cashbox_transactions)}';

    protected $description = 'Compare transaction dates with production and fix local mismatches';

    public function handle(): int
    {
        set_time_limit(0);

        $serverUrl = config('desktop.server_url');
        $device = DeviceRegistration::where('device_id', config('desktop.device_id'))->first();

        if (!$device) {
            $this->error('No device registration found.');
            return 1;
        }

        $allTables = [
            'customer_transactions' => \App\Models\CustomerTransaction::class,
            'supplier_transactions' => \App\Models\SupplierTransaction::class,
            'cashbox_transactions' => \App\Models\CashboxTransaction::class,
        ];

        $onlyTable = $this->option('table');
        $tables = $onlyTable ? [$onlyTable => $allTables[$onlyTable] ?? null] : $allTables;

        if ($onlyTable && !isset($allTables[$onlyTable])) {
            $this->error("Unknown table: {$onlyTable}");
            return 1;
        }

        $totalFixed = 0;

        foreach ($tables as $tableName => $modelClass) {
            $this->info("Checking {$tableName}...");

            $remoteDates = $this->fetchRemoteDates($serverUrl, $device, $modelClass);

            if ($remoteDates === null) {
                $this->error("  Failed to fetch from production.");
                continue;
            }

            $this->info("  Got " . count($remoteDates) . " records from production.");

            $localRecords = DB::table($tableName)->get(['id', 'transaction_date']);
            $fixed = 0;

            foreach ($localRecords as $local) {
                $remoteDate = $remoteDates[$local->id] ?? null;
                if (!$remoteDate) continue;

                $localDate = substr($local->transaction_date, 0, 10);
                $remoteDate = substr($remoteDate, 0, 10);

                if ($localDate !== $remoteDate) {
                    if ($this->option('dry-run')) {
                        $this->warn("  ID {$local->id}: {$localDate} → {$remoteDate}");
                    } else {
                        DB::table($tableName)->where('id', $local->id)->update([
                            'transaction_date' => $remoteDate,
                        ]);
                    }
                    $fixed++;
                }
            }

            if ($fixed > 0) {
                $this->info("  {$tableName}: {$fixed} dates " . ($this->option('dry-run') ? 'need fixing' : 'fixed'));
            } else {
                $this->info("  {$tableName}: all dates match");
            }

            $totalFixed += $fixed;
        }

        if ($totalFixed > 0 && !$this->option('dry-run')) {
            $this->newLine();
            $this->info('Recalculating balances...');
            $this->call('customers:recalc');
            $this->call('suppliers:recalc');
            $this->call('cashbox:recalc');
        }

        return 0;
    }

    protected function fetchRemoteDates(string $serverUrl, DeviceRegistration $device, string $modelClass): ?array
    {
        $dates = [];
        $offsetId = 0;
        $page = 0;

        do {
            $page++;
            $this->output->write("  Fetching page {$page}...");

            $data = $this->fetchPage($serverUrl, $device, $modelClass, $offsetId);

            if ($data === null) {
                $this->error(" failed");
                return count($dates) > 0 ? $dates : null;
            }

            $changes = $data['changes'] ?? [];
            $count = 0;

            foreach ($changes as $change) {
                if ($change['type'] !== $modelClass) continue;
                $id = $change['id'] ?? null;
                $date = $change['payload']['transaction_date'] ?? null;
                if ($id && $date) {
                    $dates[$id] = $date;
                    $offsetId = $id;
                    $count++;
                }
            }

            $this->info(" {$count} records");

            $hasMore = $data['has_more'] ?? false;
        } while ($hasMore && !empty($changes));

        return $dates;
    }

    protected function fetchPage(string $serverUrl, DeviceRegistration $device, string $modelClass, int $offsetId, int $limit = 500): ?array
    {
        $params = [
            'device_id' => $device->device_id,
            'limit' => $limit,
            'offset_model' => $modelClass,
            'offset_id' => $offsetId,
        ];

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withToken($device->api_token)
                ->timeout(300)
                ->get($serverUrl . '/api/v1/sync/pull', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception) {
            if ($limit > 100) {
                $this->warn(" timeout, retrying with smaller batch...");
                return $this->fetchPage($serverUrl, $device, $modelClass, $offsetId, (int) ($limit / 2));
            }

            return null;
        }
    }
}
