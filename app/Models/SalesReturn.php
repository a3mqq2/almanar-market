<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use HasFactory;
    use Syncable;

    protected $fillable = [
        'sale_id',
        'shift_id',
        'return_number',
        'return_date',
        'status',
        'subtotal',
        'total_amount',
        'refund_method',
        'cashbox_id',
        'reason',
        'reason_notes',
        'restore_stock',
        'customer_id',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'restore_stock' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusArabicAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'قيد الانتظار',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getReasonArabicAttribute(): string
    {
        return match ($this->reason) {
            'damaged' => 'تالف',
            'wrong_invoice' => 'خطأ في الفاتورة',
            'unsatisfied' => 'زبون غير راضٍ',
            'expired' => 'منتج منتهي',
            'other' => 'أخرى',
            default => $this->reason,
        };
    }

    public function getRefundMethodArabicAttribute(): string
    {
        return match ($this->refund_method) {
            'cash' => 'رد نقدي',
            'same_payment' => 'نفس طريقة الدفع',
            'store_credit' => 'رصيد للزبون',
            'deduct_credit' => 'خصم من حساب الزبون',
            default => $this->refund_method,
        };
    }

    public static function generateReturnNumber(): string
    {
        $prefix = 'RET-' . date('Ym') . '-';
        $lastReturn = static::where('return_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReturn) {
            $lastNumber = (int) substr($lastReturn->return_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total_amount');
        $this->total_amount = $this->subtotal;
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('return_date', today());
    }
}
