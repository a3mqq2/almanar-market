<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashbox_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('cashbox_transactions', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('cashbox_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('cashbox_transactions', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->nullable()->after('shift_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cashbox_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('cashbox_transactions', 'shift_id')) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            }
            if (Schema::hasColumn('cashbox_transactions', 'payment_method_id')) {
                $table->dropForeign(['payment_method_id']);
                $table->dropColumn('payment_method_id');
            }
        });
    }
};
