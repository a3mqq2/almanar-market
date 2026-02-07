<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_batches')) {
            Schema::create('inventory_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('batch_number')->nullable();
                $table->decimal('quantity', 15, 4)->default(0);
                $table->decimal('cost_price', 15, 2)->default(0);
                $table->date('expiry_date')->nullable();
                $table->text('notes')->nullable();
                $table->enum('type', ['opening_balance', 'purchase', 'adjustment', 'return'])->default('purchase');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
