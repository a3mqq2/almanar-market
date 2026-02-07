<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained('inventory_counts')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->nullable()->constrained('units');

            $table->decimal('system_qty', 15, 4);
            $table->decimal('system_cost', 15, 2);

            $table->decimal('counted_qty', 15, 4)->nullable();
            $table->decimal('difference', 15, 4)->default(0);
            $table->decimal('variance_value', 15, 2)->default(0);

            $table->timestamp('counted_at')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['inventory_count_id', 'product_id']);
            $table->index('counted_at');
            $table->unique(['inventory_count_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
    }
};
