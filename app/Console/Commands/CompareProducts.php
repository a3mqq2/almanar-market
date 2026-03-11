<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CompareProducts extends Command
{
    protected $signature = 'sync:compare-products
        {--device= : Device ID to use for API auth}
        {--product= : Compare specific product ID only}
        {--prices-only : Show only price mismatches}
        {--stock-only : Show only stock mismatches}';

    protected $description = 'Compare local product prices and quantities with production server';

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

        $this->info('Fetching production data...');
        $remoteData = $this->fetchRemoteProducts();

        if ($remoteData === null) {
            return 1;
        }

        $remoteProducts = collect($remoteData['products'] ?? []);
        $this->info("Production: {$remoteProducts->count()} products");

        $localQuery = Product::with(['productUnits.unit:id,name', 'inventoryBatches']);
        $productId = $this->option('product');
        if ($productId) {
            $localQuery->where('id', $productId);
        }
        $localProducts = $localQuery->get();
        $this->info("Local: {$localProducts->count()} products");
        $this->newLine();

        $showPrices = !$this->option('stock-only');
        $showStock = !$this->option('prices-only');

        if ($showPrices) {
            $this->comparePrices($localProducts, $remoteProducts);
            $this->newLine();
        }

        if ($showStock) {
            $this->compareStock($localProducts, $remoteProducts);
            $this->newLine();
            $this->compareBatches($localProducts, $remoteProducts);
        }

        return 0;
    }

    protected function fetchRemoteProducts(): ?array
    {
        $params = [];
        if ($this->option('product')) {
            $params['product_id'] = $this->option('product');
        }

        try {
            $response = Http::withHeaders([
                'X-Device-ID' => $this->deviceId,
                'X-API-Token' => $this->apiToken,
                'Accept' => 'application/json',
            ])->timeout(120)->get("{$this->serverUrl}/api/v1/sync/compare-products", $params);

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

    protected function comparePrices($localProducts, $remoteProducts): void
    {
        $this->warn('=== Price Comparison ===');

        $rows = [];
        $mismatches = 0;

        foreach ($localProducts as $local) {
            $remote = $remoteProducts->firstWhere('id', $local->id);
            if (!$remote) continue;

            $remoteUnits = collect($remote['units'] ?? []);

            foreach ($local->productUnits as $localUnit) {
                $remoteUnit = $remoteUnits->firstWhere('id', $localUnit->id);
                if (!$remoteUnit) {
                    $remoteUnit = $remoteUnits->first(fn($u) => $u['unit_id'] == $localUnit->unit_id);
                }
                if (!$remoteUnit) continue;

                $localSell = (float) $localUnit->sell_price;
                $remoteSell = (float) $remoteUnit['sell_price'];
                $localCost = (float) $localUnit->cost_price;
                $remoteCost = (float) $remoteUnit['cost_price'];

                $sellDiff = abs($localSell - $remoteSell) > 0.01;
                $costDiff = abs($localCost - $remoteCost) > 0.01;

                if ($sellDiff || $costDiff) {
                    $mismatches++;
                    $flags = [];
                    if ($sellDiff) $flags[] = 'SELL';
                    if ($costDiff) $flags[] = 'COST';

                    $rows[] = [
                        $local->id,
                        mb_substr($local->name, 0, 30),
                        $localUnit->unit?->name ?? '-',
                        number_format($localSell, 2),
                        number_format($remoteSell, 2),
                        number_format($localSell - $remoteSell, 2),
                        number_format($localCost, 2),
                        number_format($remoteCost, 2),
                        number_format($localCost - $remoteCost, 2),
                        implode(',', $flags),
                    ];
                }
            }
        }

        if (empty($rows)) {
            $this->info('All prices match!');
        } else {
            $this->table(
                ['ID', 'Product', 'Unit', 'L.Sell', 'P.Sell', 'Sell Diff', 'L.Cost', 'P.Cost', 'Cost Diff', 'Mismatch'],
                array_slice($rows, 0, 100)
            );
            $this->error("Price mismatches: {$mismatches}");
            if ($mismatches > 100) {
                $this->comment("... and " . ($mismatches - 100) . " more");
            }
        }
    }

    protected function compareStock($localProducts, $remoteProducts): void
    {
        $this->warn('=== Stock Comparison (Total per Product) ===');

        $rows = [];
        $mismatches = 0;

        foreach ($localProducts as $local) {
            $remote = $remoteProducts->firstWhere('id', $local->id);
            if (!$remote) continue;

            $localStock = (float) $local->inventoryBatches->sum('quantity');
            $remoteStock = (float) ($remote['total_stock'] ?? 0);
            $diff = $localStock - $remoteStock;

            if (abs($diff) > 0.01) {
                $mismatches++;
                $rows[] = [
                    $local->id,
                    mb_substr($local->name, 0, 35),
                    number_format($localStock, 4),
                    number_format($remoteStock, 4),
                    number_format($diff, 4),
                    'MISMATCH',
                ];
            }
        }

        if (empty($rows)) {
            $this->info('All stock totals match!');
        } else {
            $this->table(
                ['ID', 'Product', 'Local Stock', 'Production Stock', 'Difference', 'Status'],
                array_slice($rows, 0, 100)
            );
            $this->error("Stock mismatches: {$mismatches}");
            if ($mismatches > 100) {
                $this->comment("... and " . ($mismatches - 100) . " more");
            }
        }
    }

    protected function compareBatches($localProducts, $remoteProducts): void
    {
        $this->warn('=== Batch-Level Comparison ===');

        $batchMismatches = [];
        $onlyLocal = [];
        $onlyRemote = [];

        foreach ($localProducts as $local) {
            $remote = $remoteProducts->firstWhere('id', $local->id);
            if (!$remote) continue;

            $remoteBatches = collect($remote['batches'] ?? []);

            foreach ($local->inventoryBatches as $localBatch) {
                $remoteBatch = $remoteBatches->firstWhere('id', $localBatch->id);
                if (!$remoteBatch) {
                    $remoteBatch = $remoteBatches->first(fn($b) => $b['batch_number'] == $localBatch->batch_number);
                }

                if (!$remoteBatch) {
                    $onlyLocal[] = [
                        $local->id,
                        mb_substr($local->name, 0, 25),
                        $localBatch->id,
                        $localBatch->batch_number,
                        number_format((float) $localBatch->quantity, 4),
                        number_format((float) $localBatch->cost_price, 2),
                    ];
                    continue;
                }

                $localQty = (float) $localBatch->quantity;
                $remoteQty = (float) $remoteBatch['quantity'];
                $diff = $localQty - $remoteQty;

                if (abs($diff) > 0.01) {
                    $batchMismatches[] = [
                        $local->id,
                        mb_substr($local->name, 0, 25),
                        $localBatch->id,
                        $localBatch->batch_number,
                        number_format($localQty, 4),
                        number_format($remoteQty, 4),
                        number_format($diff, 4),
                    ];
                }
            }

            foreach ($remoteBatches as $remoteBatch) {
                $localBatch = $local->inventoryBatches->firstWhere('id', $remoteBatch['id']);
                if (!$localBatch) {
                    $localBatch = $local->inventoryBatches->first(fn($b) => $b->batch_number == $remoteBatch['batch_number']);
                }
                if (!$localBatch) {
                    $onlyRemote[] = [
                        $local->id,
                        mb_substr($local->name, 0, 25),
                        $remoteBatch['id'],
                        $remoteBatch['batch_number'],
                        number_format((float) $remoteBatch['quantity'], 4),
                        number_format((float) $remoteBatch['cost_price'], 2),
                    ];
                }
            }
        }

        if (!empty($batchMismatches)) {
            $this->warn('--- Batch Quantity Mismatches ---');
            $this->table(
                ['Prod ID', 'Product', 'Batch ID', 'Batch #', 'Local Qty', 'Prod Qty', 'Diff'],
                array_slice($batchMismatches, 0, 100)
            );
            if (count($batchMismatches) > 100) {
                $this->comment("... and " . (count($batchMismatches) - 100) . " more");
            }
        }

        if (!empty($onlyLocal)) {
            $this->warn('--- Batches Only in Local ---');
            $this->table(
                ['Prod ID', 'Product', 'Batch ID', 'Batch #', 'Quantity', 'Cost'],
                array_slice($onlyLocal, 0, 50)
            );
        }

        if (!empty($onlyRemote)) {
            $this->warn('--- Batches Only in Production ---');
            $this->table(
                ['Prod ID', 'Product', 'Batch ID', 'Batch #', 'Quantity', 'Cost'],
                array_slice($onlyRemote, 0, 50)
            );
        }

        $this->newLine();
        $this->info("Batch mismatches: " . count($batchMismatches));
        $this->info("Only in Local: " . count($onlyLocal));
        $this->info("Only in Production: " . count($onlyRemote));

        if (empty($batchMismatches) && empty($onlyLocal) && empty($onlyRemote)) {
            $this->info('All batches match!');
        }
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
