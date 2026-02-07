<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->enum('count_type', ['full', 'partial'])->default('full');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'approved', 'cancelled'])->default('draft');

            $table->foreignId('counted_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('counted_items')->default(0);
            $table->unsignedInteger('variance_items')->default(0);

            $table->decimal('total_system_value', 15, 2)->default(0);
            $table->decimal('total_counted_value', 15, 2)->default(0);
            $table->decimal('variance_value', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_counts');
    }
};
