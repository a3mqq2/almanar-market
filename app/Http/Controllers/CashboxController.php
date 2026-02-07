<?php

namespace App\Http\Controllers;

use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashboxController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getCashboxesData($request);
        }

        $stats = [
            'total' => Cashbox::count(),
            'active' => Cashbox::active()->count(),
            'total_balance' => Cashbox::active()->sum('current_balance'),
        ];

        $paymentMethods = PaymentMethod::active()->orderBy('sort_order')->get();

        return view('cashboxes.index', compact('stats', 'paymentMethods'));
    }

    protected function getCashboxesData(Request $request)
    {
        $query = Cashbox::with('paymentMethod');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'current_balance', 'status', 'type', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        $perPage = $request->get('per_page', 15);
        $cashboxes = $query->paginate($perPage);

        $data = $cashboxes->map(function ($cashbox) {
            return [
                'id' => $cashbox->id,
                'name' => $cashbox->name,
                'type' => $cashbox->type,
                'type_arabic' => $cashbox->type_arabic,
                'current_balance' => $cashbox->current_balance,
                'status' => $cashbox->status,
                'status_arabic' => $cashbox->status_arabic,
                'linked_payment_method' => $cashbox->paymentMethod ? [
                    'id' => $cashbox->paymentMethod->id,
                    'name' => $cashbox->paymentMethod->name,
                ] : null,
                'created_at' => $cashbox->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $cashboxes->currentPage(),
                'last_page' => $cashboxes->lastPage(),
                'per_page' => $cashboxes->perPage(),
                'total' => $cashboxes->total(),
                'from' => $cashboxes->firstItem(),
                'to' => $cashboxes->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cashboxes,name',
            'type' => 'nullable|in:cash,card,wallet,bank',
            'opening_balance' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        $openingBalance = $validated['opening_balance'] ?? 0;

        DB::beginTransaction();

        try {
            $cashbox = Cashbox::create([
                'name' => $validated['name'],
                'type' => $validated['type'] ?? 'cash',
                'opening_balance' => $openingBalance,
                'current_balance' => $openingBalance,
                'status' => $validated['status'] ?? true,
                'created_by' => Auth::id(),
            ]);

            // Link payment method to this cashbox
            if (!empty($validated['payment_method_id'])) {
                PaymentMethod::where('id', $validated['payment_method_id'])
                    ->update(['cashbox_id' => $cashbox->id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الخزينة بنجاح',
                'cashbox' => $cashbox,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Cashbox $cashbox)
    {
        $cashbox->load(['transactions' => function ($query) {
            $query->latest('id')->take(10);
        }]);

        $stats = [
            'total_in' => $cashbox->transactions()
                ->whereIn('type', ['in', 'transfer_in'])
                ->sum('amount'),
            'total_out' => $cashbox->transactions()
                ->whereIn('type', ['out', 'transfer_out'])
                ->sum('amount'),
            'transactions_count' => $cashbox->transactions()->count(),
        ];

        $otherCashboxes = Cashbox::active()
            ->where('id', '!=', $cashbox->id)
            ->orderBy('name')
            ->get();

        return view('cashboxes.show', compact('cashbox', 'stats', 'otherCashboxes'));
    }

    public function update(Request $request, Cashbox $cashbox)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cashboxes,name,' . $cashbox->id,
            'type' => 'nullable|in:cash,card,wallet,bank',
            'status' => 'boolean',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        DB::beginTransaction();

        try {
            $cashbox->update([
                'name' => $validated['name'],
                'type' => $validated['type'] ?? $cashbox->type,
                'status' => $validated['status'] ?? $cashbox->status,
            ]);

            // Update payment method linkage
            if (array_key_exists('payment_method_id', $validated)) {
                // Remove any existing link to this cashbox
                PaymentMethod::where('cashbox_id', $cashbox->id)
                    ->update(['cashbox_id' => null]);

                // Link new payment method if provided
                if (!empty($validated['payment_method_id'])) {
                    PaymentMethod::where('id', $validated['payment_method_id'])
                        ->update(['cashbox_id' => $cashbox->id]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الخزينة بنجاح',
                'cashbox' => $cashbox->fresh('paymentMethod'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deposit(Request $request, Cashbox $cashbox)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $currentBalance = $cashbox->current_balance;
            $newBalance = $currentBalance + $validated['amount'];

            $transaction = CashboxTransaction::create([
                'cashbox_id' => $cashbox->id,
                'type' => 'in',
                'amount' => $validated['amount'],
                'balance_after' => $newBalance,
                'description' => $validated['description'] ?? 'إيداع نقدي',
                'transaction_date' => $validated['transaction_date'] ?? now(),
                'created_by' => Auth::id(),
            ]);

            $cashbox->update(['current_balance' => $newBalance]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم الإيداع بنجاح',
                'transaction' => $transaction,
                'new_balance' => $newBalance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function withdraw(Request $request, Cashbox $cashbox)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
        ]);

        if ($cashbox->current_balance < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'الرصيد غير كافٍ. الرصيد الحالي: ' . number_format($cashbox->current_balance, 2),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $currentBalance = $cashbox->current_balance;
            $newBalance = $currentBalance - $validated['amount'];

            $transaction = CashboxTransaction::create([
                'cashbox_id' => $cashbox->id,
                'type' => 'out',
                'amount' => $validated['amount'],
                'balance_after' => $newBalance,
                'description' => $validated['description'] ?? 'سحب نقدي',
                'transaction_date' => $validated['transaction_date'] ?? now(),
                'created_by' => Auth::id(),
            ]);

            $cashbox->update(['current_balance' => $newBalance]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم السحب بنجاح',
                'transaction' => $transaction,
                'new_balance' => $newBalance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function transfer(Request $request, Cashbox $cashbox)
    {
        $validated = $request->validate([
            'to_cashbox_id' => 'required|exists:cashboxes,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validated['to_cashbox_id'] == $cashbox->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن التحويل لنفس الخزينة',
            ], 422);
        }

        if ($cashbox->current_balance < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'الرصيد غير كافٍ. الرصيد الحالي: ' . number_format($cashbox->current_balance, 2),
            ], 422);
        }

        $toCashbox = Cashbox::findOrFail($validated['to_cashbox_id']);

        if (!$toCashbox->status) {
            return response()->json([
                'success' => false,
                'message' => 'الخزينة المستلمة غير نشطة',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $transactionDate = $validated['transaction_date'] ?? now();
            $description = $validated['description'] ?? "تحويل إلى {$toCashbox->name}";
            $descriptionIn = $validated['description'] ?? "تحويل من {$cashbox->name}";

            $fromBalance = $cashbox->current_balance - $validated['amount'];
            $outTransaction = CashboxTransaction::create([
                'cashbox_id' => $cashbox->id,
                'type' => 'transfer_out',
                'amount' => $validated['amount'],
                'balance_after' => $fromBalance,
                'description' => $description,
                'related_cashbox_id' => $toCashbox->id,
                'transaction_date' => $transactionDate,
                'created_by' => Auth::id(),
            ]);

            $toBalance = $toCashbox->current_balance + $validated['amount'];
            $inTransaction = CashboxTransaction::create([
                'cashbox_id' => $toCashbox->id,
                'type' => 'transfer_in',
                'amount' => $validated['amount'],
                'balance_after' => $toBalance,
                'description' => $descriptionIn,
                'related_cashbox_id' => $cashbox->id,
                'related_transaction_id' => $outTransaction->id,
                'transaction_date' => $transactionDate,
                'created_by' => Auth::id(),
            ]);

            DB::table('cashbox_transactions')
                ->where('id', $outTransaction->id)
                ->update(['related_transaction_id' => $inTransaction->id]);

            $cashbox->update(['current_balance' => $fromBalance]);
            $toCashbox->update(['current_balance' => $toBalance]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم التحويل بنجاح',
                'from_balance' => $fromBalance,
                'to_balance' => $toBalance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getTransactions(Request $request, Cashbox $cashbox)
    {
        $query = $cashbox->transactions()->with('creator', 'relatedCashbox');

        if ($request->filled('type')) {
            if ($request->type === 'in') {
                $query->whereIn('type', ['in', 'transfer_in']);
            } elseif ($request->type === 'out') {
                $query->whereIn('type', ['out', 'transfer_out']);
            } elseif ($request->type === 'transfer') {
                $query->whereIn('type', ['transfer_in', 'transfer_out']);
            } else {
                $query->where('type', $request->type);
            }
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
                'is_in' => $t->is_in,
                'is_out' => $t->is_out,
                'is_transfer' => $t->is_transfer,
                'amount' => $t->amount,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'related_cashbox' => $t->relatedCashbox?->name,
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
                'opening_balance' => $cashbox->opening_balance,
                'current_balance' => $cashbox->current_balance,
                'total_in' => $cashbox->transactions()->whereIn('type', ['in', 'transfer_in'])->sum('amount'),
                'total_out' => $cashbox->transactions()->whereIn('type', ['out', 'transfer_out'])->sum('amount'),
            ],
        ]);
    }

    public function getSummary(Cashbox $cashbox)
    {
        return response()->json([
            'success' => true,
            'cashbox' => [
                'id' => $cashbox->id,
                'name' => $cashbox->name,
                'opening_balance' => $cashbox->opening_balance,
                'current_balance' => $cashbox->current_balance,
                'status' => $cashbox->status,
            ],
            'stats' => [
                'total_in' => $cashbox->transactions()->whereIn('type', ['in', 'transfer_in'])->sum('amount'),
                'total_out' => $cashbox->transactions()->whereIn('type', ['out', 'transfer_out'])->sum('amount'),
                'transactions_count' => $cashbox->transactions()->count(),
                'last_transaction' => $cashbox->transactions()->latest('id')->first()?->transaction_date?->format('Y-m-d'),
            ],
        ]);
    }

    public function setOpeningBalance(Request $request, Cashbox $cashbox)
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
        ]);

        if ($cashbox->transactions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل الرصيد الافتتاحي بعد وجود حركات',
            ], 422);
        }

        $cashbox->update([
            'opening_balance' => $validated['opening_balance'],
            'current_balance' => $validated['opening_balance'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الرصيد الافتتاحي بنجاح',
            'opening_balance' => $validated['opening_balance'],
        ]);
    }

    public function print(Request $request, Cashbox $cashbox)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = $cashbox->transactions();

        if ($dateFrom) {
            $openingBalance = $cashbox->transactions()
                ->where('transaction_date', '<', $dateFrom)
                ->orderBy('id', 'desc')
                ->value('balance_after') ?? $cashbox->opening_balance;

            $query->whereDate('transaction_date', '>=', $dateFrom);
        } else {
            $openingBalance = $cashbox->opening_balance;
        }

        if ($dateTo) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        $transactions = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get();

        $totalIn = $transactions->whereIn('type', ['in', 'transfer_in'])->sum('amount');
        $totalOut = $transactions->whereIn('type', ['out', 'transfer_out'])->sum('amount');
        $closingBalance = $transactions->last()?->balance_after ?? $openingBalance;

        return view('cashboxes.print', compact(
            'cashbox',
            'transactions',
            'openingBalance',
            'closingBalance',
            'totalIn',
            'totalOut',
            'dateFrom',
            'dateTo'
        ));
    }

    public function getList()
    {
        $cashboxes = Cashbox::active()
            ->orderBy('name')
            ->get(['id', 'name', 'current_balance']);

        return response()->json([
            'success' => true,
            'data' => $cashboxes,
        ]);
    }

    public function checkName(Request $request)
    {
        $name = $request->get('name');
        $excludeId = $request->get('exclude_id');

        $query = Cashbox::where('name', $name);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return response()->json([
            'exists' => $query->exists(),
        ]);
    }
}
