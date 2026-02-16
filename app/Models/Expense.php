<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;
    use Syncable;

    protected $fillable = [
        'reference_number',
        'title',
        'description',
        'category_id',
        'amount',
        'payment_method_id',
        'cashbox_id',
        'shift_id',
        'expense_date',
        'created_by',
        'attachment',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateReferenceNumber(): string
    {
        $deviceTag = config('desktop.mode') ? 'D' : '';
        $prefix = 'EXP-' . $deviceTag . date('Ym') . '-';
        $lastExpense = static::where('reference_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastExpense) {
            $lastNumber = (int) substr($lastExpense->reference_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopeForCashbox($query, int $cashboxId)
    {
        return $query->where('cashbox_id', $cashboxId);
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('expense_date', today());
    }

    public function scopeDateBetween($query, $from, $to)
    {
        if ($from) {
            $query->whereDate('expense_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('expense_date', '<=', $to);
        }
        return $query;
    }
}
