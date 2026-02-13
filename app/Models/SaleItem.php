<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    use HasFactory;
    use Syncable;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_unit_id',
        'barcode_label',
        'quantity',
        'unit_price',
        'unit_multiplier',
        'base_quantity',
        'cost_at_sale',
        'total_price',
        'inventory_batch_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'unit_multiplier' => 'decimal:4',
        'base_quantity' => 'decimal:4',
        'cost_at_sale' => 'decimal:4',
        'total_price' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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

    public function returnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function getReturnedQuantityAttribute(): float
    {
        return $this->returnItems()
            ->whereHas('salesReturn', fn($q) => $q->where('status', 'completed'))
            ->sum('quantity');
    }

    public function getReturnableQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }

    public function getUnitNameAttribute(): string
    {
        return $this->productUnit?->unit?->name ?? $this->product?->baseUnit?->unit?->name ?? '-';
    }

    public function getProfitAttribute(): float
    {
        return $this->total_price - ($this->cost_at_sale * $this->base_quantity);
    }

    public function calculateTotals(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->base_quantity = $this->quantity * $this->unit_multiplier;
    }
}
