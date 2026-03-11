<?php

namespace App\Console\Commands;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FixProducts extends Command
{
    protected $signature = 'sync:fix-products
        {--device= : Device ID to use for API auth}
        {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix local product prices and quantities from production server (source of truth)';

    protected string $serverUrl;
    protected string $apiToken;
    protected string $deviceId;

    public function handle(): int
    {
        $this->serverUrl = rtrim(config('desktop.server_url'), '/');
        $dryRun = $this->option('dry-run');

        $device = $this->resolveDevice();
        if (!$device) {
            return 1;
        }

        $this->deviceId = $device->device_id;
        $this->apiToken = $device->api_token;

        $this->info("Server: {$this->serverUrl}");
        $this->info("Device: {$device->device_name} ({$this->deviceId})");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - no changes will be made');
        }
        $this->newLine();

        $this->info('Step 1: Fetching production data...');
        $remoteData = $this->fetchRemoteProducts();
        if ($remoteData === null) {
            return 1;
        }

        $remoteProducts = collect($remoteData['products'] ?? []);
        $this->info("  Production products: {$remoteProducts->count()}");
        $this->newLine();

        $fixedPrices = 0;
        $fixedBatchQty = 0;
        $createdBatches = 0;
        $recalcBatches = 0;

        $this->info('Step 2: Fixing prices (production is source of truth)...');
        $fixedPrices = $this->fixPrices($remoteProducts, $dryRun);
        $this->info("  Fixed prices: {$fixedPrices}");
        $this->newLine();

        $this->info('Step 3: Creating missing batches from production...');
        $createdBatches = $this->createMissingBatches($remoteProducts, $dryRun);
        $this->info("  Created batches: {$createdBatches}");
        $this->newLine();

        $this->info('Step 4: Fixing batch quantities from production...');
        $fixedBatchQty = $this->fixBatchQuantities($remoteProducts, $dryRun);
        $this->info("  Fixed batch quantities: {$fixedBatchQty}");
        $this->newLine();

        $this->warn('=== Summary ===');
        $this->table(
            ['Action', 'Count'],
            [
                ['Prices fixed', $fixedPrices],
                ['Batches created from production', $createdBatches],
                ['Batch quantities fixed', $fixedBatchQty],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN - no changes were made. Remove --dry-run to apply.');
        } else {
            $this->info('All fixes applied successfully.');
        }

        return 0;
    }

    protected function fetchRemoteProducts(): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Device-ID' => $this->deviceId,
                'X-API-Token' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(120)->get("{$this->serverUrl}/api/v1/sync/compare-products");

            if (!$response->successful()) {
                $this->error("Server returned HTTP {$response->status()}");
                $this->error($response->body());
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->error("Connection failed: {$e->getMessage()}");
            return null;
        }
    }

    protected function fixPrices($remoteProducts, bool $dryRun): int
    {
        $fixed = 0;

        ProductUnit::disableSyncLogging();

        foreach ($remoteProducts as $remote) {
            $remoteUnits = collect($remote['units'] ?? []);

            foreach ($remoteUnits as $remoteUnit) {
                $localUnit = ProductUnit::find($remoteUnit['id']);
                if (!$localUnit) {
                    $localUnit = ProductUnit::where('product_id', $remote['id'])
                        ->where('unit_id', $remoteUnit['unit_id'])
                        ->first();
                }
                if (!$localUnit) continue;

                $sellDiff = abs((float) $localUnit->sell_price - (float) $remoteUnit['sell_price']) > 0.01;
                $costDiff = abs((float) $localUnit->cost_price - (float) $remoteUnit['cost_price']) > 0.01;

                if ($sellDiff || $costDiff) {
                    if (!$dryRun) {
                        DB::table('product_units')->where('id', $localUnit->id)->update([
                            'sell_price' => $remoteUnit['sell_price'],
                            'cost_price' => $remoteUnit['cost_price'],
                            'updated_at' => now(),
                        ]);
                    }
                    $fixed++;
                    $this->line("  [{$remote['id']}] " . mb_substr($remote['name'], 0, 30) .
                        " | sell: {$localUnit->sell_price} → {$remoteUnit['sell_price']}" .
                        " | cost: {$localUnit->cost_price} → {$remoteUnit['cost_price']}");
                }
            }
        }

        ProductUnit::enableSyncLogging();

        return $fixed;
    }

    protected function createMissingBatches($remoteProducts, bool $dryRun): int
    {
        $created = 0;

        InventoryBatch::disableSyncLogging();

        foreach ($remoteProducts as $remote) {
            $remoteBatches = collect($remote['batches'] ?? []);
            $product = Product::find($remote['id']);
            if (!$product) continue;

            foreach ($remoteBatches as $remoteBatch) {
                $localBatch = InventoryBatch::find($remoteBatch['id']);
                if (!$localBatch) {
                    $localBatch = InventoryBatch::where('batch_number', $remoteBatch['batch_number'])
                        ->where('product_id', $remote['id'])
                        ->first();
                }

                if (!$localBatch) {
                    if (!$dryRun) {
                        DB::table('inventory_batches')->insert([
                            'id' => $remoteBatch['id'],
                            'product_id' => $remote['id'],
                            'batch_number' => $remoteBatch['batch_number'],
                            'quantity' => $remoteBatch['quantity'],
                            'cost_price' => $remoteBatch['cost_price'],
                            'type' => 'purchase',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $created++;
                    $this->line("  [{$remote['id']}] " . mb_substr($remote['name'], 0, 30) .
                        " | batch: {$remoteBatch['batch_number']} qty: {$remoteBatch['quantity']}");
                }
            }
        }

        InventoryBatch::enableSyncLogging();

        return $created;
    }

    protected function fixBatchQuantities($remoteProducts, bool $dryRun): int
    {
        $fixed = 0;

        InventoryBatch::disableSyncLogging();

        foreach ($remoteProducts as $remote) {
            $remoteBatches = collect($remote['batches'] ?? []);

            foreach ($remoteBatches as $remoteBatch) {
                $localBatch = InventoryBatch::find($remoteBatch['id']);
                if (!$localBatch) {
                    $localBatch = InventoryBatch::where('batch_number', $remoteBatch['batch_number'])
                        ->where('product_id', $remote['id'])
                        ->first();
                }
                if (!$localBatch) continue;

                $localQty = (float) $localBatch->quantity;
                $remoteQty = (float) $remoteBatch['quantity'];

                if (abs($localQty - $remoteQty) > 0.01) {
                    if (!$dryRun) {
                        DB::table('inventory_batches')->where('id', $localBatch->id)->update([
                            'quantity' => $remoteQty,
                            'updated_at' => now(),
                        ]);
                    }
                    $fixed++;
                    $this->line("  [{$remote['id']}] " . mb_substr($remote['name'], 0, 30) .
                        " | batch #{$localBatch->id}: {$localQty} → {$remoteQty}");
                }
            }
        }

        InventoryBatch::enableSyncLogging();

        return $fixed;
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
}
