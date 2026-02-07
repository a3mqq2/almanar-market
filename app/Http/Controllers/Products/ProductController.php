<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getProductsData($request);
        }

        $lowStockProductIds = DB::table('inventory_batches')
            ->select('product_id')
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) > 0 AND SUM(quantity) < 10')
            ->pluck('product_id');

        $outOfStockProductIds = DB::table('inventory_batches')
            ->select('product_id')
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) <= 0')
            ->pluck('product_id');

        $productsWithStock = DB::table('inventory_batches')
            ->distinct()
            ->pluck('product_id');

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'low_stock' => Product::whereIn('id', $lowStockProductIds)->count(),
            'out_of_stock' => Product::whereIn('id', $outOfStockProductIds)
                ->orWhereNotIn('id', $productsWithStock)->count(),
        ];

        return view('products.index', compact('stats'));
    }

    protected function getProductsData(Request $request)
    {
        $query = Product::with(['baseUnit.unit', 'inventoryBatches']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('stock_filter')) {
            switch ($request->stock_filter) {
                case 'low':
                    $query->whereHas('inventoryBatches', function ($q) {
                        $q->select('product_id')
                          ->groupBy('product_id')
                          ->havingRaw('SUM(quantity) > 0 AND SUM(quantity) < 10');
                    });
                    break;
                case 'out':
                    $query->where(function ($q) {
                        $q->whereDoesntHave('inventoryBatches')
                          ->orWhereHas('inventoryBatches', function ($sq) {
                              $sq->select('product_id')
                                ->groupBy('product_id')
                                ->havingRaw('SUM(quantity) = 0');
                          });
                    });
                    break;
                case 'expiring':
                    $query->whereHas('inventoryBatches', function ($q) {
                        $q->where('quantity', '>', 0)
                          ->whereNotNull('expiry_date')
                          ->whereDate('expiry_date', '<=', now()->addDays(30))
                          ->whereDate('expiry_date', '>=', now());
                    });
                    break;
                case 'expired':
                    $query->whereHas('inventoryBatches', function ($q) {
                        $q->where('quantity', '>', 0)
                          ->whereNotNull('expiry_date')
                          ->whereDate('expiry_date', '<', now());
                    });
                    break;
            }
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'barcode', 'status', 'created_at', 'updated_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $data = $products->map(function ($product) {
            $totalStock = $product->inventoryBatches->sum('quantity');
            $costPrice = $product->baseUnit?->cost_price ?? 0;
            $sellPrice = $product->baseUnit?->sell_price ?? 0;
            $profit = $sellPrice - $costPrice;
            $margin = $sellPrice > 0 ? round(($profit / $sellPrice) * 100, 1) : 0;

            $expiringBatches = $product->inventoryBatches->filter(function ($b) {
                return $b->expiry_date && $b->quantity > 0 &&
                       $b->expiry_date->diffInDays(now(), false) <= 30 &&
                       $b->expiry_date->diffInDays(now(), false) >= 0;
            })->count();

            $expiredBatches = $product->inventoryBatches->filter(function ($b) {
                return $b->expiry_date && $b->quantity > 0 &&
                       $b->expiry_date->lt(now());
            })->count();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'image' => $product->image ? Storage::url($product->image) : null,
                'status' => $product->status,
                'unit_name' => $product->baseUnit?->unit?->name ?? '-',
                'stock' => $totalStock,
                'cost_price' => $costPrice,
                'sell_price' => $sellPrice,
                'profit' => $profit,
                'margin' => $margin,
                'is_low_stock' => $totalStock > 0 && $totalStock < 10,
                'is_out_of_stock' => $totalStock <= 0,
                'has_expiring' => $expiringBatches > 0,
                'has_expired' => $expiredBatches > 0,
                'updated_at' => $product->updated_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ]
        ]);
    }

    public function create()
    {
        $units = Unit::all();
        $defaultUnit = Unit::where('is_default', true)->first();

        if (!$defaultUnit) {
            $defaultUnit = Unit::firstOrCreate(
                ['name' => 'قطعة'],
                ['symbol' => 'قطعة', 'is_default' => true]
            );
        }

        return view('products.create', compact('units', 'defaultUnit'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:units,id',
            'units.*.multiplier' => 'required|numeric|min:0.0001',
            'units.*.sell_price' => 'required|numeric|min:0',
            'units.*.cost_price' => 'nullable|numeric|min:0',
            'units.*.is_base_unit' => 'nullable|boolean',
            'opening_quantity' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'inventory_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
            }

            $product = Product::create([
                'name' => $validated['name'],
                'barcode' => $validated['barcode'],
                'image' => $imagePath,
                'status' => $validated['status'],
            ]);

            $baseCostPrice = 0;
            foreach ($validated['units'] as $index => $unitData) {
                $isBase = $index === 0;

                if ($isBase) {
                    $baseCostPrice = $unitData['cost_price'] ?? 0;
                }

                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_id' => $unitData['unit_id'],
                    'multiplier' => $unitData['multiplier'],
                    'sell_price' => $unitData['sell_price'],
                    'cost_price' => $isBase ? $baseCostPrice : null,
                    'is_base_unit' => $isBase,
                ]);
            }

            $openingQty = $validated['opening_quantity'] ?? 0;
            if ($openingQty > 0) {
                $batch = InventoryBatch::create([
                    'product_id' => $product->id,
                    'batch_number' => InventoryBatch::generateBatchNumber(),
                    'quantity' => $openingQty,
                    'cost_price' => $baseCostPrice,
                    'expiry_date' => $validated['expiry_date'] ?? null,
                    'notes' => $validated['inventory_notes'] ?? null,
                    'type' => 'opening_balance',
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'type' => 'opening_balance',
                    'quantity' => $openingQty,
                    'before_quantity' => 0,
                    'after_quantity' => $openingQty,
                    'cost_price' => $baseCostPrice,
                    'reference' => $batch->batch_number,
                    'notes' => 'رصيد افتتاحي',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('products.index')
                ->with('success', 'تم إضافة الصنف بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            return back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء حفظ الصنف: ' . $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        $product->load(['productUnits.unit', 'baseUnit']);
        $units = Unit::all();
        $defaultUnit = Unit::where('is_default', true)->first();

        return view('products.edit', compact('product', 'units', 'defaultUnit'));
    }

    public function update(Request $request, Product $product)
    {
        if ($request->ajax() && !$request->has('units')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
                'status' => 'required|in:active,inactive',
            ]);

            try {
                $product->update([
                    'name' => $validated['name'],
                    'barcode' => $validated['barcode'],
                    'status' => $validated['status'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث الصنف بنجاح',
                    'product' => $product,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث الصنف',
                ], 500);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive',
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:units,id',
            'units.*.multiplier' => 'required|numeric|min:0.0001',
            'units.*.sell_price' => 'required|numeric|min:0',
            'units.*.cost_price' => 'nullable|numeric|min:0',
            'units.*.is_base_unit' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $imagePath = $product->image;
            if ($request->hasFile('image')) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $imagePath = $request->file('image')->store('products', 'public');
            }

            $product->update([
                'name' => $validated['name'],
                'barcode' => $validated['barcode'],
                'image' => $imagePath,
                'status' => $validated['status'],
            ]);

            $product->productUnits()->delete();

            $baseCostPrice = 0;
            foreach ($validated['units'] as $index => $unitData) {
                $isBase = $index === 0;

                if ($isBase) {
                    $baseCostPrice = $unitData['cost_price'] ?? 0;
                }

                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_id' => $unitData['unit_id'],
                    'multiplier' => $unitData['multiplier'],
                    'sell_price' => $unitData['sell_price'],
                    'cost_price' => $isBase ? $baseCostPrice : null,
                    'is_base_unit' => $isBase,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('products.index')
                ->with('success', 'تم تحديث الصنف بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الصنف: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, Product $product)
    {
        DB::beginTransaction();

        try {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الصنف بنجاح'
                ]);
            }

            return redirect()
                ->route('products.index')
                ->with('success', 'تم حذف الصنف بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الصنف'
                ], 500);
            }

            return back()->with('error', 'حدث خطأ أثناء حذف الصنف');
        }
    }

    public function duplicate(Product $product)
    {
        DB::beginTransaction();

        try {
            $newProduct = $product->replicate();
            $newProduct->name = $product->name . ' (نسخة)';
            $newProduct->barcode = null;
            $newProduct->save();

            foreach ($product->productUnits as $unit) {
                $newUnit = $unit->replicate();
                $newUnit->product_id = $newProduct->id;
                $newUnit->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم نسخ الصنف بنجاح',
                'product_id' => $newProduct->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء نسخ الصنف'
            ], 500);
        }
    }

    public function generateBarcode()
    {
        do {
            $barcode = str_pad(mt_rand(1, 9999999999999), 13, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $barcode)->exists());

        return response()->json(['barcode' => $barcode]);
    }

    public function show(Product $product)
    {
        $product->load(['productUnits.unit', 'inventoryBatches', 'stockMovements.user']);
        $units = Unit::all();

        return view('products.show', compact('product', 'units'));
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::create([
                'name' => $validated['name'],
                'barcode' => $validated['barcode'] ?: null,
                'status' => 'active',
            ]);

            $defaultUnit = Unit::where('is_default', true)->first();
            if (!$defaultUnit) {
                $defaultUnit = Unit::firstOrCreate(
                    ['name' => 'قطعة'],
                    ['symbol' => 'قطعة', 'is_default' => true]
                );
            }

            ProductUnit::create([
                'product_id' => $product->id,
                'unit_id' => $defaultUnit->id,
                'multiplier' => 1,
                'sell_price' => 0,
                'cost_price' => 0,
                'is_base_unit' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الصنف بنجاح',
                'product_id' => $product->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkBarcode(Request $request)
    {
        $barcode = $request->get('barcode');
        $excludeId = $request->get('exclude_id');

        $query = Product::where('barcode', $barcode);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return response()->json([
            'exists' => $query->exists(),
        ]);
    }
}
