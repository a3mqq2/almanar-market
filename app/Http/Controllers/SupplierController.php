<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getSuppliersData($request);
        }

        $stats = [
            'total' => Supplier::count(),
            'active' => Supplier::where('status', true)->count(),
            'inactive' => Supplier::where('status', false)->count(),
        ];

        return view('suppliers.index', compact('stats'));
    }

    protected function getSuppliersData(Request $request)
    {
        $query = Supplier::query();

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

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'phone', 'status', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        $perPage = $request->get('per_page', 15);
        $suppliers = $query->paginate($perPage);

        $data = $suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'status' => $supplier->status,
                'current_balance' => $supplier->current_balance,
                'created_at' => $supplier->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
                'from' => $suppliers->firstItem(),
                'to' => $suppliers->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:suppliers,phone',
            'status' => 'boolean',
        ]);

        $supplier = Supplier::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'status' => $validated['status'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المورد بنجاح',
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:suppliers,phone,' . $supplier->id,
            'status' => 'boolean',
        ]);

        $supplier->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'status' => $validated['status'] ?? $supplier->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المورد بنجاح',
            'supplier' => $supplier,
        ]);
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المورد بنجاح',
        ]);
    }

    public function linkProducts()
    {
        $suppliers = Supplier::where('status', true)->orderBy('name')->get(['id', 'name']);

        return view('suppliers.link-products', compact('suppliers'));
    }

    public function searchProducts(Request $request)
    {
        $query = Product::where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('supplier_filter')) {
            if ($request->supplier_filter === 'none') {
                $query->whereNull('supplier_id');
            } else {
                $query->where('supplier_id', $request->supplier_filter);
            }
        }

        $products = $query->with('supplier:id,name')
            ->orderBy('name')
            ->paginate(50);

        return response()->json([
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'supplier_id' => $product->supplier_id,
                    'supplier_name' => $product->supplier?->name,
                ];
            }),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
        ]);
    }

    public function assignSupplier(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        $products = Product::whereIn('id', $validated['product_ids'])->get();
        foreach ($products as $product) {
            $product->update(['supplier_id' => $validated['supplier_id']]);
        }

        $supplier = Supplier::find($validated['supplier_id']);

        return response()->json([
            'success' => true,
            'message' => "تم ربط {$products->count()} منتج بالمورد {$supplier->name}",
        ]);
    }

    public function checkPhone(Request $request)
    {
        $phone = $request->get('phone');
        $excludeId = $request->get('exclude_id');

        $query = Supplier::where('phone', $phone);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return response()->json([
            'exists' => $query->exists(),
        ]);
    }
}
