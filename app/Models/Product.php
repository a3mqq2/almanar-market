<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'name',
        'barcode',
        'image',
        'status',
    ];

    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function baseUnit(): HasOne
    {
        return $this->hasOne(ProductUnit::class)->where('is_base_unit', true);
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function activeBarcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class)->where('is_active', true);
    }

    public function getTotalStockAttribute(): float
    {
        return $this->inventoryBatches()->sum('quantity');
    }

    public function getAllBarcodesAttribute(): array
    {
        $barcodes = [];
        if ($this->barcode) {
            $barcodes[] = $this->barcode;
        }
        foreach ($this->activeBarcodes as $barcode) {
            $barcodes[] = $barcode->barcode;
        }
        return $barcodes;
    }
}
