<?php

namespace App\Console\Commands;

use App\Models\DeviceRegistration;
use App\Models\ProductUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CompareUnits extends Command
{
    protected $signature = 'sync:compare-units
        {--fix : Fix mismatches by updating local units from production}
        {--product= : Check specific product ID only}';

    protected $description = 'Compare local product units with production and show differences';

    public function handle(): int
    {
        $serverUrl = config('desktop.server_url');
        $device = DeviceRegistration::where('device_id', config('desktop.device_id'))->first();

        if (!$device) {
            $this->error('No device registration found.');
            return 1;
        }

        $this->info('Fetching units from production...');

        $page = 1;
        $remoteUnits = collect();

        do {
            $response = Http::withToken($device->api_token)
                ->timeout(120)
                ->get($serverUrl . '/api/v1/sync/pull', [
                    'device_id' => $device->device_id,
                    'limit' => 5000,
                    'offset_model' => $page === 1 ? null : 'App\Models\ProductUnit',
                    'offset_id' => $remoteUnits->last()['id'] ?? 0,
                ]);

            if (!$response->successful()) {
                $this->error('API Error: ' . $response->status());
                return 1;
            }

            $data = $response->json();
            $changes = collect($data['changes'] ?? []);

            $units = $changes->filter(fn($c) => $c['type'] === 'App\Models\ProductUnit');
            $remoteUnits = $remoteUnits->merge($units);

            $hasMore = $data['has_more'] ?? false;
            $page++;
        } while ($hasMore);

        if ($remoteUnits->isEmpty()) {
            $this->warn('No ProductUnit changes returned from production.');
            $this->info('Trying direct fetch...');

            $remoteUnits = $this->fetchAllUnitsDirectly($device);
            if ($remoteUnits->isEmpty()) {
                $this->error('Could not fetch units from production.');
                return 1;
            }
        }

        $this->info("Got {$remoteUnits->count()} units from production.");

        $productFilter = $this->option('product');
        $missingLocally = [];
        $priceMismatch = [];
        $missingOnServer = [];

        $localUnits = ProductUnit::with('product', 'unit')->get()->keyBy(function ($u) {
            return $u->product_id . '-' . $u->unit_id;
        });

        foreach ($remoteUnits as $remote) {
            $payload = $remote['payload'] ?? $remote;
            $productId = $payload['product_id'] ?? null;
            $unitId = $payload['unit_id'] ?? null;

            if (!$productId || !$unitId) continue;
            if ($productFilter && $productId != $productFilter) continue;

            $key = $productId . '-' . $unitId;
            $local = $localUnits->get($key);

            if (!$local) {
                $missingLocally[] = $payload;
            } else {
                $sellDiff = abs(($local->sell_price ?? 0) - ($payload['sell_price'] ?? 0)) > 0.01;
                $costDiff = abs(($local->cost_price ?? 0) - ($payload['cost_price'] ?? 0)) > 0.01;

                if ($sellDiff || $costDiff) {
                    $priceMismatch[] = [
                        'product_id' => $productId,
                        'unit_id' => $unitId,
                        'product_name' => $local->product?->name ?? '-',
                        'local_sell' => $local->sell_price,
                        'server_sell' => $payload['sell_price'],
                        'local_cost' => $local->cost_price,
                        'server_cost' => $payload['cost_price'],
                        'server_id' => $payload['id'] ?? null,
                        'local_id' => $local->id,
                    ];
                }

                $localUnits->forget($key);
            }
        }

        if ($productFilter) {
            $localUnits = $localUnits->filter(fn($u) => $u->product_id == $productFilter);
        }

        foreach ($localUnits as $local) {
            $missingOnServer[] = [
                'id' => $local->id,
                'product_id' => $local->product_id,
                'product_name' => $local->product?->name ?? '-',
                'unit_id' => $local->unit_id,
                'sell_price' => $local->sell_price,
                'cost_price' => $local->cost_price,
            ];
        }

        $this->newLine();

        if (!empty($missingLocally)) {
            $this->warn("Missing locally ({$this->count($missingLocally)}):");
            $rows = [];
            foreach ($missingLocally as $m) {
                $rows[] = [
                    $m['id'] ?? '-',
                    $m['product_id'],
                    $m['unit_id'],
                    number_format($m['sell_price'] ?? 0, 2),
                    number_format($m['cost_price'] ?? 0, 2),
                ];
            }
            $this->table(['Server ID', 'Product', 'Unit', 'Sell', 'Cost'], $rows);
        }

        if (!empty($priceMismatch)) {
            $this->warn("Price mismatches ({$this->count($priceMismatch)}):");
            $rows = [];
            foreach ($priceMismatch as $m) {
                $rows[] = [
                    $m['product_id'],
                    mb_substr($m['product_name'], 0, 30),
                    $m['unit_id'],
                    $m['local_sell'] . ' → ' . $m['server_sell'],
                    $m['local_cost'] . ' → ' . $m['server_cost'],
                ];
            }
            $this->table(['Product', 'Name', 'Unit', 'Sell (local→server)', 'Cost (local→server)'], $rows);
        }

        if (!empty($missingOnServer)) {
            $this->warn("Missing on server ({$this->count($missingOnServer)}):");
            $rows = [];
            foreach ($missingOnServer as $m) {
                $rows[] = [
                    $m['id'],
                    $m['product_id'],
                    mb_substr($m['product_name'], 0, 30),
                    $m['unit_id'],
                    number_format($m['sell_price'] ?? 0, 2),
                ];
            }
            $this->table(['Local ID', 'Product', 'Name', 'Unit', 'Sell'], $rows);
        }

        if (empty($missingLocally) && empty($priceMismatch) && empty($missingOnServer)) {
            $this->info('All units match!');
            return 0;
        }

        if ($this->option('fix') && (!empty($missingLocally) || !empty($priceMismatch))) {
            $this->fixLocalUnits($missingLocally, $priceMismatch);
        }

        return 0;
    }

    protected function fixLocalUnits(array $missing, array $mismatches): void
    {
        $this->newLine();

        if (!empty($missing)) {
            $this->info("Creating {$this->count($missing)} missing units locally...");
            foreach ($missing as $payload) {
                $existing = ProductUnit::find($payload['id'] ?? 0);
                if ($existing) {
                    $existing->update([
                        'product_id' => $payload['product_id'],
                        'unit_id' => $payload['unit_id'],
                        'multiplier' => $payload['multiplier'] ?? 1,
                        'sell_price' => $payload['sell_price'] ?? 0,
                        'cost_price' => $payload['cost_price'] ?? 0,
                        'is_base_unit' => $payload['is_base_unit'] ?? false,
                    ]);
                    $this->line("  Updated ID {$existing->id} for product {$payload['product_id']}");
                } else {
                    $unit = new ProductUnit();
                    $unit->id = $payload['id'] ?? null;
                    $unit->product_id = $payload['product_id'];
                    $unit->unit_id = $payload['unit_id'];
                    $unit->multiplier = $payload['multiplier'] ?? 1;
                    $unit->sell_price = $payload['sell_price'] ?? 0;
                    $unit->cost_price = $payload['cost_price'] ?? 0;
                    $unit->is_base_unit = $payload['is_base_unit'] ?? false;
                    $unit->synced_at = now();
                    $unit->save();
                    $this->line("  Created ID {$unit->id} for product {$payload['product_id']}");
                }
            }
        }

        if (!empty($mismatches)) {
            $this->info("Fixing {$this->count($mismatches)} price mismatches...");
            foreach ($mismatches as $m) {
                ProductUnit::where('product_id', $m['product_id'])
                    ->where('unit_id', $m['unit_id'])
                    ->update([
                        'sell_price' => $m['server_sell'],
                        'cost_price' => $m['server_cost'],
                        'synced_at' => now(),
                    ]);
                $this->line("  Fixed product {$m['product_id']}: sell {$m['local_sell']}→{$m['server_sell']}");
            }
        }

        $this->info('Done!');
    }

    protected function fetchAllUnitsDirectly(DeviceRegistration $device): \Illuminate\Support\Collection
    {
        $serverUrl = config('desktop.server_url');
        $allUnits = collect();
        $offsetId = 0;

        do {
            $response = Http::withToken($device->api_token)
                ->timeout(120)
                ->get($serverUrl . '/api/v1/sync/pull', [
                    'device_id' => $device->device_id,
                    'limit' => 5000,
                    'offset_model' => 'App\Models\ProductUnit',
                    'offset_id' => $offsetId,
                ]);

            if (!$response->successful()) break;

            $data = $response->json();
            $changes = collect($data['changes'] ?? []);
            $units = $changes->filter(fn($c) => $c['type'] === 'App\Models\ProductUnit');

            if ($units->isEmpty()) break;

            $allUnits = $allUnits->merge($units);
            $offsetId = $units->last()['id'] ?? 0;
            $hasMore = $data['has_more'] ?? false;
        } while ($hasMore);

        return $allUnits;
    }

    protected function count(array $arr): int
    {
        return count($arr);
    }
}
