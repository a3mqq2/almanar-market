<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_cashboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashbox_id')->constrained()->cascadeOnDelete();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->nullable();
            $table->decimal('expected_balance', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->decimal('total_in', 15, 2)->default(0);
            $table->decimal('total_out', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['shift_id', 'cashbox_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_cashboxes');
    }
};
