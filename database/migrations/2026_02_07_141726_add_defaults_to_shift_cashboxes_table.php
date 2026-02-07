<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shift_cashboxes')
            ->whereNull('total_in')
            ->update(['total_in' => 0]);

        DB::table('shift_cashboxes')
            ->whereNull('total_out')
            ->update(['total_out' => 0]);

        DB::table('shift_cashboxes')
            ->whereNull('difference')
            ->update(['difference' => 0]);

        Schema::table('shift_cashboxes', function (Blueprint $table) {
            $table->decimal('total_in', 15, 2)->default(0)->change();
            $table->decimal('total_out', 15, 2)->default(0)->change();
            $table->decimal('difference', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('shift_cashboxes', function (Blueprint $table) {
            $table->decimal('total_in', 15, 2)->nullable()->change();
            $table->decimal('total_out', 15, 2)->nullable()->change();
            $table->decimal('difference', 15, 2)->nullable()->change();
        });
    }
};
