<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'requires_cashbox',
        'status',
        'sort_order',
        'cashbox_id',
    ];

    protected $casts = [
        'requires_cashbox' => 'boolean',
        'status' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public static function cash(): ?self
    {
        return static::getByCode('cash');
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }
}
