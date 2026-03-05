<?php

namespace App\Console\Commands;

use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\SalePayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileCashboxes extends Command
{
    protected $signature = 'cashbox:reconcile
        {--dry-run : Show what would change without applying}
        {--recalc : Recalculate balance_after from transactions}';

    protected $description = 'Reconcile cashbox balances: rebuild balance_after chain and fix current_balance';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('=== Cashbox Reconciliation ===');
        $this->newLine();

        $cashboxes = Cashbox::all();

        foreach ($cashboxes as $cashbox) {
            $this->warn("Cashbox #{$cashbox->id}: {$cashbox->name}");

            $transactions = CashboxTransaction::where('cashbox_id', $cashbox->id)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $salePaymentsTotal = SalePayment::where('cashbox_id', $cashbox->id)
                ->whereHas('sale', fn($q) => $q->where('status', 'completed'))
                ->sum('amount');

            $txnInFromSales = CashboxTransaction::where('cashbox_id', $cashbox->id)
                ->where('type', 'in')
                ->where('reference_type', 'App\Models\Sale')
                ->sum('amount');

            $balance = (float) $cashbox->opening_balance;
            $fixed = 0;

            if (!$dryRun && $this->option('recalc')) {
                DB::beginTransaction();
                foreach ($transactions as $t) {
                    if (in_array($t->type, ['in', 'transfer_in'])) {
                        $balance += (float) $t->amount;
                    } else {
                        $balance -= (float) $t->amount;
                    }

                    if (abs((float) $t->balance_after - $balance) > 0.001) {
                        DB::table('cashbox_transactions')
                            ->where('id', $t->id)
                            ->update(['balance_after' => round($balance, 2)]);
                        $fixed++;
                    }
                }

                if (abs((float) $cashbox->current_balance - $balance) > 0.001) {
                    $cashbox->current_balance = round($balance, 2);
                    $cashbox->saveQuietly();
                }
                DB::commit();
            } else {
                foreach ($transactions as $t) {
                    if (in_array($t->type, ['in', 'transfer_in'])) {
                        $balance += (float) $t->amount;
                    } else {
                        $balance -= (float) $t->amount;
                    }
                    if (abs((float) $t->balance_after - $balance) > 0.001) {
                        $fixed++;
                    }
                }
            }

            $totalIn = $transactions->whereIn('type', ['in', 'transfer_in'])->sum('amount');
            $totalOut = $transactions->whereIn('type', ['out', 'transfer_out'])->sum('amount');

            $this->table([], [
                ['Opening balance', number_format($cashbox->opening_balance, 2)],
                ['Current balance', number_format($cashbox->current_balance, 2)],
                ['Calculated balance', number_format($balance, 2)],
                ['Total IN', number_format($totalIn, 2)],
                ['Total OUT', number_format($totalOut, 2)],
                ['Transactions', $transactions->count()],
                ['Wrong balance_after', $fixed],
                ['SalePayments total', number_format($salePaymentsTotal, 2)],
                ['CashboxTxn from sales', number_format($txnInFromSales, 2)],
                ['Missing sale income', number_format($salePaymentsTotal - $txnInFromSales, 2)],
            ]);

            $this->newLine();
        }

        $this->info('=== Customer Balances ===');
        $this->recalcCustomers($dryRun);

        $this->info('=== Supplier Balances ===');
        $this->recalcSuppliers($dryRun);

        if ($dryRun) {
            $this->newLine();
            $this->warn('Dry run - no changes made. Remove --dry-run to apply fixes.');
            $this->info('Use --recalc to rebuild balance_after chain.');
        }

        return 0;
    }

    protected function recalcCustomers(bool $dryRun): void
    {
        $customers = \App\Models\Customer::all();
        $fixed = 0;

        foreach ($customers as $customer) {
            $transactions = $customer->transactions()
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $balance = (float) $customer->opening_balance;

            foreach ($transactions as $t) {
                $balance = $t->type === 'debit'
                    ? $balance + (float) $t->amount
                    : $balance - (float) $t->amount;

                if (!$dryRun && abs((float) $t->balance_after - $balance) > 0.001) {
                    DB::table('customer_transactions')
                        ->where('id', $t->id)
                        ->update(['balance_after' => round($balance, 2)]);
                }
            }

            if (abs((float) $customer->current_balance - $balance) > 0.001) {
                $this->line("  {$customer->name}: {$customer->current_balance} -> " . round($balance, 2));
                if (!$dryRun) {
                    $customer->current_balance = round($balance, 2);
                    $customer->saveQuietly();
                }
                $fixed++;
            }
        }

        $this->info("  Fixed: {$fixed} customers");
    }

    protected function recalcSuppliers(bool $dryRun): void
    {
        $suppliers = \App\Models\Supplier::all();
        $fixed = 0;

        foreach ($suppliers as $supplier) {
            $transactions = $supplier->transactions()
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $balance = (float) $supplier->opening_balance;

            foreach ($transactions as $t) {
                $balance = $t->type === 'debit'
                    ? $balance + (float) $t->amount
                    : $balance - (float) $t->amount;

                if (!$dryRun && abs((float) $t->balance_after - $balance) > 0.001) {
                    DB::table('supplier_transactions')
                        ->where('id', $t->id)
                        ->update(['balance_after' => round($balance, 2)]);
                }
            }

            if (abs((float) $supplier->current_balance - $balance) > 0.001) {
                $this->line("  {$supplier->name}: {$supplier->current_balance} -> " . round($balance, 2));
                if (!$dryRun) {
                    $supplier->current_balance = round($balance, 2);
                    $supplier->saveQuietly();
                }
                $fixed++;
            }
        }

        $this->info("  Fixed: {$fixed} suppliers");
    }
}
