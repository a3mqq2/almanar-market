<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $syncableTables = [
        'sales',
        'sale_items',
        'sale_payments',
        'shifts',
        'expenses',
        'stock_movements',
        'sales_returns',
        'sale_return_items',
        'cashbox_transactions',
        'customer_transactions',
        'supplier_transactions',
    ];

    public function up(): void
    {
        foreach ($this->syncableTables as $tableName) {

            Schema::table($tableName, function (Blueprint $table) {

                if (!Schema::hasColumn($table->getTable(), 'device_id')) {

                    $table->string('device_id', 36)->nullable()->after('id');
                    $table->string('local_uuid', 36)->nullable()->after('device_id');
                    $table->timestamp('synced_at')->nullable();
                    $table->unsignedInteger('sync_version')->default(1);

                    $table->index('device_id');
                    $table->index('local_uuid');
                    $table->index('synced_at');

                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->syncableTables as $tableName) {

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {

                $table->dropIndex($tableName.'_device_id_index');
                $table->dropIndex($tableName.'_local_uuid_index');
                $table->dropIndex($tableName.'_synced_at_index');

                $table->dropColumn([
                    'device_id',
                    'local_uuid',
                    'synced_at',
                    'sync_version'
                ]);
            });
        }
    }
};