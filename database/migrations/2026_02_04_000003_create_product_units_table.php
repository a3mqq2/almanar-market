<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
                $table->decimal('multiplier', 10, 4)->default(1);
                $table->decimal('sell_price', 15, 2)->default(0);
                $table->decimal('cost_price', 15, 2)->nullable();
                $table->boolean('is_base_unit')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
