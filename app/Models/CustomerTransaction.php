<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'cashbox_id',
        'transaction_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getReferenceTypeArabicAttribute(): string
    {
        return match ($this->reference_type) {
            Sale::class, 'App\\Models\\Sale' => 'فاتورة مبيعات',
            'customer_payment' => 'سداد زبون',
            'opening_balance' => 'رصيد افتتاحي',
            default => $this->reference_type ?? '-',
        };
    }

    public function getTypeArabicAttribute(): string
    {
        return match ($this->type) {
            'debit' => 'مدين',
            'credit' => 'دائن',
            default => $this->type,
        };
    }

    public function getIsDebitAttribute(): bool
    {
        return $this->type === 'debit';
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->type === 'credit';
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
}
