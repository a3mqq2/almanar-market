<?php

namespace App\Console\Commands;

use App\Models\DeviceRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FixTransactionDates extends Command
{
    protected $signature = 'sync:fix-dates {--dry-run : Show differences without fixing}';

    protected $description = 'Compare transaction dates with production and fix local mismatches';

    public function handle(): int
    {
        $serverUrl = config('desktop.server_url');
        $device = DeviceRegistration::where('device_id', config('desktop.device_id'))->first();

        if (!$device) {
            $this->error('No device registration found.');
            return 1;
        }

        $tables = [
            'customer_transactions' => \App\Models\CustomerTransaction::class,
            'supplier_transactions' => \App\Models\SupplierTransaction::class,
            'cashbox_transactions' => \App\Models\CashboxTransaction::class,
        ];

        $totalFixed = 0;

        foreach ($tables as $tableName => $modelClass) {
            $this->info("Checking {$tableName}...");

            $remoteDates = $this->fetchRemoteDates($serverUrl, $device, $modelClass);

            if ($remoteDates === null) {
                $this->error("  Failed to fetch from production.");
                continue;
            }

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
            $this->call('cashbox:recalc');
        }

        return 0;
    }

    protected function fetchRemoteDates(string $serverUrl, DeviceRegistration $device, string $modelClass): ?array
    {
        $dates = [];
        $offsetId = 0;

        do {
            $params = [
                'device_id' => $device->device_id,
                'limit' => 5000,
                'offset_model' => $modelClass,
                'offset_id' => $offsetId,
            ];

            $response = Http::withToken($device->api_token)
                ->timeout(120)
                ->get($serverUrl . '/api/v1/sync/pull', $params);

            if (!$response->successful()) return null;

            $data = $response->json();
            $changes = $data['changes'] ?? [];

            foreach ($changes as $change) {
                if ($change['type'] !== $modelClass) continue;
                $id = $change['id'] ?? null;
                $date = $change['payload']['transaction_date'] ?? null;
                if ($id && $date) {
                    $dates[$id] = $date;
                    $offsetId = $id;
                }
            }

            $hasMore = $data['has_more'] ?? false;
        } while ($hasMore && !empty($changes));

        return $dates;
    }
}
