<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMissingProductUnits extends Command
{
    protected $signature = 'products:fix-units {--dry-run : Show what would be fixed without fixing}';

    protected $description = 'Find products without units and restore them from sale/purchase history';

    public function handle(): int
    {
        $products = Product::whereDoesntHave('productUnits')->get();

        if ($products->isEmpty()) {
            $this->info('All products have units.');
            return 0;
        }

        $this->warn("Found {$products->count()} products without units:");
        $this->newLine();

        $rows = [];

        foreach ($products as $product) {
            $saleItem = SaleItem::where('product_id', $product->id)
                ->whereNotNull('unit_price')
                ->where('unit_price', '>', 0)
                ->latest('id')
                ->first();

            $purchaseItem = PurchaseItem::where('product_id', $product->id)
                ->whereNotNull('unit_price')
                ->where('unit_price', '>', 0)
                ->latest('id')
                ->first();

            $sellPrice = $saleItem?->unit_price ?? 0;
            $costPrice = $purchaseItem?->unit_price ?? $saleItem?->cost_at_sale ?? 0;

            $source = [];
            if ($saleItem) $source[] = "sale #{$saleItem->sale_id} (price: {$saleItem->unit_price})";
            if ($purchaseItem) $source[] = "purchase #{$purchaseItem->purchase_id} (cost: {$purchaseItem->unit_price})";
            if (empty($source)) $source[] = 'no history found';

            $rows[] = [
                $product->id,
                mb_substr($product->name, 0, 35),
                $product->barcode ?? '-',
                number_format($sellPrice, 2),
                number_format($costPrice, 2),
                implode(', ', $source),
            ];
        }

        $this->table(
            ['ID', 'Name', 'Barcode', 'Sell Price', 'Cost Price', 'Source'],
            $rows
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run - no changes made.');
            return 0;
        }

        if (!$this->confirm('Create missing units for these products?')) {
            return 0;
        }

        DB::beginTransaction();

        try {
            $fixed = 0;

            foreach ($products as $product) {
                $saleItem = SaleItem::where('product_id', $product->id)
                    ->whereNotNull('unit_price')
                    ->where('unit_price', '>', 0)
                    ->latest('id')
                    ->first();

                $purchaseItem = PurchaseItem::where('product_id', $product->id)
                    ->whereNotNull('unit_price')
                    ->where('unit_price', '>', 0)
                    ->latest('id')
                    ->first();

                $sellPrice = $saleItem->unit_price ?? 0;
                $costPrice = $purchaseItem->unit_price ?? $saleItem->cost_at_sale ?? 0;

                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_id' => 1,
                    'multiplier' => 1,
                    'sell_price' => $sellPrice,
                    'cost_price' => $costPrice,
                    'is_base_unit' => true,
                ]);

                $product->touch();

                $fixed++;
                $this->line("  Fixed: [{$product->id}] {$product->name} (sell: {$sellPrice}, cost: {$costPrice})");
            }

            DB::commit();
            $this->newLine();
            $this->info("Fixed {$fixed} products.");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed: " . $e->getMessage());
            return 1;
        }
    }
}
