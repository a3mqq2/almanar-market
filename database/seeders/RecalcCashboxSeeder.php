<?php

namespace Database\Seeders;

use App\Models\Cashbox;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecalcCashboxSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cashbox_transactions')
            ->whereIn('type', ['transfer_in', 'transfer_out'])
            ->delete();

        $cashboxes = Cashbox::all();

        foreach ($cashboxes as $cashbox) {
            $transactions = DB::table('cashbox_transactions')
                ->where('cashbox_id', $cashbox->id)
                ->orderBy('id')
                ->get();

            $balance = $cashbox->opening_balance;

            foreach ($transactions as $tx) {
                if (in_array($tx->type, ['in'])) {
                    $balance += $tx->amount;
                } elseif (in_array($tx->type, ['out'])) {
                    $balance -= $tx->amount;
                }

                DB::table('cashbox_transactions')
                    ->where('id', $tx->id)
                    ->update([
                        'balance_after' => $balance,
                        'related_cashbox_id' => null,
                        'related_transaction_id' => null,
                    ]);
            }

            $cashbox->update(['current_balance' => $balance]);

            $this->command->info("{$cashbox->name}: {$cashbox->opening_balance} → {$balance}");
        }
    }
}
