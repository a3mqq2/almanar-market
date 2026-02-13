<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;
    use Syncable;

    protected $fillable = [
        'customer_id',
        'shift_id',
        'invoice_number',
        'sale_date',
        'status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total',
        'payment_status',
        'paid_amount',
        'credit_amount',
        'is_suspended',
        'notes',
        'cashier_id',
        'created_by',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'is_suspended' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function getStatusArabicAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            'returned' => 'مرتجعة',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            'returned' => 'info',
            default => 'secondary',
        };
    }

    public function getPaymentStatusArabicAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'مدفوعة',
            'partial' => 'مدفوعة جزئياً',
            'credit' => 'آجل',
            default => $this->payment_status,
        };
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status == 'paid';
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->payment_status == 'credit';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'completed']);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total_price');

        if ($this->discount_type == 'percentage') {
            $this->discount_amount = ($this->subtotal * $this->discount_value) / 100;
        } else {
            $this->discount_amount = $this->discount_value ?? 0;
        }

        $afterDiscount = $this->subtotal - $this->discount_amount;
        $this->tax_amount = ($afterDiscount * $this->tax_rate) / 100;
        $this->total = $afterDiscount + $this->tax_amount;
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'SAL-' . date('Ym') . '-';
        $lastSale = static::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeSuspended($query)
    {
        return $query->where('is_suspended', true)->where('status', 'draft');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    public function getTotalReturnedAttribute(): float
    {
        return $this->returns()->where('status', 'completed')->sum('total_amount');
    }

    public function getHasReturnsAttribute(): bool
    {
        return $this->returns()->where('status', 'completed')->exists();
    }

    public function canBeReturned(): bool
    {
        return $this->status == 'completed' && $this->total > $this->total_returned;
    }
}
