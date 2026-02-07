<?php

namespace App\Http\Controllers;

use App\Models\Cashbox;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    public function index()
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get(['id', 'name']);
        $cashboxes = Cashbox::active()->orderBy('name')->get(['id', 'name']);

        return view('expenses.index', compact('categories', 'cashboxes'));
    }

    public function filter(Request $request)
    {
        $query = Expense::with(['category:id,name', 'cashbox:id,name', 'creator:id,name']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('cashbox_id')) {
            $query->where('cashbox_id', $request->cashbox_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('category', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $statsQuery = clone $query;
        $stats = [
            'total_count' => $statsQuery->count(),
            'total_amount' => $statsQuery->sum('amount'),
        ];

        $sortField = $request->get('sort', 'expense_date');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['id', 'expense_date', 'amount', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'expense_date';
        }

        $query->orderBy($sortField, $sortDirection);

        $expenses = $query->paginate(15);

        $expensesData = $expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'reference_number' => $expense->reference_number,
                'title' => $expense->title,
                'category' => $expense->category->name ?? '-',
                'amount' => $expense->amount,
                'cashbox' => $expense->cashbox->name ?? '-',
                'expense_date' => $expense->expense_date->format('Y/m/d'),
                'creator' => $expense->creator->name ?? '-',
            ];
        });

        return response()->json([
            'success' => true,
            'expenses' => $expensesData,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
                'from' => $expenses->firstItem(),
                'to' => $expenses->lastItem(),
            ],
        ]);
    }

    public function create()
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $cashboxes = Cashbox::active()->orderBy('name')->get();
        $paymentMethods = PaymentMethod::active()->orderBy('sort_order')->get();

        return view('expenses.create', compact('categories', 'cashboxes', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'cashbox_id' => 'required|exists:cashboxes,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'expense_date' => 'required|date',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        try {
            $data = $request->only(['title', 'description', 'category_id', 'amount', 'cashbox_id', 'payment_method_id', 'expense_date']);

            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment')->store('expenses', 'public');
            }

            $expense = $this->expenseService->create($data);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل المصروف بنجاح',
                'expense' => $expense,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Expense $expense)
    {
        $expense->load([
            'category',
            'cashbox',
            'paymentMethod',
            'creator',
        ]);

        return view('expenses.show', compact('expense'));
    }

    public function report(Request $request)
    {
        $type = $request->get('type', 'daily');
        $dateFrom = $request->get('date_from', now()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        $categories = ExpenseCategory::active()->orderBy('name')->get(['id', 'name']);
        $cashboxes = Cashbox::active()->orderBy('name')->get(['id', 'name']);

        if ($type === 'daily') {
            $reportData = $this->expenseService->getDailyReport($dateFrom);
        } else {
            $reportData = $this->expenseService->getCategoryReport($dateFrom, $dateTo);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        }

        return view('expenses.report', compact('type', 'dateFrom', 'dateTo', 'categories', 'cashboxes', 'reportData'));
    }
}
