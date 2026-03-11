<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FixSupplierTransactions extends Command
{
    protected $signature = 'sync:fix-supplier-transactions
        {--device= : Device ID to use for API auth}
        {--dry-run : Show what would change without applying}';

    protected $description = 'Make local supplier transactions match production: pull missing, fix dates, remove duplicates, recalculate balances';

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
        $dryRun = $this->option('dry-run');

        $this->info("Server: {$this->serverUrl}");
        $this->info("Device: {$device->device_name} ({$this->deviceId})");
        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
        }
        $this->newLine();

        $this->info('Step 1: Fetching production transactions...');
        $remoteData = $this->fetchRemoteTransactions();
        if ($remoteData === null) {
            return 1;
        }

        $remoteTransactions = collect($remoteData['transactions'] ?? []);
        $remoteSuppliers = collect($remoteData['suppliers'] ?? []);
        $this->info("Production: {$remoteTransactions->count()} transactions");

        $localTransactions = SupplierTransaction::orderBy('transaction_date')
            ->orderBy('created_at')
            ->orderBy('type')
            ->orderBy('amount')
            ->get();
        $this->info("Local: {$localTransactions->count()} transactions");
        $this->newLine();

        $this->info('Step 2: Analyzing differences...');
        $analysis = $this->analyze($localTransactions, $remoteTransactions);
        $this->newLine();

        $this->table(['Category', 'Count'], [
            ['Matched (identical)', $analysis['matched']],
            ['Date/time mismatches to fix', count($analysis['date_mismatches'])],
            ['Missing locally (to pull)', count($analysis['only_remote'])],
            ['Missing in production (local only)', count($analysis['only_local'])],
            ['Extra duplicates locally', count($analysis['duplicates_local'])],
            ['Extra duplicates in production', count($analysis['duplicates_remote'])],
        ]);
        $this->newLine();

        if (count($analysis['only_remote']) == 0
            && count($analysis['date_mismatches']) == 0
            && count($analysis['only_local']) == 0
            && count($analysis['duplicates_local']) == 0
            && count($analysis['duplicates_remote']) == 0
        ) {
            $this->info('Nothing to fix!');
            return 0;
        }

        if (!$dryRun && !$this->confirm('Proceed with fixes?')) {
            return 0;
        }

        $fixedDates = 0;
        $pulled = 0;
        $deletedLocalDupes = 0;
        $deletedRemoteDupes = 0;

        if (count($analysis['date_mismatches']) > 0) {
            $this->info('Step 3: Fixing date/time mismatches (production is source of truth)...');
            foreach ($analysis['date_mismatches'] as $item) {
                $local = $item['local'];
                $remote = $item['remote'];

                if (!$dryRun) {
                    DB::table('supplier_transactions')
                        ->where('id', $local->id)
                        ->update([
                            'transaction_date' => $remote['transaction_date'],
                            'created_at' => $remote['created_at'],
                            'updated_at' => $remote['updated_at'] ?? $remote['created_at'],
                        ]);
                }
                $fixedDates++;
            }
            $this->info("  Fixed dates: {$fixedDates}");
            $this->newLine();
        }

        if (count($analysis['only_remote']) > 0) {
            $this->info('Step 4: Pulling missing transactions from production...');

            $localSupplierIds = DB::table('suppliers')->pluck('id')->flip();
            $localCashboxIds = DB::table('cashboxes')->pluck('id')->flip();
            $localUserIds = DB::table('users')->pluck('id')->flip();
            $skipped = 0;

            foreach ($analysis['only_remote'] as $remote) {
                if (!$dryRun) {
                    if (!$localSupplierIds->has($remote['supplier_id'])) {
                        $this->warn("  Skip (supplier {$remote['supplier_id']} not found): {$remote['description']}");
                        $skipped++;
                        continue;
                    }

                    $cashboxId = isset($remote['cashbox_id']) && $localCashboxIds->has($remote['cashbox_id'])
                        ? $remote['cashbox_id'] : null;
                    $createdBy = isset($remote['created_by']) && $localUserIds->has($remote['created_by'])
                        ? $remote['created_by'] : null;

                    try {
                        DB::table('supplier_transactions')->insert([
                            'supplier_id' => $remote['supplier_id'],
                            'type' => $remote['type'],
                            'amount' => $remote['amount'],
                            'balance_after' => $remote['balance_after'],
                            'description' => $remote['description'],
                            'reference_type' => $remote['reference_type'],
                            'reference_id' => $remote['reference_id'],
                            'cashbox_id' => $cashboxId,
                            'transaction_date' => $remote['transaction_date'],
                            'created_by' => $createdBy,
                            'created_at' => $remote['created_at'],
                            'updated_at' => $remote['updated_at'] ?? $remote['created_at'],
                        ]);
                    } catch (\Exception $e) {
                        $this->warn("  Skip: {$remote['description']} - {$e->getMessage()}");
                        $skipped++;
                        continue;
                    }
                }
                $pulled++;
            }
            $this->info("  Pulled: {$pulled}" . ($skipped > 0 ? ", Skipped: {$skipped}" : ''));
            $this->newLine();
        }

        $deletedOnlyLocal = 0;
        if (count($analysis['only_local']) > 0) {
            $this->info('Step 5: Removing local-only transactions (not in production)...');
            $localOnlyIds = collect($analysis['only_local'])->pluck('id')->toArray();
            if (!$dryRun) {
                $deletedOnlyLocal = DB::table('supplier_transactions')
                    ->whereIn('id', $localOnlyIds)
                    ->delete();
            } else {
                $deletedOnlyLocal = count($localOnlyIds);
            }
            $this->info("  Deleted local-only: {$deletedOnlyLocal}");
            $this->newLine();
        }

        if (count($analysis['duplicates_local']) > 0) {
            $this->info('Step 6: Removing local duplicates...');
            $dupeIds = collect($analysis['duplicates_local'])->pluck('id')->toArray();
            if (!$dryRun) {
                $deletedLocalDupes = DB::table('supplier_transactions')
                    ->whereIn('id', $dupeIds)
                    ->delete();
            } else {
                $deletedLocalDupes = count($dupeIds);
            }
            $this->info("  Deleted local duplicates: {$deletedLocalDupes}");
            $this->newLine();
        }

        if (count($analysis['duplicates_remote']) > 0) {
            $this->info('Step 7: Removing production duplicates...');
            $dupeIds = collect($analysis['duplicates_remote'])->pluck('id')->toArray();
            if (!$dryRun) {
                $deletedRemoteDupes = $this->deleteRemoteDuplicates($dupeIds);
            } else {
                $deletedRemoteDupes = count($dupeIds);
            }
            $this->info("  Deleted production duplicates: {$deletedRemoteDupes}");
            $this->newLine();
        }

        if (!$dryRun) {
            $this->info('Step 8: Recalculating local balances...');
            $this->recalcLocal();
            $this->newLine();

            $this->info('Step 9: Recalculating production balances...');
            $this->recalcRemote();
            $this->newLine();

            $this->info('Step 10: Verifying...');
            $this->verify();
        }

        $this->newLine();
        $this->warn('=== Summary ===');
        $this->table(['Action', 'Count'], [
            ['Dates fixed', $fixedDates],
            ['Transactions pulled from production', $pulled],
            ['Local-only deleted (not in production)', $deletedOnlyLocal],
            ['Local duplicates deleted', $deletedLocalDupes],
            ['Production duplicates deleted', $deletedRemoteDupes],
        ]);

        if ($dryRun) {
            $this->warn('DRY RUN - no changes were made. Remove --dry-run to apply.');
        } else {
            $this->info('Done! Local and production should now match.');
        }

        return 0;
    }

    protected function analyze($localTransactions, $remoteTransactions): array
    {
        $localGrouped = [];
        foreach ($localTransactions as $t) {
            $key = $this->matchKey($t->supplier_id, $t->type, $t->amount, $t->description);
            $localGrouped[$key][] = $t;
        }

        $remoteGrouped = [];
        foreach ($remoteTransactions as $t) {
            $key = $this->matchKey($t['supplier_id'], $t['type'], $t['amount'], $t['description']);
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

                if ($localDate !== $remoteDate || $localCreatedAt !== $remoteCreatedAt) {
                    $dateMismatches[] = ['local' => $local, 'remote' => $remote];
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

        return [
            'matched' => $matched,
            'date_mismatches' => $dateMismatches,
            'only_local' => $onlyLocal,
            'only_remote' => $onlyRemote,
            'duplicates_local' => $duplicatesLocal,
            'duplicates_remote' => $duplicatesRemote,
        ];
    }

    protected function recalcLocal(): void
    {
        $suppliers = Supplier::all();

        foreach ($suppliers as $supplier) {
            $transactions = SupplierTransaction::where('supplier_id', $supplier->id)
                ->orderBy('transaction_date')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $balance = (float) $supplier->opening_balance;
            $fixed = 0;

            foreach ($transactions as $t) {
                if ($t->type === 'debit') {
                    $balance += (float) $t->amount;
                } else {
                    $balance -= (float) $t->amount;
                }

                if (abs((float) $t->balance_after - $balance) > 0.001) {
                    DB::table('supplier_transactions')
                        ->where('id', $t->id)
                        ->update(['balance_after' => round($balance, 2)]);
                    $fixed++;
                }
            }

            if (abs((float) $supplier->current_balance - $balance) > 0.001) {
                $this->line("  {$supplier->name}: {$supplier->current_balance} -> " . round($balance, 2));
                $supplier->current_balance = round($balance, 2);
                $supplier->saveQuietly();
            }

            $this->info("  {$supplier->name}: {$transactions->count()} txns, {$fixed} balance_after fixed, balance = " . round($balance, 2));
        }
    }

    protected function recalcRemote(): void
    {
        try {
            $response = Http::withHeaders([
                'X-Device-ID' => $this->deviceId,
                'X-API-Token' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(120)->post("{$this->serverUrl}/api/v1/sync/recalc-supplier-balances");

            if ($response->successful()) {
                $results = $response->json('results', []);
                foreach ($results as $r) {
                    $this->info("  {$r['name']}: {$r['old_balance']} -> {$r['new_balance']} ({$r['transactions_fixed']} fixed)");
                }
            } else {
                $this->error("  Failed: HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $this->error("  Failed: {$e->getMessage()}");
        }
    }

    protected function deleteRemoteDuplicates(array $ids): int
    {
        try {
            $response = Http::withHeaders([
                'X-Device-ID' => $this->deviceId,
                'X-API-Token' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(60)->post("{$this->serverUrl}/api/v1/sync/delete-supplier-transactions", [
                'ids' => $ids,
            ]);

            if ($response->successful()) {
                return $response->json('deleted', 0);
            }

            $this->error("  Delete failed: HTTP {$response->status()}");
            return 0;
        } catch (\Exception $e) {
            $this->error("  Delete failed: {$e->getMessage()}");
            return 0;
        }
    }

    protected function verify(): void
    {
        $remoteData = $this->fetchRemoteTransactions();
        if ($remoteData === null) {
            $this->error('  Could not verify - failed to fetch production data');
            return;
        }

        $remoteSuppliers = collect($remoteData['suppliers'] ?? []);
        $localSuppliers = Supplier::all();

        $allMatch = true;
        foreach ($localSuppliers as $local) {
            $remote = $remoteSuppliers->firstWhere('id', $local->id);
            if (!$remote) continue;

            $localBalance = (float) $local->fresh()->current_balance;
            $remoteBalance = (float) $remote['current_balance'];
            $diff = abs($localBalance - $remoteBalance);

            $status = $diff < 0.01 ? 'OK' : 'MISMATCH';
            if ($diff >= 0.01) $allMatch = false;

            $this->line("  {$local->name}: Local={$localBalance} Production={$remoteBalance} [{$status}]");
        }

        $remoteCount = count($remoteData['transactions'] ?? []);
        $localCount = SupplierTransaction::count();
        $this->line("  Transaction count: Local={$localCount} Production={$remoteCount}");

        if ($allMatch && $localCount == $remoteCount) {
            $this->info('  Verification PASSED!');
        } else {
            $this->warn('  Verification shows remaining differences - may need another run');
        }
    }

    protected function fetchRemoteTransactions(): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Device-ID' => $this->deviceId,
                'X-API-Token' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(60)->get("{$this->serverUrl}/api/v1/sync/compare-supplier-transactions");

            if (!$response->successful()) {
                $this->error("Server returned HTTP {$response->status()}");
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->error("Connection failed: {$e->getMessage()}");
            return null;
        }
    }

    protected function matchKey($supplierId, $type, $amount, $description): string
    {
        $amount = number_format((float) $amount, 2, '.', '');
        return "{$supplierId}|{$type}|{$amount}|{$description}";
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
