<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'terminal_id',
        'total_cash_sales',
        'total_card_sales',
        'total_other_sales',
        'total_refunds',
        'total_expenses',
        'total_deposits',
        'total_withdrawals',
        'sales_count',
        'refunds_count',
        'opened_at',
        'closed_at',
        'status',
        'force_closed',
        'force_closed_by',
        'force_close_reason',
        'approved',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'total_cash_sales' => 'decimal:2',
        'total_card_sales' => 'decimal:2',
        'total_other_sales' => 'decimal:2',
        'total_refunds' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_deposits' => 'decimal:2',
        'total_withdrawals' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
        'force_closed' => 'boolean',
        'approved' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function forceClosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'force_closed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function shiftCashboxes(): HasMany
    {
        return $this->hasMany(ShiftCashbox::class);
    }

    public function cashboxes(): BelongsToMany
    {
        return $this->belongsToMany(Cashbox::class, 'shift_cashboxes')
            ->withPivot([
                'opening_balance',
                'closing_balance',
                'expected_balance',
                'difference',
                'total_in',
                'total_out',
            ])
            ->withTimestamps();
    }

    public function cashboxTransactions(): HasMany
    {
        return $this->hasMany(CashboxTransaction::class);
    }

    public function hasCashbox(int $cashboxId): bool
    {
        return $this->shiftCashboxes()->where('cashbox_id', $cashboxId)->exists();
    }

    public function getShiftCashbox(int $cashboxId): ?ShiftCashbox
    {
        return $this->shiftCashboxes()->where('cashbox_id', $cashboxId)->first();
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'open';
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status === 'closed';
    }

    public function getStatusArabicAttribute(): string
    {
        return match ($this->status) {
            'open' => 'مفتوح',
            'closed' => 'مغلق',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'success',
            'closed' => 'secondary',
            default => 'secondary',
        };
    }

    public function getTotalSalesAttribute(): float
    {
        return $this->total_cash_sales + $this->total_card_sales + $this->total_other_sales;
    }

    public function getTotalOpeningBalanceAttribute(): float
    {
        return $this->shiftCashboxes->sum('opening_balance');
    }

    public function getTotalExpectedBalanceAttribute(): float
    {
        return $this->shiftCashboxes->sum('expected_balance');
    }

    public function getTotalClosingBalanceAttribute(): float
    {
        return $this->shiftCashboxes->sum('closing_balance') ?? 0;
    }

    public function getTotalDifferenceAttribute(): float
    {
        return $this->shiftCashboxes->sum('difference');
    }

    public function recalculateTotals(): void
    {
        $saleIds = $this->sales()->where('status', 'completed')->pluck('id');

        $this->sales_count = $saleIds->count();

        if ($saleIds->isNotEmpty()) {
            $payments = SalePayment::whereIn('sale_id', $saleIds)
                ->with('paymentMethod')
                ->get();

            $this->total_cash_sales = $payments
                ->filter(fn($p) => $p->paymentMethod && $p->paymentMethod->code === 'cash')
                ->sum('amount') ?? 0;

            $this->total_card_sales = $payments
                ->filter(fn($p) => $p->paymentMethod && $p->paymentMethod->code === 'card')
                ->sum('amount') ?? 0;

            $this->total_other_sales = $payments
                ->filter(fn($p) => $p->paymentMethod && !in_array($p->paymentMethod->code, ['cash', 'card']))
                ->sum('amount') ?? 0;
        } else {
            $this->total_cash_sales = 0;
            $this->total_card_sales = 0;
            $this->total_other_sales = 0;
        }

        $this->total_refunds = $this->returns()
            ->where('status', 'completed')
            ->where('refund_method', 'cash')
            ->sum('total_amount') ?? 0;

        $this->refunds_count = $this->returns()->where('status', 'completed')->count();

        foreach ($this->shiftCashboxes as $sc) {
            $sc->recalculateTotals();
            $sc->save();
        }
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTerminal($query, string $terminalId)
    {
        return $query->where('terminal_id', $terminalId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('opened_at', today());
    }

    public static function hasOpenShift(?int $userId = null, ?string $terminalId = null): bool
    {
        $query = static::open();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($terminalId) {
            $query->where('terminal_id', $terminalId);
        }

        return $query->exists();
    }

    public static function getOpenShift(?int $userId = null, ?string $terminalId = null): ?self
    {
        $query = static::open();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($terminalId) {
            $query->where('terminal_id', $terminalId);
        }

        return $query->first();
    }
}
