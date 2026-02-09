<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() == 'sqlite') {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('cashbox_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() == 'sqlite') {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('cashbox_id')->nullable(false)->change();
        });
    }
};
