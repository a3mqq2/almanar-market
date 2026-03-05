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
        {--dry-run : Show what would be pushed without pushing}';

    protected $description = 'Compare local products with production server and find missing ones';

    protected string $serverUrl;
    protected string $apiToken;
    protected string $deviceId;

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

        $this->info('Fetching products from production...');
        $remoteProducts = $this->fetchRemoteProducts();

        if ($remoteProducts === null) {
            return 1;
        }

        $this->info("Remote products found: " . count($remoteProducts));

        $localProducts = Product::with(['productUnits', 'barcodes', 'inventoryBatches'])->get();
        $this->info("Local products: " . $localProducts->count());
        $this->newLine();

        $remoteIds = collect($remoteProducts)->pluck('id')->toArray();
        $remoteBarcodes = collect($remoteProducts)->pluck('barcode')->filter()->toArray();
        $remoteNames = collect($remoteProducts)->pluck('name')->filter()->toArray();

        $missingById = $localProducts->filter(fn($p) => !in_array($p->id, $remoteIds));

        $missingByBarcode = $missingById->filter(function ($p) use ($remoteBarcodes) {
            if ($p->barcode && in_array($p->barcode, $remoteBarcodes)) {
                return false;
            }
            return true;
        });

        $missingByName = $missingByBarcode->filter(function ($p) use ($remoteNames) {
            return !in_array($p->name, $remoteNames);
        });

        $existRemoteNotLocal = collect($remoteProducts)->filter(function ($rp) use ($localProducts) {
            return !$localProducts->contains('id', $rp['id']);
        });

        $this->showResults($missingByName, $missingById, $existRemoteNotLocal, $localProducts, $remoteProducts);

        if ($this->option('push') && $missingByName->isNotEmpty()) {
            return $this->pushMissingProducts($missingByName);
        }

        if ($this->option('dry-run') && $missingByName->isNotEmpty()) {
            $this->showPushPreview($missingByName);
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

    protected function fetchRemoteProducts(): ?array
    {
        $products = [];
        $offsetModel = null;
        $offsetId = 0;
        $page = 0;

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

                foreach ($data['changes'] ?? [] as $change) {
                    if ($change['type'] === Product::class || $change['type'] === 'App\\Models\\Product') {
                        $products[] = $change['payload'];
                    }
                }

                $hasMore = $data['has_more'] ?? false;
                $offsetModel = $data['next_offset_model'] ?? null;
                $offsetId = $data['next_offset_id'] ?? 0;

                $this->output->write("\r  Page {$page}: fetched " . count($products) . " products so far...");

                $passedProducts = false;
                foreach ($data['changes'] ?? [] as $change) {
                    $type = $change['type'] ?? '';
                    if (str_contains($type, 'Product')) {
                        $passedProducts = true;
                    }
                    if ($passedProducts && !str_contains($type, 'Product') && !str_contains($type, 'Barcode') && !str_contains($type, 'Unit')) {
                        $hasMore = false;
                        break;
                    }
                }

                if ($offsetModel && !str_contains($offsetModel, 'Product') && !str_contains($offsetModel, 'Barcode') && !str_contains($offsetModel, 'Unit')) {
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

    protected function showResults($missingByName, $missingById, $existRemoteNotLocal, $localProducts, $remoteProducts): void
    {
        $this->info('=== Comparison Results ===');
        $this->newLine();

        $this->info("Missing from production (not matched by ID, barcode, or name): {$missingByName->count()}");

        if ($missingByName->isNotEmpty()) {
            $rows = $missingByName->map(function ($p) {
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
        $this->info("Exist on production but not locally: {$existRemoteNotLocal->count()}");

        if ($existRemoteNotLocal->isNotEmpty()) {
            $rows = $existRemoteNotLocal->take(20)->map(function ($rp) {
                return [
                    $rp['id'],
                    $rp['name'] ?? '-',
                    $rp['barcode'] ?? '-',
                ];
            })->toArray();

            $this->table(['ID', 'Name', 'Barcode'], $rows);

            if ($existRemoteNotLocal->count() > 20) {
                $this->warn("  ... and " . ($existRemoteNotLocal->count() - 20) . " more");
            }
        }
    }

    protected function showPushPreview($missingProducts): void
    {
        $this->newLine();
        $this->warn('=== DRY RUN - Would push these products ===');

        foreach ($missingProducts as $product) {
            $this->line("  [{$product->id}] {$product->name}");
            $this->line("    Units: " . $product->productUnits->pluck('unit_id')->implode(', '));
            $this->line("    Barcodes: " . $product->barcodes->pluck('barcode')->implode(', '));
            $this->line("    Batches: " . $product->inventoryBatches->count());
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
