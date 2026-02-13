<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountItem extends Model
{
    use Syncable;
    protected $fillable = [
        'inventory_count_id',
        'product_id',
        'unit_id',
        'system_qty',
        'system_cost',
        'counted_qty',
        'difference',
        'variance_value',
        'counted_at',
        'counted_by',
        'notes',
    ];

    protected $casts = [
        'system_qty' => 'decimal:4',
        'system_cost' => 'decimal:2',
        'counted_qty' => 'decimal:4',
        'difference' => 'decimal:4',
        'variance_value' => 'decimal:2',
        'counted_at' => 'datetime',
    ];

    public function inventoryCount(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function countedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function calculateVariance(): void
    {
        if ($this->counted_qty != null) {
            $this->difference = $this->counted_qty - $this->system_qty;
            $this->variance_value = $this->difference * $this->system_cost;
        } else {
            $this->difference = 0;
            $this->variance_value = 0;
        }
    }

    public function getVarianceStatusAttribute(): string
    {
        if ($this->counted_qty == null) return 'pending';
        if ($this->difference > 0) return 'surplus';
        if ($this->difference < 0) return 'shortage';
        return 'match';
    }

    public function getVarianceStatusColorAttribute(): string
    {
        return match ($this->variance_status) {
            'surplus' => 'success',
            'shortage' => 'danger',
            'match' => 'secondary',
            'pending' => 'warning',
            default => 'secondary',
        };
    }

    public function getVarianceStatusArabicAttribute(): string
    {
        return match ($this->variance_status) {
            'surplus' => 'فائض',
            'shortage' => 'عجز',
            'match' => 'مطابق',
            'pending' => 'لم يجرد',
            default => $this->variance_status,
        };
    }

    public function getIsCountedAttribute(): bool
    {
        return $this->counted_qty != null;
    }
}
