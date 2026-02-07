<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('restrict');
            $table->string('return_number')->unique();
            $table->date('return_date');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('refund_method', ['cash', 'same_payment', 'store_credit', 'deduct_credit'])->default('cash');
            $table->foreignId('cashbox_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('reason', ['damaged', 'wrong_invoice', 'unsatisfied', 'expired', 'other'])->default('other');
            $table->text('reason_notes')->nullable();
            $table->boolean('restore_stock')->default(true);
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'status']);
            $table->index('return_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
