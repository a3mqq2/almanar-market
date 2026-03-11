<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CompareCustomerTransactions extends Command
{
    protected $signature = 'sync:compare-customer-transactions
        {--device= : Device ID to use for API auth}
        {--customer= : Compare specific customer ID only}
        {--date-from= : Filter from date (Y-m-d)}
        {--date-to= : Filter to date (Y-m-d)}';

    protected $description = 'Compare local customer transactions with production server and show differences';

    protected string $serverUrl;
    protected string $apiToken;
    protected string $deviceId;

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

        $this->info('Fetching production transactions...');
        $remoteData = $this->fetchRemoteTransactions();

        if ($remoteData === null) {
            return 1;
        }

        $remoteCustomers = collect($remoteData['customers'] ?? []);
        $remoteTransactions = collect($remoteData['transactions'] ?? []);

        $this->info("Production: {$remoteTransactions->count()} transactions");

        $localQuery = CustomerTransaction::query();
        $customerId = $this->option('customer');

        if ($customerId) {
            $localQuery->where('customer_id', $customerId);
        }
        if ($this->option('date-from')) {
            $localQuery->whereDate('transaction_date', '>=', $this->option('date-from'));
        }
        if ($this->option('date-to')) {
            $localQuery->whereDate('transaction_date', '<=', $this->option('date-to'));
        }

        $localTransactions = $localQuery
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->orderBy('type')
            ->orderBy('amount')
            ->orderBy('description')
            ->get();

        $this->info("Local: {$localTransactions->count()} transactions");
        $this->newLine();

        $this->compareCustomerSummary($remoteCustomers);
        $this->newLine();

        $this->compareTransactions($localTransactions, $remoteTransactions);

        return 0;
    }

    protected function fetchRemoteTransactions(): ?array
    {
        $params = [];
        if ($this->option('customer')) {
            $params['customer_id'] = $this->option('customer');
        }
        if ($this->option('date-from')) {
            $params['date_from'] = $this->option('date-from');
        }
        if ($this->option('date-to')) {
            $params['date_to'] = $this->option('date-to');
        }

        try {
            $response = Http::withHeaders([
                'X-Device-ID' => $this->deviceId,
                'X-API-Token' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(60)->get("{$this->serverUrl}/api/v1/sync/compare-customer-transactions", $params);

            if (!$response->successful()) {
                $this->error("Server returned HTTP {$response->status()}");
                $this->error($response->body());
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->error("Connection failed: {$e->getMessage()}");
            return null;
        }
    }

    protected function compareCustomerSummary($remoteCustomers): void
    {
        $this->warn('=== Customer Balances ===');

        $customerQuery = Customer::query();
        if ($this->option('customer')) {
            $customerQuery->where('id', $this->option('customer'));
        }
        $localCustomers = $customerQuery->get();

        $rows = [];
        foreach ($localCustomers as $local) {
            $remote = $remoteCustomers->firstWhere('id', $local->id);
            $remoteBalance = $remote ? (float) $remote['current_balance'] : 0;
            $localBalance = (float) $local->current_balance;
            $diff = $localBalance - $remoteBalance;

            $status = abs($diff) < 0.01 ? 'OK' : 'MISMATCH';

            $rows[] = [
                $local->id,
                $local->name,
                number_format($localBalance, 2),
                $remote ? number_format($remoteBalance, 2) : 'N/A',
                number_format($diff, 2),
                $status,
            ];
        }

        $this->table(
            ['ID', 'Customer', 'Local Balance', 'Production Balance', 'Difference', 'Status'],
            $rows
        );
    }

    protected function compareTransactions($localTransactions, $remoteTransactions): void
    {
        $this->warn('=== Transaction Comparison ===');
        $this->newLine();

        $localGrouped = [];
        foreach ($localTransactions as $t) {
            $key = $this->matchKey($t->customer_id, $t->type, $t->amount, $t->description);
            $localGrouped[$key][] = $t;
        }

        $remoteGrouped = [];
        foreach ($remoteTransactions as $t) {
            $key = $this->matchKey($t['customer_id'], $t['type'], $t['amount'], $t['description']);
            $remoteGrouped[$key][] = $t;
        }

        $allKeys = array_unique(array_merge(array_keys($localGrouped), array_keys($remoteGrouped)));

        $matched = 0;
        $dateMismatches = [];
        $onlyLocal = [];
        $onlyRemote = [];
        $duplicatesLocal = [];
        $duplicatesRemote = [];

        foreach ($allKeys as $key) {
            $localItems = $localGrouped[$key] ?? [];
            $remoteItems = $remoteGrouped[$key] ?? [];

            $localCount = count($localItems);
            $remoteCount = count($remoteItems);

            $pairs = min($localCount, $remoteCount);

            for ($i = 0; $i < $pairs; $i++) {
                $local = $localItems[$i];
                $remote = $remoteItems[$i];

                $localDate = $local->transaction_date?->format('Y-m-d') ?? '';
                $remoteDate = $remote['transaction_date'] ?? '';
                $localCreatedAt = $local->created_at?->format('Y-m-d H:i:s') ?? '';
                $remoteCreatedAt = $remote['created_at'] ?? '';

                $hasDateDiff = $localDate !== $remoteDate || $localCreatedAt !== $remoteCreatedAt;
                $hasBalanceDiff = abs((float) $local->balance_after - (float) $remote['balance_after']) > 0.01;

                if ($hasDateDiff || $hasBalanceDiff) {
                    $dateMismatches[] = [
                        'local' => $local,
                        'remote' => $remote,
                        'date_diff' => $localDate !== $remoteDate,
                        'created_at_diff' => $localCreatedAt !== $remoteCreatedAt,
                        'balance_diff' => $hasBalanceDiff,
                    ];
                } else {
                    $matched++;
                }
            }

            if ($localCount > $remoteCount) {
                for ($i = $pairs; $i < $localCount; $i++) {
                    if ($remoteCount == 0) {
                        $onlyLocal[] = $localItems[$i];
                    } else {
                        $duplicatesLocal[] = $localItems[$i];
                    }
                }
            }

            if ($remoteCount > $localCount) {
                for ($i = $pairs; $i < $remoteCount; $i++) {
                    if ($localCount == 0) {
                        $onlyRemote[] = $remoteItems[$i];
                    } else {
                        $duplicatesRemote[] = $remoteItems[$i];
                    }
                }
            }
        }

        $this->info("Matched (identical): {$matched}");
        $this->info("Matched (date/balance diff): " . count($dateMismatches));
        $this->info("Only in Local: " . count($onlyLocal));
        $this->info("Only in Production: " . count($onlyRemote));
        $this->info("Extra duplicates Local: " . count($duplicatesLocal));
        $this->info("Extra duplicates Production: " . count($duplicatesRemote));
        $this->newLine();

        if (count($onlyLocal) > 0) {
            $this->warn('--- Only in LOCAL (missing from production) ---');
            $this->renderLocalTable(array_slice($onlyLocal, 0, 50));
            if (count($onlyLocal) > 50) {
                $this->comment("... and " . (count($onlyLocal) - 50) . " more");
            }
            $this->newLine();
        }

        if (count($onlyRemote) > 0) {
            $this->warn('--- Only in PRODUCTION (missing from local) ---');
            $this->renderRemoteTable(array_slice($onlyRemote, 0, 50));
            if (count($onlyRemote) > 50) {
                $this->comment("... and " . (count($onlyRemote) - 50) . " more");
            }
            $this->newLine();
        }

        if (count($duplicatesLocal) > 0) {
            $this->warn('--- Extra DUPLICATES in Local ---');
            $this->renderLocalTable(array_slice($duplicatesLocal, 0, 30));
            if (count($duplicatesLocal) > 30) {
                $this->comment("... and " . (count($duplicatesLocal) - 30) . " more");
            }
            $this->newLine();
        }

        if (count($duplicatesRemote) > 0) {
            $this->warn('--- Extra DUPLICATES in Production ---');
            $this->renderRemoteTable(array_slice($duplicatesRemote, 0, 30));
            if (count($duplicatesRemote) > 30) {
                $this->comment("... and " . (count($duplicatesRemote) - 30) . " more");
            }
            $this->newLine();
        }

        if (count($dateMismatches) > 0) {
            $this->warn('--- Date/Balance Mismatches (same transaction, different values) ---');
            $rows = [];
            foreach (array_slice($dateMismatches, 0, 50) as $item) {
                $local = $item['local'];
                $remote = $item['remote'];
                $flags = [];
                if ($item['date_diff']) $flags[] = 'DATE';
                if ($item['created_at_diff']) $flags[] = 'CREATED_AT';
                if ($item['balance_diff']) $flags[] = 'BALANCE';

                $rows[] = [
                    $local->id,
                    $local->customer_id,
                    mb_substr($local->description, 0, 35),
                    number_format((float) $local->amount, 2),
                    $local->transaction_date?->format('Y-m-d'),
                    $remote['transaction_date'] ?? '-',
                    $local->created_at?->format('H:i:s'),
                    isset($remote['created_at']) ? substr($remote['created_at'], 11, 8) : '-',
                    number_format((float) $local->balance_after, 2),
                    number_format((float) $remote['balance_after'], 2),
                    implode(',', $flags),
                ];
            }
            $this->table(
                ['ID', 'Cust', 'Description', 'Amount', 'L.Date', 'P.Date', 'L.Time', 'P.Time', 'L.Balance', 'P.Balance', 'Diff In'],
                $rows
            );
            if (count($dateMismatches) > 50) {
                $this->comment("... and " . (count($dateMismatches) - 50) . " more");
            }
        }

        if ($matched == $localTransactions->count() && $matched == $remoteTransactions->count()) {
            $this->newLine();
            $this->info('All transactions match!');
        }
    }

    protected function matchKey($customerId, $type, $amount, $description): string
    {
        $amount = number_format((float) $amount, 2, '.', '');
        return "{$customerId}|{$type}|{$amount}|{$description}";
    }

    protected function renderLocalTable(array $items): void
    {
        $rows = [];
        foreach ($items as $t) {
            $rows[] = [
                $t->id,
                $t->customer_id,
                $t->type,
                number_format((float) $t->amount, 2),
                mb_substr($t->description, 0, 40),
                $t->transaction_date?->format('Y-m-d'),
                $t->created_at?->format('Y-m-d H:i:s'),
                $t->reference_type ? class_basename($t->reference_type) : '-',
                $t->reference_id ?? '-',
            ];
        }
        $this->table(
            ['ID', 'Cust', 'Type', 'Amount', 'Description', 'Date', 'Created At', 'Ref', 'Ref ID'],
            $rows
        );
    }

    protected function renderRemoteTable(array $items): void
    {
        $rows = [];
        foreach ($items as $t) {
            $rows[] = [
                $t['id'] ?? '-',
                $t['customer_id'] ?? '-',
                $t['type'] ?? '-',
                number_format((float) ($t['amount'] ?? 0), 2),
                mb_substr($t['description'] ?? '-', 0, 40),
                $t['transaction_date'] ?? '-',
                $t['created_at'] ?? '-',
                isset($t['reference_type']) ? class_basename($t['reference_type']) : '-',
                $t['reference_id'] ?? '-',
            ];
        }
        $this->table(
            ['ID', 'Cust', 'Type', 'Amount', 'Description', 'Date', 'Created At', 'Ref', 'Ref ID'],
            $rows
        );
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
