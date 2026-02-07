<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('expense_categories');
            $table->decimal('amount', 15, 2);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods');
            $table->foreignId('cashbox_id')->constrained('cashboxes');
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->date('expense_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();

            $table->index(['expense_date', 'status']);
            $table->index('category_id');
            $table->index('cashbox_id');
            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
