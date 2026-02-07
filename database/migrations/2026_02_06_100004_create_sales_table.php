<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('sale_date');
            $table->enum('status', ['draft', 'completed', 'cancelled', 'returned'])->default('draft');

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->enum('payment_status', ['paid', 'partial', 'credit'])->default('paid');
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);

            $table->boolean('is_suspended')->default(false);
            $table->text('notes')->nullable();

            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();

            $table->timestamps();

            $table->index(['customer_id', 'sale_date']);
            $table->index('status');
            $table->index('invoice_number');
            $table->index('is_suspended');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
