<?php

namespace App\Console\Commands;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMissingBatches extends Command
{
    protected $signature = 'inventory:fix-missing-batches
        {--product= : Fix specific product ID only}
        {--dry-run : Show what would be fixed without fixing}';

    protected $description = 'Find products with stock movements but no batches, and create adjustment batches';

    public function handle(): int
    {
        $productId = $this->option('product');

        $query = Product::query();
        if ($productId) {
            $query->where('id', $productId);
        }

        $products = $query->get();
        $issues = [];

        foreach ($products as $product) {
            $batchCount = InventoryBatch::where('product_id', $product->id)->count();
            $movementQty = (float) StockMovement::where('product_id', $product->id)->sum('quantity');

            if ($batchCount === 0 && abs($movementQty) > 0.001) {
                $lastMovement = StockMovement::where('product_id', $product->id)
                    ->orderBy('id', 'desc')
                    ->first();

                $issues[] = [
                    'product' => $product,
                    'movement_qty' => $movementQty,
                    'movement_count' => StockMovement::where('product_id', $product->id)->count(),
                    'last_cost' => $lastMovement?->cost_price ?? 0,
                ];
            }
        }

        if (empty($issues)) {
            $this->info('No products found with movements but no batches.');
            return 0;
        }

        $this->warn("Found " . count($issues) . " products with movements but no batches");
        $this->newLine();

        $rows = [];
        foreach ($issues as $issue) {
            $rows[] = [
                $issue['product']->id,
                mb_substr($issue['product']->name, 0, 35),
                $issue['movement_count'],
                number_format($issue['movement_qty'], 2),
                number_format($issue['last_cost'], 2),
            ];
        }

        $this->table(
            ['Product', 'Name', 'Movements', 'Net Qty', 'Last Cost'],
            $rows
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run - no changes made.');
            return 0;
        }

        $positiveOnly = collect($issues)->filter(fn($i) => $i['movement_qty'] > 0);
        $negativeOnly = collect($issues)->filter(fn($i) => $i['movement_qty'] <= 0);

        if ($negativeOnly->isNotEmpty()) {
            $this->newLine();
            $this->warn("Skipping " . $negativeOnly->count() . " products with negative/zero net quantity (no stock to assign)");
        }

        if ($positiveOnly->isEmpty()) {
            $this->info('No products with positive stock to fix.');
            return 0;
        }

        if (!$this->confirm("Create adjustment batches for {$positiveOnly->count()} products?")) {
            return 0;
        }

        DB::beginTransaction();

        try {
            $fixed = 0;

            foreach ($positiveOnly as $issue) {
                $product = $issue['product'];

                $batch = InventoryBatch::create([
                    'product_id' => $product->id,
                    'batch_number' => 'ADJ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                    'quantity' => $issue['movement_qty'],
                    'cost_price' => $issue['last_cost'],
                    'type' => 'adjustment',
                    'notes' => 'تسوية تلقائية - منتج بدون دفعات',
                ]);

                StockMovement::where('product_id', $product->id)
                    ->whereNull('batch_id')
                    ->update(['batch_id' => $batch->id]);

                $fixed++;
            }

            DB::commit();
            $this->info("Created {$fixed} adjustment batches and linked orphaned movements.");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Fix failed: " . $e->getMessage());
            return 1;
        }
    }
}
