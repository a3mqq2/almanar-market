<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('notes')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('stock_movements', 'reason')) {
                $table->string('reason')->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'reason']);
        });
    }
};
