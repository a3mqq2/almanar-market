<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use Syncable;
    protected $fillable = [
        'product_id',
        'batch_id',
        'type',
        'reason',
        'quantity',
        'before_quantity',
        'after_quantity',
        'cost_price',
        'reference',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'before_quantity' => 'decimal:4',
        'after_quantity' => 'decimal:4',
        'cost_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeArabicAttribute(): string
    {
        return match($this->type) {
            'opening_balance' => 'رصيد افتتاحي',
            'purchase' => 'شراء',
            'sale' => 'بيع',
            'adjustment' => 'تعديل',
            'return' => 'مرتجع',
            'transfer' => 'تحويل',
            'damage' => 'تالف',
            'loss' => 'فقدان',
            default => $this->type,
        };
    }

    public function getDirectionAttribute(): string
    {
        return in_array($this->type, ['purchase', 'return', 'opening_balance']) || $this->quantity > 0 ? 'in' : 'out';
    }
}
