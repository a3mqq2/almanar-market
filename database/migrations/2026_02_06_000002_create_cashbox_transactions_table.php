<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbox_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashbox_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'transfer_in', 'transfer_out']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('related_cashbox_id')->nullable();
            $table->unsignedBigInteger('related_transaction_id')->nullable();
            $table->date('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cashbox_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('related_cashbox_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbox_transactions');
    }
};
