<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $syncableTables = [
        'users',
        'units',
        'suppliers',
        'customers',
        'cashboxes',
        'payment_methods',
        'expense_categories',
        'products',
        'product_units',
        'product_barcodes',
        'inventory_batches',
        'purchases',
        'purchase_items',
        'shifts',
        'shift_cashboxes',
        'sales',
        'sale_items',
        'sale_payments',
        'sales_returns',
        'sale_return_items',
        'expenses',
        'stock_movements',
        'cashbox_transactions',
        'customer_transactions',
        'supplier_transactions',
        'inventory_counts',
        'inventory_count_items',
        'user_activity_logs',
    ];

    public function up(): void
    {
        foreach ($this->syncableTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'synced_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('device_id', 36)->nullable();
                    $table->string('local_uuid', 36)->nullable();
                    $table->timestamp('synced_at')->nullable();
                    $table->unsignedInteger('sync_version')->default(1);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->syncableTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'synced_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn(['device_id', 'local_uuid', 'synced_at', 'sync_version']);
                });
            }
        }
    }
};
