<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->onDelete('set null');
                $table->enum('type', ['opening_balance', 'purchase', 'sale', 'adjustment', 'return', 'transfer']);
                $table->decimal('quantity', 15, 4);
                $table->decimal('before_quantity', 15, 4)->default(0);
                $table->decimal('after_quantity', 15, 4)->default(0);
                $table->decimal('cost_price', 15, 2)->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
