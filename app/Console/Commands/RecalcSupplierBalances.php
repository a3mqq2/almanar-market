<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcSupplierBalances extends Command
{
    protected $signature = 'suppliers:recalc {--supplier= : Specific supplier ID}';

    protected $description = 'Recalculate balance_after for all supplier transactions';

    public function handle(): int
    {
        $supplierId = $this->option('supplier');

        if ($supplierId) {
            $suppliers = Supplier::where('id', $supplierId)->get();
        } else {
            $suppliers = Supplier::all();
        }

        $totalFixed = 0;

        foreach ($suppliers as $supplier) {
            $transactions = SupplierTransaction::where('supplier_id', $supplier->id)
                ->orderBy('transaction_date')
                ->orderBy('created_at')
                ->orderBy('type')
                ->orderBy('amount')
                ->get();

            if ($transactions->isEmpty()) continue;

            $balance = (float) $supplier->opening_balance;
            $fixed = 0;

            foreach ($transactions as $t) {
                $balance = $t->type === 'debit'
                    ? $balance + (float) $t->amount
                    : $balance - (float) $t->amount;

                if (abs((float) $t->balance_after - $balance) > 0.001) {
                    DB::table('supplier_transactions')
                        ->where('id', $t->id)
                        ->update(['balance_after' => round($balance, 2)]);
                    $fixed++;
                }
            }

            if ($fixed > 0 || abs((float) $supplier->current_balance - $balance) > 0.001) {
                $this->info("{$supplier->name} (ID: {$supplier->id}): {$transactions->count()} transactions, {$fixed} fixed");
            }

            if (abs((float) $supplier->current_balance - $balance) > 0.001) {
                $this->warn("  Balance: {$supplier->current_balance} → {$balance}");
                DB::table('suppliers')->where('id', $supplier->id)->update([
                    'current_balance' => round($balance, 2),
                    'updated_at' => now(),
                ]);
            }

            $totalFixed += $fixed;
        }

        if ($totalFixed === 0) {
            $this->info('All supplier balances are correct.');
        } else {
            $this->info("Fixed {$totalFixed} transactions.");
        }

        return 0;
    }
}
