<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\FinancialTransactionService;
use Illuminate\Console\Command;

class RecalculateCustomerBalances extends Command
{
    protected $signature = 'customers:recalculate-balances {customer? : Customer ID (optional, recalculates all if omitted)}';

    protected $description = 'Recalculate customer transaction balances in chronological order';

    public function handle(FinancialTransactionService $service): int
    {
        $customerId = $this->argument('customer');

        if ($customerId) {
            $customer = Customer::find($customerId);

            if (!$customer) {
                $this->error("Customer #{$customerId} not found.");
                return self::FAILURE;
            }

            $customers = collect([$customer]);
        } else {
            $customers = Customer::whereHas('transactions')->get();
        }

        if ($customers->isEmpty()) {
            $this->info('No customers with transactions found.');
            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($customers as $customer) {
            $oldBalance = (float) $customer->current_balance;
            $service->recalculateCustomerBalances($customer);
            $customer->refresh();
            $newBalance = (float) $customer->current_balance;

            if (abs($oldBalance - $newBalance) > 0.001) {
                $this->line("<comment>Fixed:</comment> {$customer->name} (#{$customer->id}) {$oldBalance} → {$newBalance}");
                $fixed++;
            } else {
                $this->line("<info>OK:</info> {$customer->name} (#{$customer->id}) {$newBalance}");
            }
        }

        $this->newLine();
        $this->info("Done. {$fixed} customer(s) fixed out of {$customers->count()}.");

        return self::SUCCESS;
    }
}
