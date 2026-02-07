<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    public function deductStock(
        Product $product,
        float $baseQuantity,
        string $reason,
        ?string $reference = null
    ): array {
        $remaining = $baseQuantity;
        $deductions = [];
        $totalCost = 0;

        $batches = InventoryBatch::where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->orderBy('expiry_date', 'asc')
            ->get();

        $availableStock = $batches->sum('quantity');

        if ($availableStock < $baseQuantity) {
            throw new InsufficientStockException(
                "المخزون غير كافٍ للمنتج: {$product->name}. المطلوب: {$baseQuantity}، المتاح: {$availableStock}",
                $product,
                $baseQuantity,
                $availableStock
            );
        }

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $deduct = min($batch->quantity, $remaining);
            $currentStock = $product->total_stock ?? 0;

            $batch->decrement('quantity', $deduct);

            StockMovement::create([
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'type' => 'sale',
                'reason' => $reason,
                'quantity' => -$deduct,
                'before_quantity' => $currentStock,
                'after_quantity' => $currentStock - $deduct,
                'cost_price' => $batch->cost_price,
                'reference' => $reference,
                'user_id' => Auth::id(),
            ]);

            $deductions[] = [
                'batch_id' => $batch->id,
                'quantity' => $deduct,
                'cost_price' => $batch->cost_price,
            ];

            $totalCost += $deduct * $batch->cost_price;
            $remaining -= $deduct;
        }

        return [
            'deductions' => $deductions,
            'total_cost' => $totalCost,
            'average_cost' => $baseQuantity > 0 ? $totalCost / $baseQuantity : 0,
        ];
    }

    public function restoreStock(
        Product $product,
        array $deductions,
        string $reason,
        ?string $reference = null
    ): void {
        foreach ($deductions as $deduction) {
            $batch = InventoryBatch::find($deduction['batch_id']);
            if (!$batch) continue;

            $quantity = $deduction['quantity'];
            $currentStock = $product->total_stock ?? 0;

            $batch->increment('quantity', $quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'type' => 'return',
                'reason' => $reason,
                'quantity' => $quantity,
                'before_quantity' => $currentStock,
                'after_quantity' => $currentStock + $quantity,
                'cost_price' => $batch->cost_price,
                'reference' => $reference,
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function hasStock(Product $product, float $baseQuantity): bool
    {
        return $this->getAvailableStock($product) >= $baseQuantity;
    }

    public function getAvailableStock(Product $product): float
    {
        return InventoryBatch::where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->sum('quantity');
    }

    public function getStockWithCost(Product $product): array
    {
        $batches = InventoryBatch::where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $totalQuantity = 0;
        $totalCost = 0;

        foreach ($batches as $batch) {
            $totalQuantity += $batch->quantity;
            $totalCost += $batch->quantity * $batch->cost_price;
        }

        return [
            'quantity' => $totalQuantity,
            'total_cost' => $totalCost,
            'average_cost' => $totalQuantity > 0 ? $totalCost / $totalQuantity : 0,
        ];
    }

    public function restoreStockFromReturn(
        Product $product,
        float $baseQuantity,
        float $costPrice,
        string $reason,
        ?string $reference = null
    ): ?InventoryBatch {
        $existingBatch = InventoryBatch::where('product_id', $product->id)
            ->where('cost_price', $costPrice)
            ->whereNull('expiry_date')
            ->orderBy('created_at', 'desc')
            ->first();

        $currentStock = $product->total_stock ?? 0;

        if ($existingBatch) {
            $existingBatch->increment('quantity', $baseQuantity);

            StockMovement::create([
                'product_id' => $product->id,
                'batch_id' => $existingBatch->id,
                'type' => 'return',
                'reason' => $reason,
                'quantity' => $baseQuantity,
                'before_quantity' => $currentStock,
                'after_quantity' => $currentStock + $baseQuantity,
                'cost_price' => $costPrice,
                'reference' => $reference,
                'user_id' => Auth::id(),
            ]);

            return $existingBatch;
        }

        $batch = InventoryBatch::create([
            'product_id' => $product->id,
            'quantity' => $baseQuantity,
            'cost_price' => $costPrice,
            'reference' => $reference,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'type' => 'return',
            'reason' => $reason,
            'quantity' => $baseQuantity,
            'before_quantity' => $currentStock,
            'after_quantity' => $currentStock + $baseQuantity,
            'cost_price' => $costPrice,
            'reference' => $reference,
            'user_id' => Auth::id(),
        ]);

        return $batch;
    }

    public function deductStockWithType(
        Product $product,
        float $baseQuantity,
        string $type,
        string $reason,
        ?string $reference = null
    ): array {
        $remaining = $baseQuantity;
        $deductions = [];
        $totalCost = 0;

        $batches = InventoryBatch::where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->orderBy('expiry_date', 'asc')
            ->get();

        $availableStock = $batches->sum('quantity');

        if ($availableStock < $baseQuantity) {
            throw new InsufficientStockException(
                "المخزون غير كافٍ للمنتج: {$product->name}. المطلوب: {$baseQuantity}، المتاح: {$availableStock}",
                $product,
                $baseQuantity,
                $availableStock
            );
        }

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $deduct = min($batch->quantity, $remaining);
            $currentStock = $product->total_stock ?? 0;

            $batch->decrement('quantity', $deduct);

            StockMovement::create([
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'type' => $type,
                'reason' => $reason,
                'quantity' => -$deduct,
                'before_quantity' => $currentStock,
                'after_quantity' => $currentStock - $deduct,
                'cost_price' => $batch->cost_price,
                'reference' => $reference,
                'user_id' => Auth::id(),
            ]);

            $deductions[] = [
                'batch_id' => $batch->id,
                'quantity' => $deduct,
                'cost_price' => $batch->cost_price,
            ];

            $totalCost += $deduct * $batch->cost_price;
            $remaining -= $deduct;
        }

        return [
            'deductions' => $deductions,
            'total_cost' => $totalCost,
            'average_cost' => $baseQuantity > 0 ? $totalCost / $baseQuantity : 0,
        ];
    }
}
