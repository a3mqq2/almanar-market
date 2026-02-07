<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'total_cash_sales',
            'total_card_sales',
            'total_other_sales',
            'total_refunds',
            'total_expenses',
            'total_deposits',
            'total_withdrawals',
        ];

        foreach ($columns as $column) {
            DB::table('shifts')
                ->whereNull($column)
                ->update([$column => 0]);
        }

        DB::table('shifts')
            ->whereNull('sales_count')
            ->update(['sales_count' => 0]);

        DB::table('shifts')
            ->whereNull('refunds_count')
            ->update(['refunds_count' => 0]);
    }

    public function down(): void
    {
    }
};
