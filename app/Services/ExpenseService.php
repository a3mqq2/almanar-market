<?php

namespace App\Services;

use App\Models\Cashbox;
use App\Models\Expense;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    protected FinancialTransactionService $financialService;

    public function __construct(FinancialTransactionService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $cashbox = Cashbox::findOrFail($data['cashbox_id']);

            if ($cashbox->current_balance < $data['amount']) {
                throw new \Exception('رصيد الخزينة غير كافٍ. الرصيد الحالي: ' . number_format($cashbox->current_balance, 2));
            }

            $expense = Expense::create([
                'reference_number' => Expense::generateReferenceNumber(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'],
                'amount' => $data['amount'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'cashbox_id' => $data['cashbox_id'],
                'shift_id' => $data['shift_id'] ?? null,
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'created_by' => Auth::id(),
                'attachment' => $data['attachment'] ?? null,
            ]);

            $this->financialService->createCashboxOut(
                $cashbox,
                $expense->amount,
                "مصروف: {$expense->title} - #{$expense->reference_number}",
                Expense::class,
                $expense->id,
                $expense->expense_date,
                $expense->shift_id,
                $expense->payment_method_id
            );

            if ($expense->shift_id) {
                $this->updateShiftExpenses($expense->shift_id);
            }

            return $expense;
        });
    }

    protected function updateShiftExpenses(int $shiftId): void
    {
        $shift = Shift::find($shiftId);
        if (!$shift) return;

        $totalExpenses = Expense::where('shift_id', $shiftId)->sum('amount');
        $shift->update(['total_expenses' => $totalExpenses]);
    }

    public function getExpensesByShift(Shift $shift): \Illuminate\Database\Eloquent\Collection
    {
        return Expense::where('shift_id', $shift->id)
            ->with(['category', 'cashbox', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getExpensesByCashbox(Cashbox $cashbox, ?string $from = null, ?string $to = null): \Illuminate\Database\Eloquent\Collection
    {
        return Expense::where('cashbox_id', $cashbox->id)
            ->when($from, fn($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('expense_date', '<=', $to))
            ->with(['category', 'shift', 'creator'])
            ->orderBy('expense_date', 'desc')
            ->get();
    }

    public function getDailyReport(string $date): array
    {
        $expenses = Expense::whereDate('expense_date', $date)
            ->with(['category', 'cashbox'])
            ->get();

        $byCategory = $expenses->groupBy('category_id')->map(function ($items) {
            return [
                'category' => $items->first()->category->name,
                'count' => $items->count(),
                'total' => $items->sum('amount'),
            ];
        });

        $byCashbox = $expenses->groupBy('cashbox_id')->map(function ($items) {
            return [
                'cashbox' => $items->first()->cashbox->name,
                'count' => $items->count(),
                'total' => $items->sum('amount'),
            ];
        });

        return [
            'date' => $date,
            'total_count' => $expenses->count(),
            'total_amount' => $expenses->sum('amount'),
            'by_category' => $byCategory->values(),
            'by_cashbox' => $byCashbox->values(),
            'expenses' => $expenses,
        ];
    }

    public function getCategoryReport(?string $from = null, ?string $to = null): array
    {
        $query = Expense::with('category');

        if ($from) {
            $query->whereDate('expense_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('expense_date', '<=', $to);
        }

        $expenses = $query->get();

        $byCategory = $expenses->groupBy('category_id')->map(function ($items) {
            return [
                'category_id' => $items->first()->category_id,
                'category' => $items->first()->category->name,
                'count' => $items->count(),
                'total' => $items->sum('amount'),
                'percentage' => 0,
            ];
        });

        $totalAmount = $expenses->sum('amount');

        if ($totalAmount > 0) {
            $byCategory = $byCategory->map(function ($item) use ($totalAmount) {
                $item['percentage'] = round(($item['total'] / $totalAmount) * 100, 2);
                return $item;
            });
        }

        return [
            'from' => $from,
            'to' => $to,
            'total_count' => $expenses->count(),
            'total_amount' => $totalAmount,
            'by_category' => $byCategory->sortByDesc('total')->values(),
        ];
    }
}
