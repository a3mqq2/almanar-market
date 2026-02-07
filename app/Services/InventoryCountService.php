<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryCountService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createCount(string $type, ?string $notes = null): InventoryCount
    {
        return InventoryCount::create([
            'reference_number' => InventoryCount::generateReferenceNumber(),
            'count_type' => $type,
            'status' => 'draft',
            'counted_by' => Auth::id(),
            'notes' => $notes,
        ]);
    }

    public function startCount(InventoryCount $count): void
    {
        if (!$count->canStart()) {
            throw new \Exception('لا يمكن بدء هذا الجرد');
        }

        DB::transaction(function () use ($count) {
            $products = Product::where('status', 'active')->get();

            foreach ($products as $product) {
                $stockInfo = $this->inventoryService->getStockWithCost($product);
                $baseUnit = $product->baseUnit;

                InventoryCountItem::create([
                    'inventory_count_id' => $count->id,
                    'product_id' => $product->id,
                    'unit_id' => $baseUnit?->unit_id,
                    'system_qty' => $stockInfo['quantity'],
                    'system_cost' => $stockInfo['average_cost'],
                    'counted_qty' => null,
                    'difference' => 0,
                    'variance_value' => 0,
                ]);
            }

            $count->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            $count->updateCounters();

            UserActivityLog::log(
                'inventory_count_started',
                "بدء جرد المخزون #{$count->reference_number}",
                Auth::id(),
                ['count_id' => $count->id, 'total_items' => $count->total_items]
            );
        });
    }

    public function countItem(InventoryCountItem $item, float $countedQty, ?string $notes = null): void
    {
        $count = $item->inventoryCount;

        if (!$count->canCount()) {
            throw new \Exception('لا يمكن تسجيل جرد في هذه الحالة');
        }

        DB::transaction(function () use ($item, $countedQty, $notes, $count) {
            $item->counted_qty = $countedQty;
            $item->counted_at = now();
            $item->counted_by = Auth::id();
            $item->notes = $notes;
            $item->calculateVariance();
            $item->save();

            $count->updateCounters();
        });
    }

    public function completeCount(InventoryCount $count): void
    {
        $uncountedItems = $count->items()->whereNull('counted_qty')->count();

        if ($uncountedItems > 0) {
            throw new \Exception("لا يمكن إكمال الجرد. هناك {$uncountedItems} منتج لم يتم جردهم");
        }

        DB::transaction(function () use ($count) {
            $count->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $count->updateCounters();

            UserActivityLog::log(
                'inventory_count_completed',
                "اكتمال جرد المخزون #{$count->reference_number}",
                Auth::id(),
                ['count_id' => $count->id, 'variance_items' => $count->variance_items]
            );
        });
    }

    public function approveCount(InventoryCount $count): void
    {
        if (!$count->canApprove()) {
            throw new \Exception('لا يمكن اعتماد هذا الجرد');
        }

        if (!Auth::user()->isManager()) {
            throw new \Exception('فقط المديرين يمكنهم اعتماد الجرد');
        }

        DB::transaction(function () use ($count) {
            $totalVarianceValue = 0;

            foreach ($count->items()->where('difference', '!=', 0)->get() as $item) {
                $product = $item->product;
                $difference = $item->difference;
                $cost = $item->system_cost;

                if ($difference > 0) {
                    $this->createSurplusAdjustment($product, $difference, $cost, $count);
                } else {
                    $this->createShortageAdjustment($product, abs($difference), $count);
                }

                $totalVarianceValue += $item->variance_value;
            }

            if (abs($totalVarianceValue) > 0.01) {
                UserActivityLog::log(
                    'inventory_variance_recorded',
                    $totalVarianceValue > 0
                        ? "فائض جرد مخزون #{$count->reference_number}"
                        : "عجز جرد مخزون #{$count->reference_number}",
                    Auth::id(),
                    [
                        'count_id' => $count->id,
                        'reference_number' => $count->reference_number,
                        'variance_value' => $totalVarianceValue,
                        'type' => $totalVarianceValue > 0 ? 'gain' : 'loss',
                    ]
                );
            }

            $count->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            UserActivityLog::log(
                'inventory_count_approved',
                "اعتماد جرد المخزون #{$count->reference_number}",
                Auth::id(),
                [
                    'count_id' => $count->id,
                    'variance_value' => $totalVarianceValue,
                    'variance_items' => $count->variance_items,
                ]
            );
        });
    }

    public function cancelCount(InventoryCount $count, string $reason): void
    {
        if (!$count->canCancel()) {
            throw new \Exception('لا يمكن إلغاء هذا الجرد');
        }

        DB::transaction(function () use ($count, $reason) {
            $count->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            UserActivityLog::log(
                'inventory_count_cancelled',
                "إلغاء جرد المخزون #{$count->reference_number}",
                Auth::id(),
                ['count_id' => $count->id, 'reason' => $reason]
            );
        });
    }

    private function createSurplusAdjustment(Product $product, float $quantity, float $cost, InventoryCount $count): void
    {
        $currentStock = $product->total_stock ?? 0;

        $batch = InventoryBatch::create([
            'product_id' => $product->id,
            'batch_number' => InventoryBatch::generateBatchNumber(),
            'quantity' => $quantity,
            'cost_price' => $cost,
            'notes' => "فائض جرد #{$count->reference_number}",
            'type' => 'adjustment',
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'type' => 'adjustment',
            'reason' => "فائض جرد المخزون #{$count->reference_number}",
            'quantity' => $quantity,
            'before_quantity' => $currentStock,
            'after_quantity' => $currentStock + $quantity,
            'cost_price' => $cost,
            'reference' => "INV-{$count->id}",
            'user_id' => Auth::id(),
        ]);
    }

    private function createShortageAdjustment(Product $product, float $quantity, InventoryCount $count): void
    {
        try {
            $this->inventoryService->deductStockWithType(
                $product,
                $quantity,
                'adjustment',
                "نقص جرد المخزون #{$count->reference_number}",
                "INV-{$count->id}"
            );
        } catch (\Exception $e) {
            $currentStock = $product->total_stock ?? 0;

            StockMovement::create([
                'product_id' => $product->id,
                'batch_id' => null,
                'type' => 'adjustment',
                'reason' => "نقص جرد المخزون #{$count->reference_number}",
                'quantity' => -$quantity,
                'before_quantity' => $currentStock,
                'after_quantity' => max(0, $currentStock - $quantity),
                'cost_price' => 0,
                'reference' => "INV-{$count->id}",
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function getVarianceSummary(InventoryCount $count): array
    {
        $items = $count->items()->with('product')->get();

        return [
            'total_items' => $count->total_items,
            'counted_items' => $count->counted_items,
            'match_items' => $items->where('variance_status', 'match')->count(),
            'surplus_items' => $items->where('variance_status', 'surplus')->count(),
            'shortage_items' => $items->where('variance_status', 'shortage')->count(),
            'total_system_value' => $count->total_system_value,
            'total_counted_value' => $count->total_counted_value,
            'variance_value' => $count->variance_value,
            'surplus_value' => $items->where('difference', '>', 0)->sum('variance_value'),
            'shortage_value' => abs($items->where('difference', '<', 0)->sum('variance_value')),
        ];
    }
}
