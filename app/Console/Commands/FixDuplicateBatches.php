<?php

namespace App\Console\Commands;

use App\Models\InventoryBatch;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDuplicateBatches extends Command
{
    protected $signature = 'inventory:fix-duplicates
        {--product= : Fix specific product ID only}
        {--dry-run : Show duplicates without fixing}';

    protected $description = 'Find and merge duplicate inventory batches (same product_id + batch_number)';

    public function handle(): int
    {
        $productId = $this->option('product');

        $query = InventoryBatch::select('product_id', 'batch_number', DB::raw('COUNT(*) as cnt'), DB::raw('GROUP_CONCAT(id ORDER BY id) as batch_ids'))
            ->groupBy('product_id', 'batch_number')
            ->having('cnt', '>', 1);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $duplicates = $query->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate batches found.');
            return 0;
        }

        $this->warn("Found {$duplicates->count()} duplicate batch groups");
        $this->newLine();

        $rows = [];
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup->batch_ids);
            $batches = InventoryBatch::whereIn('id', $ids)->with('product')->orderBy('id')->get();

            $productName = $batches->first()?->product?->name ?? '??';
            $totalQty = $batches->sum('quantity');

            foreach ($batches as $i => $batch) {
                $movementCount = StockMovement::where('batch_id', $batch->id)->count();
                $rows[] = [
                    $batch->product_id,
                    mb_substr($productName, 0, 30),
                    $batch->id,
                    $batch->batch_number,
                    number_format((float)$batch->quantity, 2),
                    $movementCount,
                    $i === 0 ? 'KEEP' : 'MERGE',
                ];
            }
        }

        $this->table(
            ['Product', 'Name', 'Batch ID', 'Batch #', 'Qty', 'Movements', 'Action'],
            $rows
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run - no changes made. Remove --dry-run to fix.');
            return 0;
        }

        if (!$this->confirm("Merge {$duplicates->count()} duplicate batch groups? (keeps first batch, transfers movements, sums quantities)")) {
            return 0;
        }

        DB::beginTransaction();

        try {
            $merged = 0;
            $deleted = 0;

            foreach ($duplicates as $dup) {
                $ids = explode(',', $dup->batch_ids);
                $batches = InventoryBatch::whereIn('id', $ids)->orderBy('id')->get();

                $keepBatch = $batches->first();
                $duplicateBatches = $batches->slice(1);
                $extraQty = 0;

                foreach ($duplicateBatches as $dupBatch) {
                    StockMovement::where('batch_id', $dupBatch->id)
                        ->update(['batch_id' => $keepBatch->id]);

                    DB::table('sale_items')
                        ->where('inventory_batch_id', $dupBatch->id)
                        ->update(['inventory_batch_id' => $keepBatch->id]);

                    DB::table('purchase_items')
                        ->where('inventory_batch_id', $dupBatch->id)
                        ->update(['inventory_batch_id' => $keepBatch->id]);

                    DB::table('sale_return_items')
                        ->where('inventory_batch_id', $dupBatch->id)
                        ->update(['inventory_batch_id' => $keepBatch->id]);

                    $extraQty += (float)$dupBatch->quantity;
                    $dupBatch->delete();
                    $deleted++;
                }

                if ($extraQty > 0) {
                    $keepBatch->decrement('quantity', $extraQty);
                }

                $merged++;
            }

            DB::commit();
            $this->info("Merged {$merged} groups, deleted {$deleted} duplicate batches.");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Fix failed: " . $e->getMessage());
            return 1;
        }
    }
}
