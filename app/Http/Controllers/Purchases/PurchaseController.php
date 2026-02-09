<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Cashbox;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\FinancialTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    protected FinancialTransactionService $financialService;

    public function __construct(FinancialTransactionService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'creator'])
            ->orderBy('id', 'desc');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $purchases = $query->paginate(20);
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();

        $stats = [
            'total' => Purchase::count(),
            'draft' => Purchase::draft()->count(),
            'approved' => Purchase::approved()->count(),
            'total_amount' => Purchase::approved()->sum('total'),
        ];

        return view('purchases.index', compact('purchases', 'suppliers', 'stats'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $products = Product::with(['productUnits.unit', 'baseUnit'])
            ->where('status', true)
            ->orderBy('name')
            ->get();
        $cashboxes = Cashbox::active()->orderBy('name')->get();

        return view('purchases.create', compact('suppliers', 'products', 'cashboxes'));
    }

    public function store(Request $request)
    {
        $rules = [
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'payment_type' => 'required|in:cash,bank,credit',
            'paid_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_unit_id' => 'nullable|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
            'save_as_draft' => 'nullable|boolean',
        ];

        if (in_array($request->payment_type, ['cash', 'bank']) && !$request->boolean('save_as_draft')) {
            $paidAmount = $request->input('paid_amount', 0);
            if ($paidAmount > 0) {
                $rules['cashbox_id'] = 'required|exists:cashboxes,id';
            }
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            $paidAmount = $validated['paid_amount'] ?? 0;

            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'purchase_date' => $validated['purchase_date'],
                'payment_type' => $validated['payment_type'],
                'status' => 'draft',
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'] ?? 0,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'paid_amount' => $paidAmount,
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                $productUnit = $product->productUnits()
                    ->where('id', $itemData['product_unit_id'] ?? null)
                    ->first() ?? $product->baseUnit;

                $multiplier = $productUnit->multiplier ?? 1;
                $baseQuantity = $itemData['quantity'] * $multiplier;
                $totalPrice = $itemData['quantity'] * $itemData['unit_price'];
                $baseUnitCost = $baseQuantity > 0 ? $totalPrice / $baseQuantity : 0;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'product_unit_id' => $productUnit->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit_multiplier' => $multiplier,
                    'base_quantity' => $baseQuantity,
                    'total_price' => $totalPrice,
                    'base_unit_cost' => $baseUnitCost,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);
            }

            $purchase->load('items');
            $purchase->calculateTotals();
            $purchase->save();

            if (!$request->boolean('save_as_draft')) {
                $cashboxId = $validated['cashbox_id'] ?? null;
                $this->approvePurchase($purchase, $cashboxId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->boolean('save_as_draft')
                    ? 'تم حفظ الفاتورة كمسودة'
                    : 'تم حفظ واعتماد الفاتورة بنجاح',
                'purchase' => $purchase,
                'redirect' => route('purchases.show', $purchase),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'items.product',
            'items.productUnit.unit',
            'items.inventoryBatch',
            'creator',
            'approver',
            'canceller',
        ]);

        $financialTrace = $this->financialService->getFinancialTrace(Purchase::class, $purchase->id);

        return view('purchases.show', compact('purchase', 'financialTrace'));
    }

    public function edit(Purchase $purchase)
    {
        if (!$purchase->canBeEdited()) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'لا يمكن تعديل فاتورة معتمدة');
        }

        $purchase->load(['items.product', 'items.productUnit.unit']);
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $products = Product::with(['productUnits.unit', 'baseUnit'])
            ->where('status', true)
            ->orderBy('name')
            ->get();
        $cashboxes = Cashbox::active()->orderBy('name')->get();

        return view('purchases.edit', compact('purchase', 'suppliers', 'products', 'cashboxes'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        if (!$purchase->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل فاتورة معتمدة',
            ], 422);
        }

        $rules = [
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'payment_type' => 'required|in:cash,bank,credit',
            'paid_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_unit_id' => 'nullable|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
            'save_as_draft' => 'nullable|boolean',
        ];

        if (in_array($request->payment_type, ['cash', 'bank']) && !$request->boolean('save_as_draft')) {
            $paidAmount = $request->input('paid_amount', 0);
            if ($paidAmount > 0) {
                $rules['cashbox_id'] = 'required|exists:cashboxes,id';
            }
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            $paidAmount = $validated['paid_amount'] ?? 0;

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'purchase_date' => $validated['purchase_date'],
                'payment_type' => $validated['payment_type'],
                'paid_amount' => $paidAmount,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'] ?? 0,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'notes' => $validated['notes'],
            ]);

            $purchase->items()->delete();

            foreach ($validated['items'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                $productUnit = $product->productUnits()
                    ->where('id', $itemData['product_unit_id'] ?? null)
                    ->first() ?? $product->baseUnit;

                $multiplier = $productUnit->multiplier ?? 1;
                $baseQuantity = $itemData['quantity'] * $multiplier;
                $totalPrice = $itemData['quantity'] * $itemData['unit_price'];
                $baseUnitCost = $baseQuantity > 0 ? $totalPrice / $baseQuantity : 0;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'product_unit_id' => $productUnit->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit_multiplier' => $multiplier,
                    'base_quantity' => $baseQuantity,
                    'total_price' => $totalPrice,
                    'base_unit_cost' => $baseUnitCost,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);
            }

            $purchase->load('items');
            $purchase->calculateTotals();
            $purchase->save();

            if (!$request->boolean('save_as_draft')) {
                $cashboxId = $validated['cashbox_id'] ?? null;
                $this->approvePurchase($purchase, $cashboxId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->boolean('save_as_draft')
                    ? 'تم تحديث المسودة'
                    : 'تم تحديث واعتماد الفاتورة بنجاح',
                'purchase' => $purchase,
                'redirect' => route('purchases.show', $purchase),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, Purchase $purchase)
    {
        if ($purchase->status != 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد هذه الفاتورة',
            ], 422);
        }

        $rules = [
            'paid_amount' => 'nullable|numeric|min:0',
        ];

        if (in_array($purchase->payment_type, ['cash', 'bank'])) {
            $paidAmount = $request->input('paid_amount', $purchase->paid_amount ?? 0);
            if ($paidAmount > 0) {
                $rules['cashbox_id'] = 'required|exists:cashboxes,id';
            }
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            if (isset($validated['paid_amount'])) {
                $purchase->update(['paid_amount' => $validated['paid_amount']]);
            }

            $cashboxId = $validated['cashbox_id'] ?? null;
            $this->approvePurchase($purchase, $cashboxId);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم اعتماد الفاتورة بنجاح',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, Purchase $purchase)
    {
        if (!$purchase->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذه الفاتورة',
            ], 422);
        }

        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            if ($purchase->status == 'approved') {
                $this->reversePurchase($purchase);
            }

            $purchase->update([
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancel_reason' => $validated['cancel_reason'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الفاتورة بنجاح',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function print(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'items.product',
            'items.productUnit.unit',
            'creator',
            'approver',
        ]);

        return view('purchases.print', compact('purchase'));
    }

    public function searchProducts(Request $request)
    {
        try {
            $search = $request->get('q', '');

            $productIdsFromBarcodes = ProductBarcode::where(function ($q) use ($search) {
                    $q->where('barcode', 'like', "%{$search}%")
                      ->orWhere('label', 'like', "%{$search}%");
                })
                ->where('is_active', true)
                ->pluck('product_id');

            $products = Product::with(['productUnits.unit', 'baseUnit.unit'])
                ->where('status', true)
                ->where(function ($q) use ($search, $productIdsFromBarcodes) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhereIn('id', $productIdsFromBarcodes);
                })
                ->limit(20)
                ->get()
                ->map(function ($product) {
                    $baseCost = $product->baseUnit?->cost_price ?? 0;
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'barcode' => $product->barcode,
                        'current_stock' => $product->total_stock,
                        'base_unit' => $product->baseUnit ? [
                            'id' => $product->baseUnit->id,
                            'name' => $product->baseUnit->unit?->name ?? '-',
                            'cost_price' => $baseCost,
                            'multiplier' => 1,
                        ] : null,
                        'units' => $product->productUnits->map(function ($pu) use ($baseCost) {
                            $multiplier = $pu->multiplier ?? 1;
                            return [
                                'id' => $pu->id,
                                'name' => $pu->unit?->name ?? '-',
                                'multiplier' => $multiplier,
                                'cost_price' => $pu->is_base_unit ? $baseCost : ($baseCost * $multiplier),
                                'is_base' => $pu->is_base_unit,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'success' => true,
                'products' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProductByBarcode(Request $request)
    {
        $barcode = $request->get('barcode');
        $barcodeLabel = null;

        $product = Product::with(['productUnits.unit', 'baseUnit.unit'])
            ->where('barcode', $barcode)
            ->where('status', true)
            ->first();

        if (!$product) {
            $productBarcode = ProductBarcode::with('product')
                ->where('barcode', $barcode)
                ->where('is_active', true)
                ->first();

            if ($productBarcode && $productBarcode->product && $productBarcode->product->status == 'active') {
                $product = $productBarcode->product;
                $product->load(['productUnits.unit', 'baseUnit.unit']);
                $barcodeLabel = $productBarcode->label;
            }
        }

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على المنتج',
            ], 404);
        }

        $baseCost = $product->baseUnit?->cost_price ?? 0;
        $productName = $barcodeLabel ? "{$product->name} ({$barcodeLabel})" : $product->name;

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $productName,
                'barcode' => $product->barcode,
                'barcode_label' => $barcodeLabel,
                'current_stock' => $product->total_stock,
                'base_unit' => $product->baseUnit ? [
                    'id' => $product->baseUnit->id,
                    'name' => $product->baseUnit->unit?->name ?? '-',
                    'cost_price' => $baseCost,
                    'multiplier' => 1,
                ] : null,
                'units' => $product->productUnits->map(function ($pu) use ($baseCost) {
                    $multiplier = $pu->multiplier ?? 1;
                    return [
                        'id' => $pu->id,
                        'name' => $pu->unit?->name ?? '-',
                        'multiplier' => $multiplier,
                        'cost_price' => $pu->is_base_unit ? $baseCost : ($baseCost * $multiplier),
                        'is_base' => $pu->is_base_unit,
                    ];
                }),
            ],
        ]);
    }

    private function approvePurchase(Purchase $purchase, ?int $cashboxId = null): void
    {
        $purchase->load('items.product');
        $supplier = $purchase->supplier;

        foreach ($purchase->items as $item) {
            $product = $item->product;
            $currentStock = $product->total_stock;

            $batch = InventoryBatch::create([
                'product_id' => $item->product_id,
                'batch_number' => InventoryBatch::generateBatchNumber(),
                'quantity' => $item->base_quantity,
                'cost_price' => $item->base_unit_cost,
                'expiry_date' => $item->expiry_date,
                'notes' => "فاتورة مشتريات #{$purchase->id}",
                'type' => 'purchase',
            ]);

            $item->update(['inventory_batch_id' => $batch->id]);

            StockMovement::create([
                'product_id' => $item->product_id,
                'batch_id' => $batch->id,
                'type' => 'purchase',
                'reason' => "فاتورة مشتريات #{$purchase->id} - {$supplier->name}",
                'quantity' => $item->base_quantity,
                'before_quantity' => $currentStock,
                'after_quantity' => $currentStock + $item->base_quantity,
                'cost_price' => $item->base_unit_cost,
                'reference' => "PUR-{$purchase->id}",
                'user_id' => Auth::id(),
            ]);

            $this->updateProductAverageCost($product, $item->base_quantity, $item->base_unit_cost);
        }

        $this->financialService->createPurchaseEntries($purchase, $cashboxId);

        $remainingAmount = $purchase->total - ($purchase->paid_amount ?? 0);
        $purchase->update([
            'remaining_amount' => max(0, $remainingAmount),
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    private function reversePurchase(Purchase $purchase): void
    {
        $purchase->load('items.inventoryBatch');

        foreach ($purchase->items as $item) {
            if ($item->inventoryBatch) {
                $product = $item->product;
                $currentStock = $product->total_stock;
                $batch = $item->inventoryBatch;

                $batch->decrement('quantity', $item->base_quantity);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'batch_id' => $batch->id,
                    'type' => 'return',
                    'reason' => "إلغاء فاتورة مشتريات #{$purchase->id}",
                    'quantity' => -$item->base_quantity,
                    'before_quantity' => $currentStock,
                    'after_quantity' => $currentStock - $item->base_quantity,
                    'reference' => "PUR-{$purchase->id}-CANCEL",
                    'user_id' => Auth::id(),
                ]);
            }
        }

        $this->financialService->reversePurchaseEntries($purchase);
    }

    private function updateProductAverageCost(Product $product, float $newQty, float $newCost): void
    {
        $baseUnit = $product->baseUnit;
        if (!$baseUnit) return;

        $currentStock = $product->total_stock - $newQty;
        $currentCost = $baseUnit->cost_price ?? 0;

        if ($currentStock + $newQty > 0) {
            $avgCost = (($currentStock * $currentCost) + ($newQty * $newCost)) / ($currentStock + $newQty);
            $baseUnit->update(['cost_price' => round($avgCost, 4)]);
        }
    }
}
