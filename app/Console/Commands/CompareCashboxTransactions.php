<?php

namespace App\Console\Commands;

use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CompareCashboxTransactions extends Command
{
    protected $signature = 'sync:compare-cashbox-transactions
        {--device= : Device ID to use for API auth}
        {--cashbox= : Compare specific cashbox ID only}
        {--date-from= : Filter from date (Y-m-d)}
        {--date-to= : Filter to date (Y-m-d)}';

    protected $description = 'Compare local cashbox transactions with production server and show differences';

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

        $remoteCashboxes = collect($remoteData['cashboxes'] ?? []);
        $remoteTransactions = collect($remoteData['transactions'] ?? []);

        $this->info("Production: {$remoteTransactions->count()} transactions");

        $localQuery = CashboxTransaction::query();
        $cashboxId = $this->option('cashbox');

        if ($cashboxId) {
            $localQuery->where('cashbox_id', $cashboxId);
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
            ->orderBy('id')
            ->get();

        $this->info("Local: {$localTransactions->count()} transactions");
        $this->newLine();

        $this->compareCashboxSummary($remoteCashboxes);
        $this->newLine();

        $this->compareTransactions($localTransactions, $remoteTransactions);

        return 0;
    }

    protected function fetchRemoteTransactions(): ?array
    {
        $params = [];
        if ($this->option('cashbox')) {
            $params['cashbox_id'] = $this->option('cashbox');
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
            ])->timeout(60)->get("{$this->serverUrl}/api/v1/sync/compare-cashbox-transactions", $params);

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

    protected function compareCashboxSummary($remoteCashboxes): void
    {
        $this->warn('=== Cashbox Balances ===');

        $cashboxQuery = Cashbox::query();
        if ($this->option('cashbox')) {
            $cashboxQuery->where('id', $this->option('cashbox'));
        }
        $localCashboxes = $cashboxQuery->get();

        $rows = [];
        foreach ($localCashboxes as $local) {
            $remote = $remoteCashboxes->firstWhere('id', $local->id);
            $remoteBalance = $remote ? (float) $remote['current_balance'] : 0;
            $localBalance = (float) $local->current_balance;
            $diff = $localBalance - $remoteBalance;

            $status = abs($diff) < 0.01 ? '✅' : '❌';

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
            ['ID', 'Cashbox', 'Local Balance', 'Production Balance', 'Difference', ''],
            $rows
        );
    }

    protected function compareTransactions($localTransactions, $remoteTransactions): void
    {
        $this->warn('=== Transaction Comparison ===');

        $localByKey = $localTransactions->keyBy(function ($t) {
            return $this->transactionKey($t);
        });

        $remoteByKey = $remoteTransactions->keyBy(function ($t) {
            return $this->transactionKeyFromArray($t);
        });

        $onlyLocal = [];
        $onlyRemote = [];
        $different = [];

        foreach ($localByKey as $key => $local) {
            if (!$remoteByKey->has($key)) {
                $onlyLocal[] = $local;
            } else {
                $remote = $remoteByKey[$key];
                $diffs = $this->findFieldDiffs($local, $remote);
                if (!empty($diffs)) {
                    $different[] = [
                        'local' => $local,
                        'remote' => $remote,
                        'diffs' => $diffs,
                    ];
                }
            }
        }

        foreach ($remoteByKey as $key => $remote) {
            if (!$localByKey->has($key)) {
                $onlyRemote[] = $remote;
            }
        }

        $this->info("Matched: " . ($localByKey->count() - count($onlyLocal) - count($different)));
        $this->info("Only in Local: " . count($onlyLocal));
        $this->info("Only in Production: " . count($onlyRemote));
        $this->info("Different values: " . count($different));
        $this->newLine();

        if (count($onlyLocal) > 0) {
            $this->warn('--- Only in LOCAL (missing from production) ---');
            $rows = [];
            foreach (array_slice($onlyLocal, 0, 50) as $t) {
                $rows[] = [
                    $t->id,
                    $t->cashbox_id,
                    $t->type,
                    number_format((float) $t->amount, 2),
                    $t->description,
                    $t->transaction_date?->format('Y-m-d'),
                    $t->created_at?->format('Y-m-d H:i:s'),
                    $t->reference_type ? class_basename($t->reference_type) : '-',
                    $t->reference_id ?? '-',
                ];
            }
            $this->table(
                ['ID', 'Cashbox', 'Type', 'Amount', 'Description', 'Date', 'Created At', 'Ref Type', 'Ref ID'],
                $rows
            );
            if (count($onlyLocal) > 50) {
                $this->comment("... and " . (count($onlyLocal) - 50) . " more");
            }
            $this->newLine();
        }

        if (count($onlyRemote) > 0) {
            $this->warn('--- Only in PRODUCTION (missing from local) ---');
            $rows = [];
            foreach (array_slice($onlyRemote, 0, 50) as $t) {
                $rows[] = [
                    $t['id'] ?? '-',
                    $t['cashbox_id'] ?? '-',
                    $t['type'] ?? '-',
                    number_format((float) ($t['amount'] ?? 0), 2),
                    $t['description'] ?? '-',
                    $t['transaction_date'] ?? '-',
                    $t['created_at'] ?? '-',
                    isset($t['reference_type']) ? class_basename($t['reference_type']) : '-',
                    $t['reference_id'] ?? '-',
                ];
            }
            $this->table(
                ['ID', 'Cashbox', 'Type', 'Amount', 'Description', 'Date', 'Created At', 'Ref Type', 'Ref ID'],
                $rows
            );
            if (count($onlyRemote) > 50) {
                $this->comment("... and " . (count($onlyRemote) - 50) . " more");
            }
            $this->newLine();
        }

        if (count($different) > 0) {
            $this->warn('--- Different Values ---');
            foreach (array_slice($different, 0, 30) as $item) {
                $local = $item['local'];
                $this->line("Transaction #{$local->id} | Cashbox: {$local->cashbox_id} | {$local->transaction_date?->format('Y-m-d')} | {$local->description}");
                foreach ($item['diffs'] as $field => $vals) {
                    $this->line("  {$field}: LOCAL={$vals['local']} | PRODUCTION={$vals['remote']}");
                }
            }
            if (count($different) > 30) {
                $this->comment("... and " . (count($different) - 30) . " more");
            }
        }

        if (count($onlyLocal) == 0 && count($onlyRemote) == 0 && count($different) == 0) {
            $this->info('✅ All transactions match!');
        }
    }

    protected function transactionKey($t): string
    {
        $date = $t->transaction_date?->format('Y-m-d') ?? '';
        $createdAt = $t->created_at?->format('Y-m-d H:i:s') ?? '';
        $amount = number_format((float) $t->amount, 2, '.', '');
        $type = $t->type ?? '';
        $cashboxId = $t->cashbox_id ?? '';
        $refType = $t->reference_type ?? '';
        $refId = $t->reference_id ?? '';

        return "{$cashboxId}|{$type}|{$amount}|{$date}|{$createdAt}|{$refType}|{$refId}";
    }

    protected function transactionKeyFromArray(array $t): string
    {
        $date = $t['transaction_date'] ?? '';
        if (strlen($date) > 10) {
            $date = substr($date, 0, 10);
        }
        $createdAt = $t['created_at'] ?? '';
        $amount = number_format((float) ($t['amount'] ?? 0), 2, '.', '');
        $type = $t['type'] ?? '';
        $cashboxId = $t['cashbox_id'] ?? '';
        $refType = $t['reference_type'] ?? '';
        $refId = $t['reference_id'] ?? '';

        return "{$cashboxId}|{$type}|{$amount}|{$date}|{$createdAt}|{$refType}|{$refId}";
    }

    protected function findFieldDiffs($local, array $remote): array
    {
        $diffs = [];

        $fields = ['amount', 'balance_after', 'description', 'type'];

        foreach ($fields as $field) {
            $localVal = $local->$field ?? '';
            $remoteVal = $remote[$field] ?? '';

            if ($field === 'amount' || $field === 'balance_after') {
                if (abs((float) $localVal - (float) $remoteVal) > 0.01) {
                    $diffs[$field] = [
                        'local' => number_format((float) $localVal, 2),
                        'remote' => number_format((float) $remoteVal, 2),
                    ];
                }
            } elseif ((string) $localVal !== (string) $remoteVal) {
                $diffs[$field] = [
                    'local' => (string) $localVal,
                    'remote' => (string) $remoteVal,
                ];
            }
        }

        return $diffs;
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
