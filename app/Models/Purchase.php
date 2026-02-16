<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use Syncable;
    protected $fillable = [
        'supplier_id',
        'invoice_number',
        'purchase_date',
        'payment_type',
        'status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total',
        'paid_amount',
        'remaining_amount',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // Accessors
    public function getStatusArabicAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'approved' => 'معتمدة',
            'cancelled' => 'ملغاة',
            'returned' => 'مرتجعة',
            default => $this->status,
        };
    }

    public function getPaymentTypeArabicAttribute(): string
    {
        return match ($this->payment_type) {
            'cash' => 'نقدي',
            'bank' => 'تحويل بنكي',
            'credit' => 'آجل',
            default => $this->payment_type,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'warning',
            'approved' => 'success',
            'cancelled' => 'danger',
            'returned' => 'info',
            default => 'secondary',
        };
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Methods
    public function canBeEdited(): bool
    {
        return $this->status == 'draft';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'approved']);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total_price');

        if ($this->discount_type == 'percentage') {
            $this->discount_amount = ($this->subtotal * $this->discount_value) / 100;
        } else {
            $this->discount_amount = $this->discount_value;
        }

        $afterDiscount = $this->subtotal - $this->discount_amount;
        $this->tax_amount = ($afterDiscount * $this->tax_rate) / 100;
        $this->total = $afterDiscount + $this->tax_amount;
        $this->remaining_amount = $this->total - $this->paid_amount;
    }

    public static function generateInvoiceNumber(): string
    {
        $deviceTag = config('desktop.mode') ? 'D' : '';
        $prefix = 'PUR-' . $deviceTag . date('Ym');
        $lastPurchase = static::where('id', '>', 0)
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPurchase ? ((int) substr($lastPurchase->id, -4)) + 1 : 1;
        return $prefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
