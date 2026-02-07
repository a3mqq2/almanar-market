<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payment_methods', 'cashbox_id')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->foreignId('cashbox_id')->nullable()->after('sort_order')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payment_methods', 'cashbox_id')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropForeign(['cashbox_id']);
                $table->dropColumn('cashbox_id');
            });
        }
    }
};
