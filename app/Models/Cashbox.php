<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cashbox extends Model
{
    use HasFactory;
    use Syncable;

    protected $fillable = [
        'name',
        'type',
        'opening_balance',
        'current_balance',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(CashboxTransaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getLastTransactionAttribute()
    {
        return $this->transactions()->latest('id')->first();
    }

    public function getStatusArabicAttribute(): string
    {
        return $this->status ? 'نشط' : 'غير نشط';
    }

    public function getTypeArabicAttribute(): string
    {
        return match($this->type) {
            'cash' => 'نقدي',
            'card' => 'بطاقة',
            'wallet' => 'محفظة',
            'bank' => 'مصرفي',
            default => 'نقدي',
        };
    }

    public function paymentMethod(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PaymentMethod::class);
    }

    public function recalculateBalance(): void
    {
        $lastTransaction = $this->lastTransaction;
        $this->current_balance = $lastTransaction
            ? $lastTransaction->balance_after
            : $this->opening_balance;
        $this->saveQuietly();
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
