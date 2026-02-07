<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'quantity',
        'cost_price',
        'expiry_date',
        'notes',
        'type',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'cost_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public static function generateBatchNumber(): string
    {
        return 'BATCH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
