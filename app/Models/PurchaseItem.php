<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use Syncable;
    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_unit_id',
        'quantity',
        'unit_price',
        'unit_multiplier',
        'base_quantity',
        'total_price',
        'base_unit_cost',
        'inventory_batch_id',
        'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'unit_multiplier' => 'decimal:4',
        'base_quantity' => 'decimal:4',
        'total_price' => 'decimal:2',
        'base_unit_cost' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    // Methods
    public function calculateTotals(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->base_quantity = $this->quantity * $this->unit_multiplier;
        $this->base_unit_cost = $this->base_quantity > 0
            ? $this->total_price / $this->base_quantity
            : 0;
    }

    public function getUnitNameAttribute(): string
    {
        return $this->productUnit?->unit?->name ?? $this->product?->baseUnit?->unit?->name ?? '-';
    }
}
