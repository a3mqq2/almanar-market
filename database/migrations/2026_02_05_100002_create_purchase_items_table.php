<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 2)->comment('سعر الوحدة المختارة');
            $table->decimal('unit_multiplier', 10, 4)->default(1)->comment('معامل التحويل للوحدة الأساسية');
            $table->decimal('base_quantity', 15, 4)->comment('الكمية بالوحدة الأساسية');
            $table->decimal('total_price', 15, 2);

            // Cost tracking
            $table->decimal('base_unit_cost', 15, 4)->comment('تكلفة الوحدة الأساسية');

            // Link to inventory batch created
            $table->foreignId('inventory_batch_id')->nullable()->constrained()->nullOnDelete();

            // Expiry date for this item
            $table->date('expiry_date')->nullable();

            $table->timestamps();

            $table->index(['purchase_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
