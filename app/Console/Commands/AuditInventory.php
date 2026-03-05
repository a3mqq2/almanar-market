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
        $this->auditBatchVsMovements($products);
        $this->auditMovementChain($products);
        $this->auditOrphanedMovements();

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
            $this->line('   OK - No negative batches');
            return;
        }

        $this->warn("   FOUND {$negativeBatches->count()} negative batches");

        foreach ($negativeBatches as $batch) {
            $this->issues[] = [
                'type' => 'negative_batch',
                'product_id' => $batch->product_id,
                'product_name' => $batch->product?->name ?? '??',
                'batch_id' => $batch->id,
                'current_qty' => $batch->quantity,
                'expected_qty' => 0,
                'diff' => abs($batch->quantity),
                'detail' => "Batch #{$batch->id} has negative quantity: {$batch->quantity}",
            ];
        }
    }

    protected function auditBatchVsMovements($products): void
    {
        $this->info('2. Checking batch quantities vs stock movements...');

        $discrepancies = 0;

        foreach ($products as $product) {
            $batches = InventoryBatch::where('product_id', $product->id)->get();

            foreach ($batches as $batch) {
                $movements = StockMovement::where('batch_id', $batch->id)
                    ->orderBy('id')
                    ->get();

                if ($movements->isEmpty()) {
                    if (abs($batch->quantity) > 0.001) {
                        $discrepancies++;
                        $this->issues[] = [
                            'type' => 'batch_no_movements',
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'batch_id' => $batch->id,
                            'current_qty' => $batch->quantity,
                            'expected_qty' => 0,
                            'diff' => $batch->quantity,
                            'detail' => "Batch #{$batch->id} has qty={$batch->quantity} but no movements",
                        ];
                    }
                    continue;
                }

                $calculatedQty = $movements->sum('quantity');

                if (abs($calculatedQty - (float)$batch->quantity) > 0.001) {
                    $discrepancies++;
                    $this->issues[] = [
                        'type' => 'batch_movement_mismatch',
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'batch_id' => $batch->id,
                        'current_qty' => (float)$batch->quantity,
                        'expected_qty' => $calculatedQty,
                        'diff' => round((float)$batch->quantity - $calculatedQty, 4),
                        'detail' => "Batch #{$batch->id}: qty={$batch->quantity}, movements sum={$calculatedQty}",
                    ];
                }
            }

            $totalBatchQty = $batches->sum('quantity');
            $totalMovementQty = StockMovement::where('product_id', $product->id)
                ->sum('quantity');

            if (abs((float)$totalBatchQty - (float)$totalMovementQty) > 0.001) {
                $discrepancies++;
                $this->issues[] = [
                    'type' => 'product_total_mismatch',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'batch_id' => null,
                    'current_qty' => (float)$totalBatchQty,
                    'expected_qty' => (float)$totalMovementQty,
                    'diff' => round((float)$totalBatchQty - (float)$totalMovementQty, 4),
                    'detail' => "Product total: batches={$totalBatchQty}, movements={$totalMovementQty}",
                ];
            }
        }

        if ($discrepancies === 0) {
            $this->line('   OK - All batches match movements');
        } else {
            $this->warn("   FOUND {$discrepancies} discrepancies");
        }
    }

    protected function auditMovementChain($products): void
    {
        $this->info('3. Checking movement chain integrity (before/after)...');

        $broken = 0;

        foreach ($products as $product) {
            $batches = InventoryBatch::where('product_id', $product->id)->get();

            foreach ($batches as $batch) {
                $movements = StockMovement::where('batch_id', $batch->id)
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
                                'batch_id' => $batch->id,
                                'current_qty' => $actualBefore,
                                'expected_qty' => $expectedBefore,
                                'diff' => round($actualBefore - $expectedBefore, 4),
                                'detail' => "Movement #{$movement->id}: before_qty={$actualBefore}, expected={$expectedBefore} (after movement #{$prev->id})",
                            ];
                        }
                    }
                    $prev = $movement;
                }

                if ($prev) {
                    $expectedFinal = (float)$prev->after_quantity;
                    $actualFinal = (float)$batch->quantity;

                    if (abs($expectedFinal - $actualFinal) > 0.001) {
                        $broken++;
                        $this->issues[] = [
                            'type' => 'final_mismatch',
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'batch_id' => $batch->id,
                            'current_qty' => $actualFinal,
                            'expected_qty' => $expectedFinal,
                            'diff' => round($actualFinal - $expectedFinal, 4),
                            'detail' => "Batch #{$batch->id}: final qty={$actualFinal}, last movement after_qty={$expectedFinal}",
                        ];
                    }
                }
            }
        }

        if ($broken === 0) {
            $this->line('   OK - All movement chains are consistent');
        } else {
            $this->warn("   FOUND {$broken} broken chain links");
        }
    }

    protected function auditOrphanedMovements(): void
    {
        $this->info('4. Checking for orphaned movements (missing batch)...');

        $orphaned = StockMovement::whereNull('batch_id')->count();

        if ($orphaned === 0) {
            $this->line('   OK - No orphaned movements');
        } else {
            $this->warn("   FOUND {$orphaned} orphaned movements (batch_id = NULL)");

            $byProduct = StockMovement::whereNull('batch_id')
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
                    'detail' => "{$row->cnt} orphaned movements, total qty={$row->total_qty}",
                ];
            }
        }
    }

    protected function showSummary(): void
    {
        if (empty($this->issues)) {
            $this->info('=== AUDIT PASSED - No issues found ===');
            return;
        }

        $this->error("=== AUDIT FOUND " . count($this->issues) . " ISSUES ===");
        $this->newLine();

        $grouped = collect($this->issues)->groupBy('type');

        foreach ($grouped as $type => $items) {
            $label = match ($type) {
                'negative_batch' => 'Negative Batches',
                'batch_no_movements' => 'Batches Without Movements',
                'batch_movement_mismatch' => 'Batch vs Movement Mismatch',
                'product_total_mismatch' => 'Product Total Mismatch',
                'broken_chain' => 'Broken Movement Chain',
                'final_mismatch' => 'Final Quantity Mismatch',
                'orphaned_movements' => 'Orphaned Movements',
                default => $type,
            };

            $this->warn("{$label}: {$items->count()}");
        }

        $this->newLine();

        $rows = collect($this->issues)
            ->unique(fn($i) => $i['product_id'] . '-' . $i['batch_id'] . '-' . $i['type'])
            ->sortByDesc(fn($i) => abs($i['diff']))
            ->take(50)
            ->map(fn($i) => [
                $i['product_id'],
                mb_substr($i['product_name'], 0, 35),
                $i['batch_id'] ?? '-',
                $i['type'],
                number_format($i['current_qty'], 4),
                number_format($i['expected_qty'], 4),
                ($i['diff'] >= 0 ? '+' : '') . number_format($i['diff'], 4),
            ])
            ->toArray();

        $this->table(
            ['Product', 'Name', 'Batch', 'Issue', 'Current', 'Expected', 'Diff'],
            $rows
        );

        if (count($this->issues) > 50) {
            $this->warn("... showing top 50 of " . count($this->issues) . " issues (sorted by diff)");
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
        $this->info("Report exported to: {$path}");
    }

    protected function fixDiscrepancies(): int
    {
        $fixable = collect($this->issues)->whereIn('type', [
            'batch_movement_mismatch',
            'final_mismatch',
        ]);

        if ($fixable->isEmpty()) {
            $this->warn('No auto-fixable issues found. Manual review needed for other issue types.');
            return 1;
        }

        $batchFixes = $fixable->where('type', 'batch_movement_mismatch')
            ->unique('batch_id');
        $finalFixes = $fixable->where('type', 'final_mismatch')
            ->unique('batch_id');

        $totalFixes = $batchFixes->merge($finalFixes)->unique('batch_id');

        if (!$this->confirm("Fix {$totalFixes->count()} batch quantity discrepancies by creating adjustment movements?")) {
            return 0;
        }

        DB::beginTransaction();

        try {
            $fixed = 0;

            foreach ($totalFixes as $issue) {
                $batch = InventoryBatch::find($issue['batch_id']);
                if (!$batch) continue;

                $movementSum = StockMovement::where('batch_id', $batch->id)->sum('quantity');
                $currentQty = (float)$batch->quantity;
                $diff = round($currentQty - (float)$movementSum, 4);

                if (abs($diff) < 0.001) continue;

                StockMovement::create([
                    'product_id' => $batch->product_id,
                    'batch_id' => $batch->id,
                    'type' => 'adjustment',
                    'quantity' => $diff,
                    'before_quantity' => $movementSum,
                    'after_quantity' => $currentQty,
                    'cost_price' => $batch->cost_price,
                    'reference' => 'AUDIT-' . now()->format('Ymd'),
                    'reason' => 'تسوية تدقيق المخزون',
                    'notes' => "Audit fix: movement sum={$movementSum}, batch qty={$currentQty}",
                ]);

                $fixed++;
            }

            DB::commit();
            $this->info("Fixed {$fixed} batch discrepancies with adjustment movements.");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Fix failed: " . $e->getMessage());
            return 1;
        }
    }
}
