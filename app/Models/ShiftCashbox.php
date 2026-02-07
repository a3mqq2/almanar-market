<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftCashbox extends Model
{
    protected $fillable = [
        'shift_id',
        'cashbox_id',
        'opening_balance',
        'closing_balance',
        'expected_balance',
        'difference',
        'total_in',
        'total_out',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_in' => 'decimal:2',
        'total_out' => 'decimal:2',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function calculateExpectedBalance(): float
    {
        return $this->opening_balance + $this->total_in - $this->total_out;
    }

    public function calculateDifference(): float
    {
        if ($this->closing_balance === null) {
            return 0;
        }
        return $this->closing_balance - $this->expected_balance;
    }

    public function recalculateTotals(): void
    {
        $shift = $this->shift;
        $cashboxId = $this->cashbox_id;

        $transactions = CashboxTransaction::where('shift_id', $shift->id)
            ->where('cashbox_id', $cashboxId)
            ->get();

        $this->total_in = $transactions->whereIn('type', ['in', 'transfer_in'])->sum('amount');
        $this->total_out = $transactions->whereIn('type', ['out', 'transfer_out'])->sum('amount');
        $this->expected_balance = $this->calculateExpectedBalance();
        $this->difference = $this->calculateDifference();
    }
}
