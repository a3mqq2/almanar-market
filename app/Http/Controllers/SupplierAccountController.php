<?php

namespace App\Http\Controllers;

use App\Models\Cashbox;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\FinancialTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierAccountController extends Controller
{
    protected FinancialTransactionService $financialService;

    public function __construct(FinancialTransactionService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['transactions' => function ($query) {
            $query->latest('id')->take(10);
        }]);

        $stats = [
            'total_debit' => $supplier->transactions()->where('type', 'debit')->sum('amount'),
            'total_credit' => $supplier->transactions()->where('type', 'credit')->sum('amount'),
            'transactions_count' => $supplier->transactions()->count(),
        ];

        $cashboxes = Cashbox::active()->orderBy('name')->get();

        return view('suppliers.account', compact('supplier', 'stats', 'cashboxes'));
    }

    public function addDebit(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $transaction = $this->financialService->createSupplierDebit(
                $supplier,
                $validated['amount'],
                $validated['description'] ?? 'إضافة دين',
                null,
                null,
                $validated['transaction_date'] ?? now()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المبلغ بنجاح',
                'transaction' => $transaction,
                'new_balance' => $supplier->fresh()->current_balance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function addCredit(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'cashbox_id' => 'required|exists:cashboxes,id',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $result = $this->financialService->createSupplierPayment(
                $supplier,
                $validated['amount'],
                $validated['cashbox_id'],
                $validated['description'] ?? 'سداد دين',
                $validated['transaction_date'] ?? now()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم السداد بنجاح',
                'supplier_transaction' => $result['supplier_transaction'],
                'cashbox_transaction' => $result['cashbox_transaction'],
                'new_balance' => $supplier->fresh()->current_balance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function setOpeningBalance(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
        ]);

        if ($supplier->transactions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل الرصيد الافتتاحي بعد وجود حركات',
            ], 422);
        }

        $supplier->update([
            'opening_balance' => $validated['opening_balance'],
            'current_balance' => $validated['opening_balance'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الرصيد الافتتاحي بنجاح',
            'opening_balance' => $validated['opening_balance'],
        ]);
    }

    public function getLedger(Request $request, Supplier $supplier)
    {
        $query = $supplier->transactions()->with('creator', 'cashbox');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('id', 'desc')->paginate(20);

        $data = $transactions->map(function ($t) {
            return [
                'id' => $t->id,
                'type' => $t->type,
                'type_arabic' => $t->type_arabic,
                'amount' => $t->amount,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'reference_type' => $t->reference_type,
                'reference_type_arabic' => $t->reference_type_arabic,
                'reference_id' => $t->reference_id,
                'cashbox_name' => $t->cashbox?->name,
                'transaction_date' => $t->transaction_date->format('Y-m-d'),
                'created_by' => $t->creator?->name ?? '-',
                'created_at' => $t->created_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
            'summary' => [
                'opening_balance' => $supplier->opening_balance,
                'current_balance' => $supplier->current_balance,
                'total_debit' => $supplier->transactions()->where('type', 'debit')->sum('amount'),
                'total_credit' => $supplier->transactions()->where('type', 'credit')->sum('amount'),
            ],
        ]);
    }

    public function getAccountSummary(Supplier $supplier)
    {
        return response()->json([
            'success' => true,
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'opening_balance' => $supplier->opening_balance,
                'current_balance' => $supplier->current_balance,
            ],
            'stats' => [
                'total_debit' => $supplier->transactions()->where('type', 'debit')->sum('amount'),
                'total_credit' => $supplier->transactions()->where('type', 'credit')->sum('amount'),
                'transactions_count' => $supplier->transactions()->count(),
                'last_transaction' => $supplier->transactions()->latest('id')->first()?->transaction_date?->format('Y-m-d'),
            ],
        ]);
    }

    public function print(Request $request, Supplier $supplier)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = $supplier->transactions();

        if ($dateFrom) {
            $openingBalance = $supplier->transactions()
                ->where('transaction_date', '<', $dateFrom)
                ->orderBy('id', 'desc')
                ->value('balance_after') ?? $supplier->opening_balance;

            $query->whereDate('transaction_date', '>=', $dateFrom);
        } else {
            $openingBalance = $supplier->opening_balance;
        }

        if ($dateTo) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        $transactions = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get();

        $totalDebit = $transactions->where('type', 'debit')->sum('amount');
        $totalCredit = $transactions->where('type', 'credit')->sum('amount');
        $closingBalance = $transactions->last()?->balance_after ?? $openingBalance;

        return view('suppliers.print', compact(
            'supplier',
            'transactions',
            'openingBalance',
            'closingBalance',
            'totalDebit',
            'totalCredit',
            'dateFrom',
            'dateTo'
        ));
    }
}
