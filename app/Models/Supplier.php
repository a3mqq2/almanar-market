<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;
    use Syncable;

    protected $fillable = [
        'name',
        'phone',
        'status',
        'opening_balance',
        'current_balance',
    ];

    protected $casts = [
        'status' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function getLastTransactionAttribute()
    {
        return $this->transactions()->latest('id')->first();
    }

    public function recalculateBalance(): void
    {
        $lastTransaction = $this->lastTransaction;
        $this->current_balance = $lastTransaction ? $lastTransaction->balance_after : $this->opening_balance;
        $this->saveQuietly();
    }
}
