<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\FinancialTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function addStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.0001',
            'cost_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $currentStock = $product->total_stock;
            $costPrice = $validated['cost_price'] ?? $product->baseUnit?->cost_price ?? 0;

            $batch = InventoryBatch::create([
                'product_id' => $product->id,
                'batch_number' => InventoryBatch::generateBatchNumber(),
                'quantity' => $validated['quantity'],
                'cost_price' => $costPrice,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'type' => 'purchase',
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'type' => 'purchase',
                'reason' => $validated['reason'],
                'quantity' => $validated['quantity'],
                'before_quantity' => $currentStock,
                'after_quantity' => $currentStock + $validated['quantity'],
                'cost_price' => $costPrice,
                'reference' => $batch->batch_number,
                'notes' => $validated['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);

            if (!empty($validated['cost_price'])) {
                $this->updateAverageCost($product, $validated['quantity'], $costPrice);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المخزون بنجاح',
                'new_stock' => $currentStock + $validated['quantity'],
                'batch' => $batch,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function removeStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.0001',
            'batch_id' => 'nullable|exists:inventory_batches,id',
            'type' => 'required|in:sale,damage,loss,return',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $currentStock = $product->total_stock;

            if ($validated['quantity'] > $currentStock) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المطلوبة أكبر من المخزون المتاح (' . $currentStock . ')',
                ], 422);
            }

            $remainingQty = $validated['quantity'];
            $batch = null;

            if ($validated['batch_id']) {
                $batch = InventoryBatch::find($validated['batch_id']);
                if ($batch && $batch->quantity >= $remainingQty) {
                    $batch->decrement('quantity', $remainingQty);
                    $remainingQty = 0;
                } else if ($batch) {
                    $remainingQty -= $batch->quantity;
                    $batch->update(['quantity' => 0]);
                }
            }

            if ($remainingQty > 0) {
                $batches = InventoryBatch::where('product_id', $product->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('expiry_date')
                    ->orderBy('created_at')
                    ->get();

                foreach ($batches as $b) {
                    if ($remainingQty <= 0) break;

                    if ($b->quantity >= $remainingQty) {
                        $b->decrement('quantity', $remainingQty);
                        $remainingQty = 0;
                    } else {
                        $remainingQty -= $b->quantity;
                        $b->update(['quantity' => 0]);
                    }
                }
            }

            StockMovement::create([
                'product_id' => $product->id,
                'batch_id' => $batch?->id,
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'quantity' => -$validated['quantity'],
                'before_quantity' => $currentStock,
                'after_quantity' => $currentStock - $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            $newStock = $product->fresh()->total_stock;

            return response()->json([
                'success' => true,
                'message' => 'تم خصم المخزون بنجاح',
                'new_stock' => $newStock,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function adjustStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'new_quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $currentStock = $product->total_stock;
            $difference = $validated['new_quantity'] - $currentStock;

            if ($difference == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية الجديدة مطابقة للكمية الحالية',
                ], 422);
            }

            if ($difference > 0) {
                $batch = InventoryBatch::create([
                    'product_id' => $product->id,
                    'batch_number' => InventoryBatch::generateBatchNumber(),
                    'quantity' => $difference,
                    'cost_price' => $product->baseUnit?->cost_price ?? 0,
                    'type' => 'adjustment',
                ]);
            } else {
                $remainingQty = abs($difference);
                $batches = InventoryBatch::where('product_id', $product->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('expiry_date')
                    ->orderBy('created_at')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingQty <= 0) break;

                    if ($batch->quantity >= $remainingQty) {
                        $batch->decrement('quantity', $remainingQty);
                        $remainingQty = 0;
                    } else {
                        $remainingQty -= $batch->quantity;
                        $batch->update(['quantity' => 0]);
                    }
                }
            }

            StockMovement::create([
                'product_id' => $product->id,
                'batch_id' => isset($batch) ? $batch->id : null,
                'type' => 'adjustment',
                'reason' => $validated['reason'],
                'quantity' => $difference,
                'before_quantity' => $currentStock,
                'after_quantity' => $validated['new_quantity'],
                'notes' => $validated['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تعديل المخزون بنجاح',
                'new_stock' => $validated['new_quantity'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getBatches(Product $product)
    {
        $batches = InventoryBatch::where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('created_at')
            ->get()
            ->map(function ($batch) {
                $batch->status = $this->getBatchStatus($batch);
                return $batch;
            });

        return response()->json([
            'success' => true,
            'batches' => $batches,
            'total_stock' => $product->total_stock,
        ]);
    }

    public function getHistory(Request $request, Product $product)
    {
        $query = StockMovement::where('product_id', $product->id)
            ->with(['user', 'batch'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->paginate(15);

        return response()->json([
            'success' => true,
            'movements' => $movements,
        ]);
    }

    public function updateUnits(Request $request, Product $product)
    {
        $validated = $request->validate([
            'units' => 'required|array|min:1',
            'units.*.unit_id' => 'required|exists:units,id',
            'units.*.multiplier' => 'required|numeric|min:0.0001',
            'units.*.sell_price' => 'required|numeric|min:0',
            'units.*.cost_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $existingUnits = $product->productUnits()->get()->keyBy('unit_id');
            $incomingUnitIds = [];
            $baseCostPrice = 0;

            foreach ($validated['units'] as $index => $unitData) {
                $isBase = $index == 0;

                if ($isBase) {
                    $baseCostPrice = $unitData['cost_price'] ?? 0;
                }

                $incomingUnitIds[] = $unitData['unit_id'];

                $existing = $existingUnits->get($unitData['unit_id']);
                if ($existing) {
                    $existing->update([
                        'multiplier' => $unitData['multiplier'],
                        'sell_price' => $unitData['sell_price'],
                        'cost_price' => $isBase ? $baseCostPrice : null,
                        'is_base_unit' => $isBase,
                    ]);
                } else {
                    ProductUnit::create([
                        'product_id' => $product->id,
                        'unit_id' => $unitData['unit_id'],
                        'multiplier' => $unitData['multiplier'],
                        'sell_price' => $unitData['sell_price'],
                        'cost_price' => $isBase ? $baseCostPrice : null,
                        'is_base_unit' => $isBase,
                    ]);
                }
            }

            $product->productUnits()
                ->whereNotIn('unit_id', $incomingUnitIds)
                ->whereDoesntHave('saleItems')
                ->whereDoesntHave('purchaseItems')
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الوحدات والأسعار بنجاح',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function quickPurchase(Request $request, Product $product)
    {
        $rules = [
            'supplier_id' => 'required|exists:suppliers,id',
            'product_unit_id' => 'nullable|exists:product_units,id',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_price' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'invoice_number' => 'nullable|string|max:100',
            'payment_type' => 'required|in:cash,credit',
            'paid_amount' => 'nullable|numeric|min:0',
            'cashbox_id' => 'nullable|exists:cashboxes,id',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($request->payment_type === 'cash') {
            $paidAmount = $request->input('paid_amount', 0);
            if ($paidAmount > 0) {
                $rules['cashbox_id'] = 'required|exists:cashboxes,id';
            }
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            $productUnit = $product->productUnits()
                ->where('id', $validated['product_unit_id'] ?? null)
                ->first() ?? $product->baseUnit;

            $multiplier = $productUnit->multiplier ?? 1;
            $baseQuantity = $validated['quantity'] * $multiplier;
            $totalPrice = $validated['quantity'] * $validated['unit_price'];
            $baseUnitCost = $baseQuantity > 0 ? $totalPrice / $baseQuantity : 0;
            $paidAmount = $validated['paid_amount'] ?? 0;

            if ($validated['payment_type'] === 'credit') {
                $paidAmount = 0;
            }

            $remainingAmount = max(0, $totalPrice - $paidAmount);

            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'purchase_date' => now(),
                'payment_type' => $validated['payment_type'],
                'status' => 'draft',
                'subtotal' => $totalPrice,
                'total' => $totalPrice,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'product_unit_id' => $productUnit->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'unit_multiplier' => $multiplier,
                'base_quantity' => $baseQuantity,
                'total_price' => $totalPrice,
                'base_unit_cost' => $baseUnitCost,
                'expiry_date' => $validated['expiry_date'] ?? null,
            ]);

            $purchase->load('items.product');

            $supplier = $purchase->supplier;
            $currentStock = $product->total_stock;

            $batch = InventoryBatch::create([
                'product_id' => $product->id,
                'batch_number' => InventoryBatch::generateBatchNumber(),
                'quantity' => $baseQuantity,
                'cost_price' => $baseUnitCost,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'notes' => "فاتورة مشتريات #{$purchase->id}",
                'type' => 'purchase',
            ]);

            $purchase->items->first()->update(['inventory_batch_id' => $batch->id]);

            StockMovement::create([
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'type' => 'purchase',
                'reason' => "فاتورة مشتريات #{$purchase->id} - {$supplier->name}",
                'quantity' => $baseQuantity,
                'before_quantity' => $currentStock,
                'after_quantity' => $currentStock + $baseQuantity,
                'cost_price' => $baseUnitCost,
                'reference' => "PUR-{$purchase->id}",
                'user_id' => Auth::id(),
            ]);

            $this->updateAverageCost($product, $baseQuantity, $baseUnitCost);

            $cashboxId = $validated['cashbox_id'] ?? null;
            $financialService = app(FinancialTransactionService::class);
            $financialService->createPurchaseEntries($purchase, $cashboxId);

            $purchase->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل عملية الشراء بنجاح',
                'new_stock' => $currentStock + $baseQuantity,
                'purchase_id' => $purchase->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function updateAverageCost(Product $product, float $newQty, float $newCost): void
    {
        $baseUnit = $product->baseUnit;
        if (!$baseUnit) return;

        $currentStock = $product->total_stock - $newQty;
        $currentCost = $baseUnit->cost_price ?? 0;

        if ($currentStock + $newQty > 0) {
            $avgCost = (($currentStock * $currentCost) + ($newQty * $newCost)) / ($currentStock + $newQty);
            $baseUnit->update(['cost_price' => round($avgCost, 2)]);
        }
    }

    private function getBatchStatus(InventoryBatch $batch): array
    {
        if (!$batch->expiry_date) {
            return ['label' => 'عادي', 'class' => 'success'];
        }

        $daysUntilExpiry = now()->diffInDays($batch->expiry_date, false);

        if ($daysUntilExpiry < 0) {
            return ['label' => 'منتهي', 'class' => 'danger'];
        } elseif ($daysUntilExpiry <= 30) {
            return ['label' => 'قريب الانتهاء', 'class' => 'warning'];
        } elseif ($daysUntilExpiry <= 90) {
            return ['label' => 'يحتاج متابعة', 'class' => 'info'];
        }

        return ['label' => 'جيد', 'class' => 'success'];
    }
}
