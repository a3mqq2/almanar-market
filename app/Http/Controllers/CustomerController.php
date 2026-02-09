<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getCustomersData($request);
        }

        $stats = [
            'total' => Customer::count(),
            'active' => Customer::where('status', true)->count(),
            'with_credit' => Customer::where('allow_credit', true)->count(),
            'total_balance' => Customer::sum('current_balance'),
        ];

        return view('customers.index', compact('stats'));
    }

    protected function getCustomersData(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status == 'active');
        }

        if ($request->filled('allow_credit')) {
            $query->where('allow_credit', $request->allow_credit == 'yes');
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'phone', 'current_balance', 'credit_limit', 'status', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        $perPage = $request->get('per_page', 15);
        $customers = $query->paginate($perPage);

        $data = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'status' => $customer->status,
                'allow_credit' => $customer->allow_credit,
                'current_balance' => $customer->current_balance,
                'credit_limit' => $customer->credit_limit,
                'available_credit' => $customer->availableCredit,
                'created_at' => $customer->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'credit_limit' => 'nullable|numeric|min:0',
            'allow_credit' => 'boolean',
            'status' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'allow_credit' => $validated['allow_credit'] ?? false,
            'status' => $validated['status'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الزبون بنجاح',
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'credit_limit' => 'nullable|numeric|min:0',
            'allow_credit' => 'boolean',
            'status' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $customer->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'credit_limit' => $validated['credit_limit'] ?? $customer->credit_limit,
            'allow_credit' => $validated['allow_credit'] ?? $customer->allow_credit,
            'status' => $validated['status'] ?? $customer->status,
            'notes' => $validated['notes'] ?? $customer->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الزبون بنجاح',
            'customer' => $customer,
        ]);
    }

    public function destroy(Request $request, Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف زبون لديه فواتير',
            ], 422);
        }

        if ($customer->transactions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف زبون لديه حركات مالية',
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الزبون بنجاح',
        ]);
    }

    public function checkPhone(Request $request)
    {
        $phone = $request->get('phone');
        $excludeId = $request->get('exclude_id');

        $query = Customer::where('phone', $phone);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return response()->json([
            'exists' => $query->exists(),
        ]);
    }
}
