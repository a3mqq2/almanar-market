<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashboxTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashbox_id',
        'shift_id',
        'payment_method_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'related_cashbox_id',
        'related_transaction_id',
        'transaction_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getReferenceTypeArabicAttribute(): string
    {
        return match ($this->reference_type) {
            Purchase::class, 'App\\Models\\Purchase' => 'فاتورة مشتريات',
            Expense::class, 'App\\Models\\Expense', 'expense' => 'مصروف',
            'supplier_payment' => 'سداد مورد',
            'sale' => 'مبيعات',
            default => $this->reference_type ?? '-',
        };
    }

    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            if (!$transaction->transaction_date) {
                $transaction->transaction_date = now();
            }
        });

        static::deleting(function ($transaction) {
            return false;
        });

        static::updating(function ($transaction) {
            return false;
        });
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function relatedCashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class, 'related_cashbox_id');
    }

    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(CashboxTransaction::class, 'related_transaction_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function getTypeArabicAttribute(): string
    {
        return match ($this->type) {
            'in' => 'إيداع',
            'out' => 'سحب',
            'transfer_in' => 'تحويل وارد',
            'transfer_out' => 'تحويل صادر',
            default => $this->type,
        };
    }

    public function getIsInAttribute(): bool
    {
        return in_array($this->type, ['in', 'transfer_in']);
    }

    public function getIsOutAttribute(): bool
    {
        return in_array($this->type, ['out', 'transfer_out']);
    }

    public function getIsTransferAttribute(): bool
    {
        return in_array($this->type, ['transfer_in', 'transfer_out']);
    }
}
