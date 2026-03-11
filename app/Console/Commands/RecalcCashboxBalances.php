<?php

namespace App\Console\Commands;

use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcCashboxBalances extends Command
{
    protected $signature = 'cashbox:recalc {--cashbox= : Specific cashbox ID}';

    protected $description = 'Recalculate balance_after for all cashbox transactions';

    public function handle(): int
    {
        $cashboxId = $this->option('cashbox');

        if ($cashboxId) {
            $cashboxes = Cashbox::where('id', $cashboxId)->get();
        } else {
            $cashboxes = Cashbox::all();
        }

        foreach ($cashboxes as $cashbox) {
            $this->info("Recalculating: {$cashbox->name} (ID: {$cashbox->id})");

            $transactions = CashboxTransaction::where('cashbox_id', $cashbox->id)
                ->orderBy('transaction_date')
                ->orderBy('created_at')
                ->orderBy('type')
                ->orderBy('amount')
                ->get();

            $balance = $cashbox->opening_balance;
            $fixed = 0;

            foreach ($transactions as $t) {
                if (in_array($t->type, ['in', 'transfer_in'])) {
                    $balance += $t->amount;
                } else {
                    $balance -= $t->amount;
                }

                if (abs($t->balance_after - $balance) > 0.001) {
                    DB::table('cashbox_transactions')
                        ->where('id', $t->id)
                        ->update(['balance_after' => $balance]);
                    $fixed++;
                }
            }

            if (abs($cashbox->current_balance - $balance) > 0.001) {
                $this->warn("  Balance mismatch: {$cashbox->current_balance} -> {$balance}");
                $cashbox->update(['current_balance' => $balance]);
            }

            $this->info("  Transactions: {$transactions->count()}, Fixed: {$fixed}");
        }

        $this->info('Done.');
        return 0;
    }
}
