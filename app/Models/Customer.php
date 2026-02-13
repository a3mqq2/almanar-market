<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;
    use Syncable;

    protected $fillable = [
        'name',
        'phone',
        'opening_balance',
        'current_balance',
        'credit_limit',
        'allow_credit',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'allow_credit' => 'boolean',
        'status' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getLastTransactionAttribute()
    {
        return $this->transactions()->latest('id')->first();
    }

    public function getAvailableCreditAttribute(): float
    {
        return max(0, $this->credit_limit - $this->current_balance);
    }

    public function getStatusArabicAttribute(): string
    {
        return $this->status ? 'نشط' : 'معطل';
    }

    public function recalculateBalance(): void
    {
        $lastTransaction = $this->lastTransaction;
        $this->current_balance = $lastTransaction ? $lastTransaction->balance_after : $this->opening_balance;
        $this->saveQuietly();
    }

    public function canTakeCredit(float $amount): bool
    {
        if (!$this->allow_credit) {
            return false;
        }
        return ($this->current_balance + $amount) <= $this->credit_limit;
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeAllowsCredit($query)
    {
        return $query->where('allow_credit', true);
    }
}
