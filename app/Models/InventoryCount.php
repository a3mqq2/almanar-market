<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class InventoryCount extends Model
{
    use Syncable;
    protected $fillable = [
        'reference_number',
        'count_type',
        'status',
        'counted_by',
        'approved_by',
        'started_at',
        'completed_at',
        'approved_at',
        'cancelled_at',
        'cancel_reason',
        'total_items',
        'counted_items',
        'variance_items',
        'total_system_value',
        'total_counted_value',
        'variance_value',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_system_value' => 'decimal:2',
        'total_counted_value' => 'decimal:2',
        'variance_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (InventoryCount $count) {
            if ($count->getOriginal('status') == 'approved') {
                throw new \Exception('لا يمكن تعديل جرد معتمد');
            }
        });

        static::deleting(function (InventoryCount $count) {
            if ($count->status == 'approved') {
                throw new \Exception('لا يمكن حذف جرد معتمد');
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryCountItem::class);
    }

    public function countedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'in_progress']);
    }

    public function getStatusArabicAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'in_progress' => 'جاري الجرد',
            'completed' => 'مكتمل',
            'approved' => 'معتمد',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'in_progress' => 'warning',
            'completed' => 'info',
            'approved' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getCountTypeArabicAttribute(): string
    {
        return match ($this->count_type) {
            'full' => 'جرد شامل',
            'partial' => 'جرد جزئي',
            default => $this->count_type,
        };
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_items == 0) return 0;
        return (int) round(($this->counted_items / $this->total_items) * 100);
    }

    public function canStart(): bool
    {
        return $this->status == 'draft';
    }

    public function canCount(): bool
    {
        return $this->status == 'in_progress';
    }

    public function canComplete(): bool
    {
        return $this->status == 'in_progress' && $this->counted_items == $this->total_items;
    }

    public function canApprove(): bool
    {
        return $this->status == 'completed';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'in_progress', 'completed']);
    }

    public static function generateReferenceNumber(): string
    {
        $deviceTag = config('desktop.mode') ? 'D' : '';
        $prefix = 'INV-' . $deviceTag . date('Ym') . '-';
        $lastCount = static::where('reference_number', 'like', $prefix . '____')
            ->orderBy('reference_number', 'desc')
            ->first();

        if ($lastCount) {
            $lastNumber = (int) substr($lastCount->reference_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function updateCounters(): void
    {
        $this->total_items = $this->items()->count();
        $this->counted_items = $this->items()->whereNotNull('counted_qty')->count();
        $this->variance_items = $this->items()
            ->whereNotNull('counted_qty')
            ->where('difference', '!=', 0)
            ->count();

        $this->total_system_value = $this->items()->sum(DB::raw('system_qty * system_cost'));
        $this->total_counted_value = $this->items()
            ->whereNotNull('counted_qty')
            ->sum(DB::raw('counted_qty * system_cost'));
        $this->variance_value = $this->items()->sum('variance_value');

        $this->save();
    }
}
