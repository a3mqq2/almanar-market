<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcCustomerBalances extends Command
{
    protected $signature = 'customers:recalc {--customer= : Specific customer ID}';

    protected $description = 'Recalculate balance_after for all customer transactions';

    public function handle(): int
    {
        $customerId = $this->option('customer');

        if ($customerId) {
            $customers = Customer::where('id', $customerId)->get();
        } else {
            $customers = Customer::all();
        }

        $totalFixed = 0;

        foreach ($customers as $customer) {
            $transactions = CustomerTransaction::where('customer_id', $customer->id)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            if ($transactions->isEmpty()) continue;

            $balance = (float) $customer->opening_balance;
            $fixed = 0;

            foreach ($transactions as $t) {
                $balance = $t->type === 'debit'
                    ? $balance + (float) $t->amount
                    : $balance - (float) $t->amount;

                if (abs((float) $t->balance_after - $balance) > 0.001) {
                    DB::table('customer_transactions')
                        ->where('id', $t->id)
                        ->update(['balance_after' => round($balance, 2)]);
                    $fixed++;
                }
            }

            if ($fixed > 0 || abs((float) $customer->current_balance - $balance) > 0.001) {
                $this->info("{$customer->name} (ID: {$customer->id}): {$transactions->count()} transactions, {$fixed} fixed");
            }

            if (abs((float) $customer->current_balance - $balance) > 0.001) {
                $this->warn("  Balance: {$customer->current_balance} → {$balance}");
                $customer->current_balance = round($balance, 2);
                $customer->saveQuietly();
            }

            $totalFixed += $fixed;
        }

        if ($totalFixed === 0) {
            $this->info('All customer balances are correct.');
        } else {
            $this->info("Fixed {$totalFixed} transactions.");
        }

        return 0;
    }
}
