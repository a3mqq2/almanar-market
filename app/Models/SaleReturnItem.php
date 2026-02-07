<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_return_id',
        'sale_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_amount',
        'cost_at_sale',
        'base_quantity',
        'stock_restored',
        'inventory_batch_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cost_at_sale' => 'decimal:4',
        'base_quantity' => 'decimal:4',
        'stock_restored' => 'boolean',
    ];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    public function getUnitNameAttribute(): string
    {
        return $this->saleItem?->unitName ?? '-';
    }

    public function getCostTotalAttribute(): float
    {
        return $this->cost_at_sale * $this->base_quantity;
    }

    public function calculateTotals(): void
    {
        $this->total_amount = $this->quantity * $this->unit_price;
    }
}
