<?php

namespace App\Console\Commands;

use App\Models\DeviceRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FixCustomerTransactions extends Command
{
    protected $signature = 'sync:fix-customer-transactions
        {--customer= : Fix specific customer ID only}
        {--dry-run : Show differences without fixing}';

    protected $description = 'Compare and fix customer transactions from production';

    public function handle(): int
    {
        set_time_limit(0);

        $serverUrl = config('desktop.server_url');
        $device = DeviceRegistration::where('device_id', config('desktop.device_id'))->first();

        if (!$device) {
            $this->error('No device registration found.');
            return 1;
        }

        $this->info('Fetching customer transactions from production...');

        $remoteTransactions = $this->fetchAllRemote($serverUrl, $device);

        if ($remoteTransactions === null) {
            $this->error('Failed to fetch from production.');
            return 1;
        }

        $this->info('Got ' . count($remoteTransactions) . ' transactions from production.');

        $customerId = $this->option('customer');
        $localTransactions = DB::table('customer_transactions')
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->get()
            ->keyBy('id');

        $missing = [];
        $different = [];

        foreach ($remoteTransactions as $id => $remote) {
            if ($customerId && ($remote['customer_id'] ?? null) != $customerId) continue;

            $local = $localTransactions->get($id);

            if (!$local) {
                $missing[$id] = $remote;
            } else {
                $diffs = [];
                $remoteDate = substr($remote['transaction_date'] ?? '', 0, 10);
                $localDate = substr($local->transaction_date, 0, 10);

                if ($localDate !== $remoteDate) $diffs['transaction_date'] = [$localDate, $remoteDate];
                if (abs((float) ($local->amount ?? 0) - (float) ($remote['amount'] ?? 0)) > 0.01) {
                    $diffs['amount'] = [$local->amount, $remote['amount']];
                }
                if (($local->type ?? '') !== ($remote['type'] ?? '')) {
                    $diffs['type'] = [$local->type, $remote['type']];
                }

                if (!empty($diffs)) {
                    $different[$id] = ['diffs' => $diffs, 'remote' => $remote];
                }
            }
        }

        if (empty($missing) && empty($different)) {
            $this->info('All transactions match!');
            return 0;
        }

        if (!empty($missing)) {
            $this->warn('Missing locally: ' . count($missing));
            $rows = [];
            foreach ($missing as $id => $r) {
                $rows[] = [$id, $r['customer_id'] ?? '-', $r['type'] ?? '-', $r['amount'] ?? 0, substr($r['transaction_date'] ?? '', 0, 10), mb_substr($r['description'] ?? '-', 0, 40)];
            }
            $this->table(['ID', 'Customer', 'Type', 'Amount', 'Date', 'Description'], $rows);
        }

        if (!empty($different)) {
            $this->warn('Different: ' . count($different));
            foreach ($different as $id => $info) {
                $parts = [];
                foreach ($info['diffs'] as $field => [$local, $remote]) {
                    $parts[] = "{$field}: {$local} → {$remote}";
                }
                $this->line("  ID {$id}: " . implode(', ', $parts));
            }
        }

        if ($this->option('dry-run')) {
            return 0;
        }

        $this->newLine();
        $this->info('Fixing...');

        $fixed = 0;

        foreach ($missing as $id => $remote) {
            $insertData = [
                'id' => $id,
                'customer_id' => $remote['customer_id'] ?? null,
                'type' => $remote['type'] ?? null,
                'amount' => $remote['amount'] ?? 0,
                'balance_after' => $remote['balance_after'] ?? 0,
                'description' => $remote['description'] ?? null,
                'reference_type' => $remote['reference_type'] ?? null,
                'reference_id' => $remote['reference_id'] ?? null,
                'cashbox_id' => $remote['cashbox_id'] ?? null,
                'transaction_date' => substr($remote['transaction_date'] ?? '', 0, 10),
                'created_by' => $remote['created_by'] ?? null,
                'device_id' => $remote['device_id'] ?? null,
                'local_uuid' => $remote['local_uuid'] ?? null,
                'synced_at' => now(),
                'created_at' => $remote['created_at'] ?? now(),
                'updated_at' => $remote['updated_at'] ?? now(),
            ];

            DB::table('customer_transactions')->insert($insertData);
            $this->line("  Inserted ID {$id}");
            $fixed++;
        }

        foreach ($different as $id => $info) {
            $updateData = [];
            foreach ($info['diffs'] as $field => [$local, $remote]) {
                $updateData[$field] = $field === 'transaction_date' ? substr($remote, 0, 10) : $remote;
            }
            $updateData['synced_at'] = now();

            DB::table('customer_transactions')->where('id', $id)->update($updateData);
            $this->line("  Updated ID {$id}");
            $fixed++;
        }

        $this->newLine();
        $this->info("Fixed {$fixed} transactions.");

        $this->info('Recalculating customer balances...');
        $this->call('customers:recalc', $customerId ? ['--customer' => $customerId] : []);

        return 0;
    }

    protected function fetchAllRemote(string $serverUrl, DeviceRegistration $device): ?array
    {
        $transactions = [];
        $offsetId = 0;
        $page = 0;
        $modelClass = 'App\Models\CustomerTransaction';

        do {
            $page++;
            $this->output->write("  Page {$page}...");

            try {
                /** @var \Illuminate\Http\Client\Response $response */
                $response = Http::withToken($device->api_token)
                    ->timeout(300)
                    ->get($serverUrl . '/api/v1/sync/pull', [
                        'device_id' => $device->device_id,
                        'limit' => 500,
                        'offset_model' => $modelClass,
                        'offset_id' => $offsetId,
                    ]);

                if (!$response->successful()) {
                    $this->error(' failed');
                    return count($transactions) > 0 ? $transactions : null;
                }
            } catch (\Exception) {
                $this->error(' timeout');
                return count($transactions) > 0 ? $transactions : null;
            }

            $data = $response->json();
            $changes = $data['changes'] ?? [];
            $count = 0;

            foreach ($changes as $change) {
                if ($change['type'] !== $modelClass) continue;
                $id = $change['id'] ?? null;
                if ($id) {
                    $transactions[$id] = $change['payload'];
                    $offsetId = $id;
                    $count++;
                }
            }

            $this->info(" {$count} records");
            if ($count === 0) break;

            $hasMore = $data['has_more'] ?? false;
        } while ($hasMore);

        return $transactions;
    }
}
