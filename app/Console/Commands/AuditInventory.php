<?php

namespace App\Console\Commands;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditInventory extends Command
{
    protected $signature = 'inventory:audit
        {--product= : Audit specific product ID}
        {--fix : Auto-fix discrepancies by creating adjustment movements}
        {--export= : Export report to CSV file path}';

    protected $description = 'Audit inventory: compare batch quantities against stock movements and find discrepancies';

    protected array $issues = [];

    public function handle(): int
    {
        $productId = $this->option('product');

        if ($productId) {
            $products = Product::where('id', $productId)->get();
        } else {
            $products = Product::all();
        }

        $this->info("Auditing {$products->count()} products...");
        $this->newLine();

        $this->auditNegativeBatches($productId);
        $this->auditProductTotals($products);
        $this->auditMovementChain($products);
        $this->auditOrphanedMovements($productId);

        $this->newLine();
        $this->showSummary();

        if ($this->option('export')) {
            $this->exportCsv($this->option('export'));
        }

        if ($this->option('fix') && !empty($this->issues)) {
            return $this->fixDiscrepancies();
        }

        return empty($this->issues) ? 0 : 1;
    }

    protected function auditNegativeBatches(?string $productId): void
    {
        $this->info('1. Checking for negative batch quantities...');

        $query = InventoryBatch::where('quantity', '<', 0);
        if ($productId) {
            $query->where('product_id', $productId);
        }

        $negativeBatches = $query->with('product')->get();

        if ($negativeBatches->isEmpty()) {
            $this->line('   OK');
            return;
        }

        $this->warn("   FOUND {$negativeBatches->count()} negative batches");

        foreach ($negativeBatches as $batch) {
            $this->issues[] = [
                'type' => 'negative_batch',
                'product_id' => $batch->product_id,
                'product_name' => $batch->product?->name ?? '??',
                'batch_id' => $batch->id,
                'current_qty' => (float)$batch->quantity,
                'expected_qty' => 0,
                'diff' => abs((float)$batch->quantity),
                'detail' => "Batch #{$batch->id} has negative quantity: {$batch->quantity}",
            ];
        }
    }

    protected function auditProductTotals($products): void
    {
        $this->info('2. Checking product totals (batch sum vs movement sum)...');

        $discrepancies = 0;

        foreach ($products as $product) {
            $totalBatchQty = (float)InventoryBatch::where('product_id', $product->id)->sum('quantity');
            $totalMovementQty = (float)StockMovement::where('product_id', $product->id)->sum('quantity');

            if (abs($totalBatchQty - $totalMovementQty) > 0.001) {
                $discrepancies++;
                $this->issues[] = [
                    'type' => 'product_total_mismatch',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'batch_id' => null,
                    'current_qty' => $totalBatchQty,
                    'expected_qty' => $totalMovementQty,
                    'diff' => round($totalBatchQty - $totalMovementQty, 4),
                    'detail' => "Batches sum={$totalBatchQty}, Movements sum={$totalMovementQty}",
                ];
            }
        }

        if ($discrepancies === 0) {
            $this->line('   OK');
        } else {
            $this->warn("   FOUND {$discrepancies} product total mismatches");
        }
    }

    protected function auditMovementChain($products): void
    {
        $this->info('3. Checking movement chain (before/after at product level)...');

        $broken = 0;

        foreach ($products as $product) {
            $movements = StockMovement::where('product_id', $product->id)
                ->orderBy('id')
                ->get();

            if ($movements->count() < 2) continue;

            $prev = null;
            foreach ($movements as $movement) {
                if ($prev !== null) {
                    $expectedBefore = (float)$prev->after_quantity;
                    $actualBefore = (float)$movement->before_quantity;

                    if (abs($expectedBefore - $actualBefore) > 0.001) {
                        $broken++;
                        $this->issues[] = [
                            'type' => 'broken_chain',
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'batch_id' => $movement->batch_id,
                            'current_qty' => $actualBefore,
                            'expected_qty' => $expectedBefore,
                            'diff' => round($actualBefore - $expectedBefore, 4),
                            'detail' => "Movement #{$movement->id} ({$movement->type}): before={$actualBefore}, expected={$expectedBefore} (prev #{$prev->id})",
                        ];
                    }
                }
                $prev = $movement;
            }

            if ($prev) {
                $totalBatchQty = (float)InventoryBatch::where('product_id', $product->id)->sum('quantity');
                $lastAfter = (float)$prev->after_quantity;

                if (abs($lastAfter - $totalBatchQty) > 0.001) {
                    $broken++;
                    $this->issues[] = [
                        'type' => 'final_mismatch',
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'batch_id' => null,
                        'current_qty' => $totalBatchQty,
                        'expected_qty' => $lastAfter,
                        'diff' => round($totalBatchQty - $lastAfter, 4),
                        'detail' => "Last movement after_qty={$lastAfter}, actual batch total={$totalBatchQty}",
                    ];
                }
            }
        }

        if ($broken === 0) {
            $this->line('   OK');
        } else {
            $this->warn("   FOUND {$broken} chain issues");
        }
    }

    protected function auditOrphanedMovements(?string $productId): void
    {
        $this->info('4. Checking for orphaned movements (missing batch)...');

        $query = StockMovement::whereNull('batch_id');
        if ($productId) {
            $query->where('product_id', $productId);
        }

        $orphaned = $query->count();

        if ($orphaned === 0) {
            $this->line('   OK');
        } else {
            $this->warn("   FOUND {$orphaned} orphaned movements (batch_id = NULL)");

            $byProduct = StockMovement::whereNull('batch_id')
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->select('product_id', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(quantity) as total_qty'))
                ->groupBy('product_id')
                ->get();

            foreach ($byProduct as $row) {
                $name = Product::find($row->product_id)?->name ?? '??';
                $this->issues[] = [
                    'type' => 'orphaned_movements',
                    'product_id' => $row->product_id,
                    'product_name' => $name,
                    'batch_id' => null,
                    'current_qty' => 0,
                    'expected_qty' => (float)$row->total_qty,
                    'diff' => (float)$row->total_qty,
                    'detail' => "{$row->cnt} movements without batch, total qty={$row->total_qty}",
                ];
            }
        }
    }

    protected function showSummary(): void
    {
        if (empty($this->issues)) {
            $this->info('=== AUDIT PASSED ===');
            return;
        }

        $this->error("=== AUDIT FOUND " . count($this->issues) . " ISSUES ===");
        $this->newLine();

        $grouped = collect($this->issues)->groupBy('type');

        foreach ($grouped as $type => $items) {
            $label = match ($type) {
                'negative_batch' => 'Negative Batches',
                'product_total_mismatch' => 'Product Total Mismatch (batches vs movements)',
                'broken_chain' => 'Broken Movement Chain',
                'final_mismatch' => 'Final Quantity Mismatch',
                'orphaned_movements' => 'Orphaned Movements',
                default => $type,
            };

            $this->warn("{$label}: {$items->count()}");
        }

        $this->newLine();

        $rows = collect($this->issues)
            ->sortByDesc(fn($i) => abs($i['diff']))
            ->take(50)
            ->map(fn($i) => [
                $i['product_id'],
                mb_substr($i['product_name'], 0, 35),
                $i['batch_id'] ?? '-',
                $i['type'],
                number_format($i['current_qty'], 2),
                number_format($i['expected_qty'], 2),
                ($i['diff'] >= 0 ? '+' : '') . number_format($i['diff'], 2),
            ])
            ->toArray();

        $this->table(
            ['Product', 'Name', 'Batch', 'Issue', 'Current', 'Expected', 'Diff'],
            $rows
        );

        if (count($this->issues) > 50) {
            $this->warn("... showing top 50 of " . count($this->issues) . " issues");
        }
    }

    protected function exportCsv(string $path): void
    {
        $fp = fopen($path, 'w');
        fputcsv($fp, ['product_id', 'product_name', 'batch_id', 'type', 'current_qty', 'expected_qty', 'diff', 'detail']);

        foreach ($this->issues as $issue) {
            fputcsv($fp, [
                $issue['product_id'],
                $issue['product_name'],
                $issue['batch_id'] ?? '',
                $issue['type'],
                $issue['current_qty'],
                $issue['expected_qty'],
                $issue['diff'],
                $issue['detail'],
            ]);
        }

        fclose($fp);
        $this->info("Exported: {$path}");
    }

    protected function fixDiscrepancies(): int
    {
        $fixable = collect($this->issues)->where('type', 'product_total_mismatch');

        if ($fixable->isEmpty()) {
            $this->warn('No auto-fixable product total mismatches found.');
            return 1;
        }

        if (!$this->confirm("Fix {$fixable->count()} product total discrepancies by creating adjustment movements?")) {
            return 0;
        }

        DB::beginTransaction();

        try {
            $fixed = 0;

            foreach ($fixable as $issue) {
                $product = Product::find($issue['product_id']);
                if (!$product) continue;

                $batchQty = (float)InventoryBatch::where('product_id', $product->id)->sum('quantity');
                $movementQty = (float)StockMovement::where('product_id', $product->id)->sum('quantity');
                $diff = round($batchQty - $movementQty, 4);

                if (abs($diff) < 0.001) continue;

                $batch = InventoryBatch::where('product_id', $product->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('id', 'desc')
                    ->first();

                if (!$batch) {
                    $batch = InventoryBatch::where('product_id', $product->id)
                        ->orderBy('id', 'desc')
                        ->first();
                }

                if (!$batch) continue;

                StockMovement::create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'type' => 'adjustment',
                    'quantity' => $diff,
                    'before_quantity' => $movementQty,
                    'after_quantity' => $batchQty,
                    'cost_price' => $batch->cost_price,
                    'reference' => 'AUDIT-' . now()->format('Ymd'),
                    'reason' => 'تسوية تدقيق المخزون',
                ]);

                $fixed++;
            }

            DB::commit();
            $this->info("Fixed {$fixed} product total discrepancies.");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Fix failed: " . $e->getMessage());
            return 1;
        }
    }
}
