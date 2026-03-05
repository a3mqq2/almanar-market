<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use App\Models\InventoryBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CompareProducts extends Command
{
    protected $signature = 'sync:compare-products
        {--device= : Device ID to use for API auth}
        {--push : Push missing products to production}
        {--pull : Pull missing products from production}
        {--sync-batches : Sync missing/different batches for existing products}
        {--product= : Compare specific product ID only}
        {--dry-run : Show what would be synced without syncing}';

    protected $description = 'Compare local products with production server and find missing ones';

    protected string $serverUrl;
    protected string $apiToken;
    protected string $deviceId;
    protected array $remoteUnits = [];
    protected array $remoteBarcodes = [];
    protected array $remoteBatches = [];

    public function handle(): int
    {
        $this->serverUrl = rtrim(config('desktop.server_url'), '/');

        $device = $this->resolveDevice();
        if (!$device) {
            return 1;
        }

        $this->deviceId = $device->device_id;
        $this->apiToken = $device->api_token;

        $this->info("Server: {$this->serverUrl}");
        $this->info("Device: {$device->device_name} ({$this->deviceId})");
        $this->newLine();

        $this->info('Fetching data from production...');
        $remoteProducts = $this->fetchRemoteData();

        if ($remoteProducts === null) {
            return 1;
        }

        $this->info("Remote products: " . count($remoteProducts));
        $this->info("Remote units: " . count($this->remoteUnits));
        $this->info("Remote barcodes: " . count($this->remoteBarcodes));
        $this->info("Remote batches: " . count($this->remoteBatches));

        $localProducts = Product::with(['productUnits', 'barcodes', 'inventoryBatches'])->get();
        $this->info("Local products: " . $localProducts->count());
        $this->newLine();

        $remoteIds = collect($remoteProducts)->pluck('id')->toArray();
        $remoteBarcodesArr = collect($remoteProducts)->pluck('barcode')->filter()->toArray();
        $remoteNames = collect($remoteProducts)->pluck('name')->filter()->toArray();

        $missingFromRemote = $localProducts->filter(fn($p) => !in_array($p->id, $remoteIds))
            ->filter(function ($p) use ($remoteBarcodesArr) {
                return !($p->barcode && in_array($p->barcode, $remoteBarcodesArr));
            })
            ->filter(function ($p) use ($remoteNames) {
                return !in_array($p->name, $remoteNames);
            });

        $localIds = $localProducts->pluck('id')->toArray();
        $localBarcodesArr = $localProducts->pluck('barcode')->filter()->toArray();
        $localNames = $localProducts->pluck('name')->filter()->toArray();

        $missingFromLocal = collect($remoteProducts)->filter(function ($rp) use ($localIds, $localBarcodesArr, $localNames) {
            if (in_array($rp['id'], $localIds)) return false;
            if (!empty($rp['barcode']) && in_array($rp['barcode'], $localBarcodesArr)) return false;
            if (!empty($rp['name']) && in_array($rp['name'], $localNames)) return false;
            return true;
        });

        $this->showResults($missingFromRemote, $missingFromLocal);

        $batchDiffs = $this->compareBatches($localProducts, $remoteProducts);

        if ($this->option('push') && $missingFromRemote->isNotEmpty()) {
            return $this->pushMissingProducts($missingFromRemote);
        }

        if ($this->option('pull') && $missingFromLocal->isNotEmpty()) {
            return $this->pullMissingProducts($missingFromLocal);
        }

        if ($this->option('sync-batches') && !empty($batchDiffs)) {
            return $this->syncBatchDiffs($batchDiffs);
        }

        if ($this->option('dry-run')) {
            if ($missingFromRemote->isNotEmpty()) {
                $this->showPushPreview($missingFromRemote);
            }
            if ($missingFromLocal->isNotEmpty()) {
                $this->showPullPreview($missingFromLocal);
            }
        }

        return 0;
    }

    protected function resolveDevice(): ?object
    {
        $deviceId = $this->option('device');

        if ($deviceId) {
            $device = DB::table('device_registrations')
                ->where('device_id', $deviceId)
                ->where('status', 'active')
                ->first();
        } else {
            $device = DB::table('device_registrations')
                ->where('status', 'active')
                ->whereNotNull('api_token')
                ->where('api_token', '!=', '')
                ->orderBy('last_seen_at', 'desc')
                ->first();
        }

        if (!$device) {
            $this->error('No active device found with API token.');
            return null;
        }

        if (empty($device->api_token)) {
            $this->error("Device {$device->device_name} has no API token.");
            return null;
        }

        return $device;
    }

    protected function fetchRemoteData(): ?array
    {
        $products = [];
        $this->remoteUnits = [];
        $this->remoteBarcodes = [];
        $this->remoteBatches = [];

        $offsetModel = null;
        $offsetId = 0;
        $page = 0;

        $targetTypes = [
            'App\\Models\\Product' => 'products',
            'App\\Models\\ProductUnit' => 'units',
            'App\\Models\\ProductBarcode' => 'barcodes',
            'App\\Models\\InventoryBatch' => 'batches',
        ];

        do {
            $page++;
            $params = [
                'device_id' => $this->deviceId,
                'limit' => 2000,
            ];

            if ($offsetModel) {
                $params['offset_model'] = $offsetModel;
                $params['offset_id'] = $offsetId;
            }

            try {
                $response = Http::timeout(30)
                    ->withToken($this->apiToken)
                    ->get("{$this->serverUrl}/api/v1/sync/pull", $params);

                if (!$response->successful()) {
                    $this->error("API Error (HTTP {$response->status()}): " . substr($response->body(), 0, 300));
                    return null;
                }

                $data = $response->json();

                if (!($data['success'] ?? false)) {
                    $this->error('API returned error: ' . json_encode($data));
                    return null;
                }

                $passedTargetModels = false;

                foreach ($data['changes'] ?? [] as $change) {
                    $type = $change['type'] ?? '';

                    if (isset($targetTypes[$type])) {
                        $passedTargetModels = true;
                        $bucket = $targetTypes[$type];
                        if ($bucket === 'products') {
                            $products[] = $change['payload'];
                        } elseif ($bucket === 'units') {
                            $this->remoteUnits[] = $change['payload'];
                        } elseif ($bucket === 'barcodes') {
                            $this->remoteBarcodes[] = $change['payload'];
                        } elseif ($bucket === 'batches') {
                            $this->remoteBatches[] = $change['payload'];
                        }
                    } elseif ($passedTargetModels) {
                        break;
                    }
                }

                $hasMore = $data['has_more'] ?? false;
                $offsetModel = $data['next_offset_model'] ?? null;
                $offsetId = $data['next_offset_id'] ?? 0;

                $this->output->write("\r  Page {$page}: products=" . count($products)
                    . " units=" . count($this->remoteUnits)
                    . " barcodes=" . count($this->remoteBarcodes)
                    . " batches=" . count($this->remoteBatches) . "   ");

                if ($offsetModel && !isset($targetTypes[$offsetModel])) {
                    $hasMore = false;
                }

            } catch (\Exception $e) {
                $this->error("Connection error: " . $e->getMessage());
                return null;
            }
        } while ($hasMore);

        $this->newLine();

        return $products;
    }

    protected function showResults($missingFromRemote, $missingFromLocal): void
    {
        $this->info('=== Comparison Results ===');
        $this->newLine();

        $this->info("Missing from production (local only): {$missingFromRemote->count()}");

        if ($missingFromRemote->isNotEmpty()) {
            $rows = $missingFromRemote->map(function ($p) {
                return [
                    $p->id,
                    $p->name,
                    $p->barcode ?? '-',
                    $p->productUnits->count(),
                    $p->barcodes->count(),
                    $p->inventoryBatches->count(),
                    $p->created_at->format('Y-m-d'),
                ];
            })->toArray();

            $this->table(
                ['ID', 'Name', 'Barcode', 'Units', 'Barcodes', 'Batches', 'Created'],
                $rows
            );
        }

        $this->newLine();
        $this->info("Missing locally (production only): {$missingFromLocal->count()}");

        if ($missingFromLocal->isNotEmpty()) {
            $remoteUnitsByProduct = collect($this->remoteUnits)->groupBy('product_id');
            $remoteBarcodesByProduct = collect($this->remoteBarcodes)->groupBy('product_id');
            $remoteBatchesByProduct = collect($this->remoteBatches)->groupBy('product_id');

            $rows = $missingFromLocal->map(function ($rp) use ($remoteUnitsByProduct, $remoteBarcodesByProduct, $remoteBatchesByProduct) {
                $pid = $rp['id'];
                return [
                    $pid,
                    $rp['name'] ?? '-',
                    $rp['barcode'] ?? '-',
                    $remoteUnitsByProduct->get($pid, collect())->count(),
                    $remoteBarcodesByProduct->get($pid, collect())->count(),
                    $remoteBatchesByProduct->get($pid, collect())->count(),
                    substr($rp['created_at'] ?? '-', 0, 10),
                ];
            })->toArray();

            $this->table(
                ['ID', 'Name', 'Barcode', 'Units', 'Barcodes', 'Batches', 'Created'],
                $rows
            );
        }
    }

    protected function showPushPreview($missingProducts): void
    {
        $this->newLine();
        $this->warn('=== DRY RUN - Would PUSH these products ===');

        foreach ($missingProducts as $product) {
            $this->line("  [{$product->id}] {$product->name}");
            $this->line("    Units: " . $product->productUnits->count());
            $this->line("    Barcodes: " . $product->barcodes->pluck('barcode')->implode(', '));
            $this->line("    Batches: " . $product->inventoryBatches->count());
        }
    }

    protected function showPullPreview($missingProducts): void
    {
        $this->newLine();
        $this->warn('=== DRY RUN - Would PULL these products ===');

        $remoteUnitsByProduct = collect($this->remoteUnits)->groupBy('product_id');
        $remoteBarcodesByProduct = collect($this->remoteBarcodes)->groupBy('product_id');
        $remoteBatchesByProduct = collect($this->remoteBatches)->groupBy('product_id');

        foreach ($missingProducts as $rp) {
            $pid = $rp['id'];
            $units = $remoteUnitsByProduct->get($pid, collect());
            $barcodes = $remoteBarcodesByProduct->get($pid, collect());
            $batches = $remoteBatchesByProduct->get($pid, collect());

            $this->line("  [{$pid}] {$rp['name']}");
            $this->line("    Units: {$units->count()}" . ($units->isNotEmpty() ? " (" . $units->pluck('unit_id')->implode(', ') . ")" : ""));
            $this->line("    Barcodes: {$barcodes->count()}" . ($barcodes->isNotEmpty() ? " (" . $barcodes->pluck('barcode')->implode(', ') . ")" : ""));
            $this->line("    Batches: {$batches->count()}");
        }
    }

    protected function compareBatches($localProducts, array $remoteProducts): array
    {
        $this->newLine();
        $this->info('=== Batch Comparison (existing products) ===');

        $productFilter = $this->option('product');
        $remoteBatchesByProduct = collect($this->remoteBatches)->groupBy('product_id');
        $remoteProductIds = collect($remoteProducts)->pluck('id')->toArray();
        $diffs = [];

        foreach ($localProducts as $localProduct) {
            if ($productFilter && $localProduct->id != $productFilter) continue;
            if (!in_array($localProduct->id, $remoteProductIds)) continue;

            $localBatches = $localProduct->inventoryBatches;
            $remoteBatches = $remoteBatchesByProduct->get($localProduct->id, collect());

            $matchedRemoteIds = [];
            $matchedLocalIds = [];

            foreach ($localBatches as $lb) {
                $rb = $remoteBatches->first(function ($b) use ($lb, $matchedRemoteIds) {
                    if (in_array($b['id'], $matchedRemoteIds)) return false;
                    return $b['id'] == $lb->id;
                });

                if (!$rb && $lb->batch_number) {
                    $rb = $remoteBatches->first(function ($b) use ($lb, $matchedRemoteIds) {
                        if (in_array($b['id'], $matchedRemoteIds)) return false;
                        return ($b['batch_number'] ?? '') === $lb->batch_number;
                    });
                }

                if ($rb) {
                    $matchedRemoteIds[] = $rb['id'];
                    $matchedLocalIds[] = $lb->id;
                }
            }

            $missingLocally = $remoteBatches->filter(fn($b) => !in_array($b['id'], $matchedRemoteIds));
            $missingRemotely = $localBatches->filter(fn($b) => !in_array($b->id, $matchedLocalIds));

            $qtyDiffs = collect();
            foreach ($localBatches as $lb) {
                if (!in_array($lb->id, $matchedLocalIds)) continue;

                $rb = $remoteBatches->first(function ($b) use ($lb) {
                    return $b['id'] == $lb->id;
                });
                if (!$rb && $lb->batch_number) {
                    $rb = $remoteBatches->first(function ($b) use ($lb) {
                        return ($b['batch_number'] ?? '') === $lb->batch_number;
                    });
                }

                if ($rb && abs((float)$lb->quantity - (float)$rb['quantity']) > 0.001) {
                    $qtyDiffs->push([
                        'batch_id' => $lb->id,
                        'batch_number' => $lb->batch_number,
                        'local_qty' => (float)$lb->quantity,
                        'remote_qty' => (float)$rb['quantity'],
                        'remote_data' => $rb,
                    ]);
                }
            }

            if ($missingLocally->isEmpty() && $missingRemotely->isEmpty() && $qtyDiffs->isEmpty()) {
                continue;
            }

            $diffs[$localProduct->id] = [
                'product' => $localProduct,
                'missing_locally' => $missingLocally,
                'missing_remotely' => $missingRemotely,
                'qty_diffs' => $qtyDiffs,
            ];
        }

        if (empty($diffs)) {
            $this->line('   All batches match.');
            return $diffs;
        }

        $this->warn("   Found " . count($diffs) . " products with batch differences");
        $this->newLine();

        $rows = [];
        foreach ($diffs as $pid => $diff) {
            $name = mb_substr($diff['product']->name, 0, 30);

            foreach ($diff['missing_locally'] as $b) {
                $rows[] = [$pid, $name, $b['id'], $b['batch_number'] ?? '-', '-', number_format((float)$b['quantity'], 2), 'باتش ناقص محلياً'];
            }

            foreach ($diff['missing_remotely'] as $b) {
                $rows[] = [$pid, $name, $b->id, $b->batch_number ?? '-', number_format((float)$b->quantity, 2), '-', 'باتش ناقص من السيرفر'];
            }

            foreach ($diff['qty_diffs'] as $d) {
                $rows[] = [$pid, $name, $d['batch_id'], $d['batch_number'] ?? '-', number_format($d['local_qty'], 2), number_format($d['remote_qty'], 2), 'فرق كمية'];
            }
        }

        $this->table(
            ['Product', 'Name', 'Batch ID', 'Batch #', 'Local Qty', 'Remote Qty', 'Issue'],
            $rows
        );

        if (!$this->option('sync-batches')) {
            $this->newLine();
            $this->line('Use --sync-batches to pull missing batches and fix quantity differences from server.');
        }

        return $diffs;
    }

    protected function syncBatchDiffs(array $diffs): int
    {
        $totalOps = 0;
        foreach ($diffs as $diff) {
            $totalOps += $diff['missing_locally']->count() + $diff['qty_diffs']->count();
        }

        if ($totalOps === 0) {
            $this->info('Nothing to sync.');
            return 0;
        }

        if (!$this->confirm("Sync {$totalOps} batch changes from server to local?")) {
            return 0;
        }

        $syncFields = ['synced_at', 'sync_version', 'local_uuid', 'device_id'];

        DB::beginTransaction();

        try {
            $created = 0;
            $updated = 0;

            foreach ($diffs as $diff) {
                foreach ($diff['missing_locally'] as $batchData) {
                    $batchData = collect($batchData)->except($syncFields)->toArray();
                    InventoryBatch::updateOrCreate(
                        ['id' => $batchData['id']],
                        $batchData
                    );
                    $created++;
                }

                foreach ($diff['qty_diffs'] as $d) {
                    $batch = InventoryBatch::find($d['batch_id']);
                    if ($batch) {
                        $batch->update(['quantity' => $d['remote_qty']]);
                        $updated++;
                    }
                }
            }

            DB::commit();
            $this->info("Created {$created} missing batches, updated {$updated} batch quantities.");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Sync failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function pullMissingProducts($missingProducts): int
    {
        $count = $missingProducts->count();

        if (!$this->confirm("Pull {$count} products with relationships from production to local?")) {
            return 0;
        }

        $remoteUnitsByProduct = collect($this->remoteUnits)->groupBy('product_id');
        $remoteBarcodesByProduct = collect($this->remoteBarcodes)->groupBy('product_id');
        $remoteBatchesByProduct = collect($this->remoteBatches)->groupBy('product_id');

        $syncFields = ['synced_at', 'sync_version', 'local_uuid', 'device_id'];

        $created = 0;
        $errors = 0;

        DB::beginTransaction();

        try {
            foreach ($missingProducts as $rp) {
                $pid = $rp['id'];

                $productData = collect($rp)->except(array_merge(
                    $syncFields,
                    ['product_units', 'active_barcodes', 'base_unit', 'inventory_batches', 'barcodes']
                ))->toArray();

                $product = Product::updateOrCreate(
                    ['id' => $pid],
                    $productData
                );

                $units = $remoteUnitsByProduct->get($pid, collect());
                foreach ($units as $unitData) {
                    $unitData = collect($unitData)->except($syncFields)->toArray();
                    ProductUnit::updateOrCreate(
                        ['id' => $unitData['id']],
                        $unitData
                    );
                }

                $barcodes = $remoteBarcodesByProduct->get($pid, collect());
                foreach ($barcodes as $barcodeData) {
                    $barcodeData = collect($barcodeData)->except($syncFields)->toArray();
                    ProductBarcode::updateOrCreate(
                        ['id' => $barcodeData['id']],
                        $barcodeData
                    );
                }

                $batches = $remoteBatchesByProduct->get($pid, collect());
                foreach ($batches as $batchData) {
                    $batchData = collect($batchData)->except($syncFields)->toArray();
                    InventoryBatch::updateOrCreate(
                        ['id' => $batchData['id']],
                        $batchData
                    );
                }

                $created++;
                $this->output->write("\r  Pulled: {$created}/{$count}");
            }

            DB::commit();
            $this->newLine();
            $this->info("Successfully pulled {$created} products with all relationships.");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error("Pull failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function pushMissingProducts($missingProducts): int
    {
        if (!$this->confirm("Push {$missingProducts->count()} products to production?")) {
            return 0;
        }

        $changes = [];

        foreach ($missingProducts as $product) {
            $payload = $product->toArray();
            $payload['product_units'] = $product->productUnits->toArray();
            $payload['barcodes'] = $product->barcodes->toArray();
            $payload['inventory_batches'] = $product->inventoryBatches->toArray();

            $changes[] = [
                'id' => $product->id,
                'type' => Product::class,
                'record_id' => $product->id,
                'action' => 'created',
                'payload' => $payload,
                'timestamp' => $product->updated_at->toIso8601String(),
            ];

            foreach ($product->productUnits as $unit) {
                $changes[] = [
                    'id' => $unit->id,
                    'type' => ProductUnit::class,
                    'record_id' => $unit->id,
                    'action' => 'created',
                    'payload' => $unit->toArray(),
                    'timestamp' => $unit->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                ];
            }

            foreach ($product->barcodes as $barcode) {
                $changes[] = [
                    'id' => $barcode->id,
                    'type' => ProductBarcode::class,
                    'record_id' => $barcode->id,
                    'action' => 'created',
                    'payload' => $barcode->toArray(),
                    'timestamp' => $barcode->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                ];
            }

            foreach ($product->inventoryBatches as $batch) {
                $changes[] = [
                    'id' => $batch->id,
                    'type' => InventoryBatch::class,
                    'record_id' => $batch->id,
                    'action' => 'created',
                    'payload' => $batch->toArray(),
                    'timestamp' => $batch->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                ];
            }
        }

        $this->info("Pushing " . count($changes) . " records...");

        try {
            $response = Http::timeout(60)
                ->withToken($this->apiToken)
                ->post("{$this->serverUrl}/api/v1/sync/push", [
                    'device_id' => $this->deviceId,
                    'changes' => $changes,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $synced = count($result['synced'] ?? []);
                $errors = count($result['errors'] ?? []);
                $conflicts = count($result['conflicts'] ?? []);

                $this->info("Synced: {$synced}");
                if ($conflicts > 0) $this->warn("Conflicts: {$conflicts}");
                if ($errors > 0) {
                    $this->error("Errors: {$errors}");
                    foreach ($result['errors'] ?? [] as $err) {
                        $this->error("  - " . ($err['message'] ?? json_encode($err)));
                    }
                }

                return $errors > 0 ? 1 : 0;
            }

            $this->error("Push failed (HTTP {$response->status()}): " . substr($response->body(), 0, 300));
            return 1;

        } catch (\Exception $e) {
            $this->error("Push error: " . $e->getMessage());
            return 1;
        }
    }
}
