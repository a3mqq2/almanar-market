<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
        'multiplier',
        'sell_price',
        'cost_price',
        'is_base_unit',
    ];

    protected $casts = [
        'multiplier' => 'decimal:4',
        'sell_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_base_unit' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getCalculatedCostAttribute(): float
    {
        if ($this->is_base_unit) {
            return $this->cost_price ?? 0;
        }

        $baseUnit = $this->product->baseUnit;
        if ($baseUnit) {
            return ($baseUnit->cost_price ?? 0) * $this->multiplier;
        }

        return 0;
    }
}
