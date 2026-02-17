<?php

namespace App\Services;

use App\Models\DeviceRegistration;
use App\Models\SyncConflict;
use App\Models\SyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncService
{
    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withOptions([
            'verify' => base_path('certs/cacert.pem'),
        ])->timeout(60)->retry(3, 200);
    }

    protected array $syncOrder = [
        'App\Models\User',
        'App\Models\Unit',
        'App\Models\Supplier',
        'App\Models\Customer',
        'App\Models\Cashbox',
        'App\Models\PaymentMethod',
        'App\Models\ExpenseCategory',
        'App\Models\Product',
        'App\Models\ProductUnit',
        'App\Models\ProductBarcode',
        'App\Models\InventoryBatch',
        'App\Models\Purchase',
        'App\Models\PurchaseItem',
        'App\Models\Shift',
        'App\Models\ShiftCashbox',
        'App\Models\Sale',
        'App\Models\SaleItem',
        'App\Models\SalePayment',
        'App\Models\SalesReturn',
        'App\Models\SaleReturnItem',
        'App\Models\Expense',
        'App\Models\StockMovement',
        'App\Models\CashboxTransaction',
        'App\Models\CustomerTransaction',
        'App\Models\SupplierTransaction',
        'App\Models\InventoryCount',
        'App\Models\InventoryCountItem',
        'App\Models\UserActivityLog',
    ];

    protected array $foreignKeyMap = [
        'App\Models\SaleItem' => ['sale_id' => 'App\Models\Sale'],
        'App\Models\SalePayment' => ['sale_id' => 'App\Models\Sale'],
        'App\Models\SaleReturnItem' => ['sales_return_id' => 'App\Models\SalesReturn'],
        'App\Models\PurchaseItem' => ['purchase_id' => 'App\Models\Purchase'],
        'App\Models\ShiftCashbox' => ['shift_id' => 'App\Models\Shift'],
        'App\Models\CashboxTransaction' => ['shift_id' => 'App\Models\Shift'],
        'App\Models\StockMovement' => ['sale_id' => 'App\Models\Sale', 'purchase_id' => 'App\Models\Purchase'],
        'App\Models\InventoryCountItem' => ['inventory_count_id' => 'App\Models\InventoryCount'],
        'App\Models\CustomerTransaction' => ['customer_id' => 'App\Models\Customer'],
        'App\Models\SupplierTransaction' => ['supplier_id' => 'App\Models\Supplier'],
    ];

    public function getPendingChanges(string $deviceId): array
    {
        $changes = [];

        foreach ($this->syncOrder as $modelClass) {
            $logs = SyncLog::where('device_id', $deviceId)
                ->where('syncable_type', $modelClass)
                ->where('sync_status', 'pending')
                ->orderBy('local_timestamp')
                ->get();

            foreach ($logs as $log) {
                $payload = $log->payload;
                $payload = $this->remapForeignKeys($modelClass, $payload);

                $changes[] = [
                    'id' => $log->id,
                    'type' => $log->syncable_type,
                    'record_id' => $log->syncable_id,
                    'action' => $log->action,
                    'payload' => $payload,
                    'timestamp' => $log->local_timestamp->toIso8601String(),
                ];
            }
        }

        return $changes;
    }

    protected function remapForeignKeys(string $modelClass, array $payload): array
    {
        if (!isset($this->foreignKeyMap[$modelClass])) {
            return $payload;
        }

        foreach ($this->foreignKeyMap[$modelClass] as $fkField => $parentClass) {
            if (!isset($payload[$fkField]) || empty($payload[$fkField])) {
                continue;
            }

            $localId = $payload[$fkField];

            $parentLog = SyncLog::where('syncable_type', $parentClass)
                ->where('syncable_id', $localId)
                ->where('sync_status', 'synced')
                ->whereNotNull('server_id')
                ->orderBy('synced_at', 'desc')
                ->first();

            if ($parentLog && $parentLog->server_id) {
                $payload[$fkField] = $parentLog->server_id;
            }
        }

        return $payload;
    }

    public function pushChanges(string $deviceId): array
    {
        $device = DeviceRegistration::where('device_id', $deviceId)->first();

        if (!$device || !$device->isActive()) {
            return ['success' => false, 'message' => 'Device not active'];
        }

        $changes = $this->getPendingChanges($deviceId);

        if (empty($changes)) {
            return ['success' => true, 'pushed' => 0];
        }

        try {
            $response = $this->http()
                ->withToken($device->api_token)
                ->post(config('desktop.server_url') . '/api/v1/sync/push', [
                    'device_id' => $deviceId,
                    'changes' => $changes,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $this->processPushResponse($result);
                $device->updateLastSync();

                return [
                    'success' => true,
                    'pushed' => count($changes),
                    'synced' => $result['synced'] ?? [],
                    'conflicts' => $result['conflicts'] ?? [],
                    'errors' => $result['errors'] ?? [],
                ];
            }

            return ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function processPushResponse(array $response): void
    {
        if (isset($response['synced'])) {
            foreach ($response['synced'] as $synced) {
                $log = SyncLog::find($synced['log_id']);
                if ($log) {
                    $log->markAsSynced($synced['server_id'] ?? null);
                }
            }
        }

        if (isset($response['conflicts'])) {
            foreach ($response['conflicts'] as $conflict) {
                $log = SyncLog::find($conflict['log_id']);
                if ($log) {
                    $log->markAsConflict();

                    SyncConflict::create([
                        'device_id' => $log->device_id,
                        'syncable_type' => $log->syncable_type,
                        'syncable_id' => $log->syncable_id,
                        'local_data' => $log->payload,
                        'server_data' => $conflict['server_data'],
                        'resolution' => 'pending',
                    ]);
                }
            }
        }

        if (isset($response['errors'])) {
            foreach ($response['errors'] as $error) {
                $log = SyncLog::find($error['log_id']);
                if ($log) {
                    $log->markAsFailed($error['message'] ?? 'Unknown server error');
                }
            }
        }
    }

    public function pullChanges(string $deviceId, Carbon $since = null): array
    {
        $device = DeviceRegistration::where('device_id', $deviceId)->first();

        if (!$device || !$device->isActive()) {
            return ['success' => false, 'message' => 'Device not active'];
        }

        try {
            $params = ['device_id' => $deviceId];
            if ($since) {
                $params['since'] = $since->toIso8601String();
            }

            $response = $this->http()
                ->withToken($device->api_token)
                ->get(config('desktop.server_url') . '/api/v1/sync/pull', $params);

            if ($response->successful()) {
                $result = $response->json();
                $applied = $this->applyPulledChanges($result['changes'] ?? []);
                $device->updateLastSync();

                return [
                    'success' => true,
                    'pulled' => count($result['changes'] ?? []),
                    'applied' => $applied,
                ];
            }

            return ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function applyPulledChanges(array $changes): int
    {
        $applied = 0;

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        foreach ($this->syncOrder as $modelClass) {
            if (method_exists($modelClass, 'disableSyncLogging')) {
                $modelClass::disableSyncLogging();
            }
        }

        DB::beginTransaction();

        try {
            foreach ($changes as $change) {
                $modelClass = $change['type'];
                $action = $change['action'];
                $payload = $change['payload'];
                $serverId = $change['id'];

                switch ($action) {
                    case 'created':
                    case 'updated':
                        $model = $this->findOrCreateModel($modelClass, $serverId, $payload);

                        if ($modelClass === 'App\Models\ProductUnit' && ($payload['is_base_unit'] ?? false)) {
                            \App\Models\ProductUnit::where('product_id', $payload['product_id'])
                                ->where('id', '!=', $model->id ?: 0)
                                ->update(['is_base_unit' => false]);
                        }

                        $hashedPassword = null;
                        if ($modelClass === 'App\Models\User' && isset($payload['password'])) {
                            $hashedPassword = $payload['password'];
                            unset($payload['password']);
                        }

                        $this->resolveUniqueConflicts($modelClass, $payload, $model->id ?: 0);

                        if ($modelClass === 'App\Models\Sale' && ($payload['status'] ?? '') === 'cancelled' && $model->exists && $model->status !== 'cancelled') {
                            $this->handlePulledCancellation($model, $payload);
                            $applied++;
                            break;
                        }

                        if ($modelClass === 'App\Models\Purchase' && ($payload['status'] ?? '') === 'cancelled' && $model->exists && $model->status !== 'cancelled') {
                            $this->handlePulledPurchaseCancellation($model, $payload);
                            $applied++;
                            break;
                        }

                        if ($modelClass === 'App\Models\SalesReturn' && $model->exists) {
                            $this->handlePulledSalesReturn($model, $payload);
                            $applied++;
                            break;
                        }

                        if ($modelClass === 'App\Models\InventoryCount' && ($payload['status'] ?? '') === 'approved' && $model->exists && $model->status !== 'approved') {
                            $this->handlePulledInventoryCountApproval($model, $payload);
                            $applied++;
                            break;
                        }

                        $model->fill($payload);
                        $model->synced_at = now();
                        $model->save();

                        if ($hashedPassword) {
                            DB::table('users')->where('id', $model->id)->update(['password' => $hashedPassword]);
                        }

                        $applied++;
                        break;

                    case 'deleted':
                        $model = $modelClass::find($serverId);
                        if ($model) {
                            $model->delete();
                            $applied++;
                        }
                        break;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            if (DB::connection()->getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }

            foreach ($this->syncOrder as $modelClass) {
                if (method_exists($modelClass, 'enableSyncLogging')) {
                    $modelClass::enableSyncLogging();
                }
            }
        }

        return $applied;
    }

    protected function findOrCreateModel(string $modelClass, int $serverId, array $payload)
    {
        $model = $modelClass::find($serverId);
        if ($model) {
            return $model;
        }

        if ($modelClass === 'App\Models\ProductUnit' && isset($payload['product_id'], $payload['unit_id'])) {
            $existingUnits = $modelClass::where('product_id', $payload['product_id'])
                ->where('unit_id', $payload['unit_id'])
                ->orderBy('id')
                ->get();

            if ($existingUnits->count() > 1) {
                $keep = $existingUnits->first();
                $existingUnits->slice(1)->each->delete();
                return $keep;
            }

            if ($existingUnits->count() === 1) {
                return $existingUnits->first();
            }
        }

        $uniqueField = $this->getUniqueField($modelClass);
        if ($uniqueField && isset($payload[$uniqueField])) {
            $existing = $modelClass::where($uniqueField, $payload[$uniqueField])->first();
            if ($existing) {
                return $existing;
            }
        }

        if ($modelClass === 'App\Models\ProductBarcode' && isset($payload['barcode'])) {
            $existingBarcodes = $modelClass::where('barcode', $payload['barcode'])
                ->orderBy('id')
                ->get();

            if ($existingBarcodes->count() > 1) {
                $keep = $existingBarcodes->first();
                $existingBarcodes->slice(1)->each->delete();
                return $keep;
            }

            if ($existingBarcodes->count() === 1) {
                return $existingBarcodes->first();
            }
        }

        $model = new $modelClass();
        $model->id = $serverId;
        return $model;
    }

    protected function getUniqueField(string $modelClass): ?string
    {
        $map = [
            'App\Models\Sale' => 'invoice_number',
            'App\Models\SalesReturn' => 'return_number',
            'App\Models\Purchase' => 'invoice_number',
            'App\Models\Expense' => 'reference_number',
            'App\Models\InventoryCount' => 'reference_number',
        ];

        return $map[$modelClass] ?? null;
    }

    protected function resolveUniqueConflicts(string $modelClass, array $payload, int $excludeId): void
    {
        $uniqueField = $this->getUniqueField($modelClass);
        if (!$uniqueField || !isset($payload[$uniqueField])) {
            return;
        }

        $table = (new $modelClass)->getTable();
        $conflicting = $modelClass::where($uniqueField, $payload[$uniqueField])
            ->where('id', '!=', $excludeId)
            ->first();

        if ($conflicting) {
            DB::table($table)->where('id', $conflicting->id)
                ->update([$uniqueField => $payload[$uniqueField] . '-L' . $conflicting->id]);
        }
    }

    protected function handlePulledCancellation($sale, array $payload): void
    {
        $ref = "SAL-{$sale->id}-CANCEL";
        $alreadyProcessed = \App\Models\StockMovement::where('reference', $ref)->exists();

        if (!$alreadyProcessed && $sale->status === 'completed') {
            foreach ($sale->items as $item) {
                $batch = \App\Models\InventoryBatch::find($item->inventory_batch_id);
                if ($batch) {
                    $currentStock = $item->product->total_stock ?? 0;
                    $batch->increment('quantity', $item->base_quantity);

                    \App\Models\StockMovement::create([
                        'product_id' => $item->product_id,
                        'batch_id' => $batch->id,
                        'type' => 'return',
                        'reason' => "إلغاء فاتورة مبيعات #{$sale->invoice_number} (sync)",
                        'quantity' => $item->base_quantity,
                        'before_quantity' => $currentStock,
                        'after_quantity' => $currentStock + $item->base_quantity,
                        'cost_price' => $batch->cost_price,
                        'reference' => $ref,
                    ]);
                }
            }

            $hasReversal = \App\Models\CashboxTransaction::where('reference_type', \App\Models\Sale::class)
                ->where('reference_id', $sale->id)
                ->where('type', 'out')
                ->exists();

            if (!$hasReversal) {
                $originalCashboxTxns = \App\Models\CashboxTransaction::where('reference_type', \App\Models\Sale::class)
                    ->where('reference_id', $sale->id)
                    ->where('type', 'in')
                    ->get();

                foreach ($originalCashboxTxns as $original) {
                    $cashbox = \App\Models\Cashbox::find($original->cashbox_id);
                    if ($cashbox) {
                        \App\Models\CashboxTransaction::create([
                            'cashbox_id' => $cashbox->id,
                            'shift_id' => $original->shift_id,
                            'type' => 'out',
                            'amount' => $original->amount,
                            'balance_after' => $cashbox->current_balance - $original->amount,
                            'description' => "إلغاء فاتورة مبيعات #{$sale->invoice_number} (sync)",
                            'reference_type' => \App\Models\Sale::class,
                            'reference_id' => $sale->id,
                        ]);
                        $cashbox->decrement('current_balance', $original->amount);
                    }
                }
            }

            if (($sale->credit_amount ?? 0) > 0 && $sale->customer_id) {
                $hasCreditReversal = \App\Models\CustomerTransaction::where('reference_type', \App\Models\Sale::class)
                    ->where('reference_id', $sale->id)
                    ->where('type', 'credit')
                    ->exists();

                if (!$hasCreditReversal) {
                    $customer = \App\Models\Customer::find($sale->customer_id);
                    if ($customer) {
                        \App\Models\CustomerTransaction::create([
                            'customer_id' => $customer->id,
                            'type' => 'credit',
                            'amount' => $sale->credit_amount,
                            'balance_after' => $customer->current_balance - $sale->credit_amount,
                            'description' => "إلغاء فاتورة مبيعات #{$sale->invoice_number} (sync)",
                            'reference_type' => \App\Models\Sale::class,
                            'reference_id' => $sale->id,
                        ]);
                        $customer->decrement('current_balance', $sale->credit_amount);
                    }
                }
            }
        }

        $sale->update([
            'status' => 'cancelled',
            'cancelled_by' => $payload['cancelled_by'] ?? null,
            'cancelled_at' => $payload['cancelled_at'] ?? now(),
            'cancel_reason' => $payload['cancel_reason'] ?? 'synced cancellation',
            'synced_at' => now(),
        ]);
    }

    protected function handlePulledPurchaseCancellation($purchase, array $payload): void
    {
        $ref = "PUR-{$purchase->id}-CANCEL";
        $alreadyProcessed = \App\Models\StockMovement::where('reference', $ref)->exists();

        if (!$alreadyProcessed && $purchase->status === 'approved') {
            foreach ($purchase->items as $item) {
                $batch = \App\Models\InventoryBatch::where('purchase_item_id', $item->id)->first();
                if ($batch && $batch->quantity > 0) {
                    $product = \App\Models\Product::find($item->product_id);
                    $currentStock = $product->total_stock ?? 0;
                    $deductQty = min($batch->quantity, $item->base_quantity ?? $item->quantity);
                    $batch->decrement('quantity', $deductQty);

                    \App\Models\StockMovement::create([
                        'product_id' => $item->product_id,
                        'batch_id' => $batch->id,
                        'type' => 'adjustment',
                        'reason' => "إلغاء فاتورة مشتريات #{$purchase->invoice_number} (sync)",
                        'quantity' => -$deductQty,
                        'before_quantity' => $currentStock,
                        'after_quantity' => $currentStock - $deductQty,
                        'cost_price' => $batch->cost_price,
                        'reference' => $ref,
                    ]);
                }
            }

            $hasReversal = \App\Models\SupplierTransaction::where('reference_type', \App\Models\Purchase::class)
                ->where('reference_id', $purchase->id)
                ->where('description', 'LIKE', '%إلغاء%')
                ->exists();

            if (!$hasReversal && $purchase->supplier_id) {
                $supplier = \App\Models\Supplier::find($purchase->supplier_id);
                if ($supplier) {
                    $remaining = $purchase->total - ($purchase->paid_amount ?? 0);
                    if ($remaining > 0) {
                        \App\Models\SupplierTransaction::create([
                            'supplier_id' => $supplier->id,
                            'type' => 'credit',
                            'amount' => $remaining,
                            'balance_after' => $supplier->current_balance - $remaining,
                            'description' => "إلغاء فاتورة مشتريات #{$purchase->invoice_number} (sync)",
                            'reference_type' => \App\Models\Purchase::class,
                            'reference_id' => $purchase->id,
                        ]);
                        $supplier->decrement('current_balance', $remaining);
                    }

                    if (($purchase->paid_amount ?? 0) > 0) {
                        \App\Models\SupplierTransaction::create([
                            'supplier_id' => $supplier->id,
                            'type' => 'debit',
                            'amount' => $purchase->paid_amount,
                            'balance_after' => $supplier->current_balance + $purchase->paid_amount,
                            'description' => "استرداد دفعة مشتريات #{$purchase->invoice_number} (sync)",
                            'reference_type' => \App\Models\Purchase::class,
                            'reference_id' => $purchase->id,
                        ]);
                        $supplier->increment('current_balance', $purchase->paid_amount);

                        $cashboxTxn = \App\Models\CashboxTransaction::where('reference_type', \App\Models\Purchase::class)
                            ->where('reference_id', $purchase->id)
                            ->where('type', 'out')
                            ->first();

                        if ($cashboxTxn) {
                            $cashbox = \App\Models\Cashbox::find($cashboxTxn->cashbox_id);
                            if ($cashbox) {
                                \App\Models\CashboxTransaction::create([
                                    'cashbox_id' => $cashbox->id,
                                    'shift_id' => $cashboxTxn->shift_id,
                                    'type' => 'in',
                                    'amount' => $purchase->paid_amount,
                                    'balance_after' => $cashbox->current_balance + $purchase->paid_amount,
                                    'description' => "استرداد دفعة مشتريات #{$purchase->invoice_number} (sync)",
                                    'reference_type' => \App\Models\Purchase::class,
                                    'reference_id' => $purchase->id,
                                ]);
                                $cashbox->increment('current_balance', $purchase->paid_amount);
                            }
                        }
                    }
                }
            }
        }

        $purchase->update([
            'status' => 'cancelled',
            'synced_at' => now(),
        ]);
    }

    protected function handlePulledSalesReturn($returnModel, array $payload): void
    {
        $ref = "RET-{$returnModel->id}";
        $alreadyProcessed = \App\Models\StockMovement::where('reference', $ref)->exists();

        if (!$alreadyProcessed) {
            foreach ($returnModel->items as $item) {
                if ($item->restore_stock ?? true) {
                    $batch = \App\Models\InventoryBatch::where('product_id', $item->product_id)
                        ->where('cost_price', $item->cost_price ?? 0)
                        ->orderBy('id', 'desc')
                        ->first();

                    if (!$batch) {
                        $batch = \App\Models\InventoryBatch::create([
                            'product_id' => $item->product_id,
                            'quantity' => 0,
                            'cost_price' => $item->cost_price ?? 0,
                            'source' => 'return',
                        ]);
                    }

                    $product = \App\Models\Product::find($item->product_id);
                    $currentStock = $product->total_stock ?? 0;
                    $qty = $item->base_quantity ?? $item->quantity;
                    $batch->increment('quantity', $qty);

                    \App\Models\StockMovement::create([
                        'product_id' => $item->product_id,
                        'batch_id' => $batch->id,
                        'type' => 'return',
                        'reason' => "مرتجع مبيعات #{$returnModel->return_number} (sync)",
                        'quantity' => $qty,
                        'before_quantity' => $currentStock,
                        'after_quantity' => $currentStock + $qty,
                        'cost_price' => $item->cost_price ?? 0,
                        'reference' => $ref,
                    ]);
                }
            }

            $hasCashboxTxn = \App\Models\CashboxTransaction::where('reference_type', \App\Models\SalesReturn::class)
                ->where('reference_id', $returnModel->id)
                ->exists();

            if (!$hasCashboxTxn && ($returnModel->refund_amount ?? 0) > 0) {
                $cashbox = \App\Models\Cashbox::first();
                if ($cashbox) {
                    \App\Models\CashboxTransaction::create([
                        'cashbox_id' => $cashbox->id,
                        'type' => 'out',
                        'amount' => $returnModel->refund_amount,
                        'balance_after' => $cashbox->current_balance - $returnModel->refund_amount,
                        'description' => "مرتجع مبيعات #{$returnModel->return_number} (sync)",
                        'reference_type' => \App\Models\SalesReturn::class,
                        'reference_id' => $returnModel->id,
                    ]);
                    $cashbox->decrement('current_balance', $returnModel->refund_amount);
                }
            }
        }

        $returnModel->fill($payload);
        $returnModel->synced_at = now();
        $returnModel->save();
    }

    protected function handlePulledInventoryCountApproval($count, array $payload): void
    {
        $ref = "COUNT-{$count->id}";
        $alreadyProcessed = \App\Models\StockMovement::where('reference', $ref)->exists();

        if (!$alreadyProcessed) {
            foreach ($count->items as $item) {
                $variance = ($item->counted_quantity ?? 0) - ($item->system_quantity ?? 0);
                if ($variance == 0) continue;

                $product = \App\Models\Product::find($item->product_id);
                if (!$product) continue;

                $currentStock = $product->total_stock ?? 0;

                if ($variance > 0) {
                    $batch = \App\Models\InventoryBatch::create([
                        'product_id' => $item->product_id,
                        'quantity' => $variance,
                        'cost_price' => $item->system_cost ?? 0,
                        'source' => 'adjustment',
                    ]);

                    \App\Models\StockMovement::create([
                        'product_id' => $item->product_id,
                        'batch_id' => $batch->id,
                        'type' => 'adjustment',
                        'reason' => "جرد #{$count->reference_number} - فائض (sync)",
                        'quantity' => $variance,
                        'before_quantity' => $currentStock,
                        'after_quantity' => $currentStock + $variance,
                        'cost_price' => $item->system_cost ?? 0,
                        'reference' => $ref,
                    ]);
                } else {
                    $remaining = abs($variance);
                    $batches = \App\Models\InventoryBatch::where('product_id', $item->product_id)
                        ->where('quantity', '>', 0)
                        ->orderBy('created_at')
                        ->get();

                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;
                        $deduct = min($batch->quantity, $remaining);
                        $batch->decrement('quantity', $deduct);
                        $remaining -= $deduct;

                        \App\Models\StockMovement::create([
                            'product_id' => $item->product_id,
                            'batch_id' => $batch->id,
                            'type' => 'adjustment',
                            'reason' => "جرد #{$count->reference_number} - نقص (sync)",
                            'quantity' => -$deduct,
                            'before_quantity' => $currentStock,
                            'after_quantity' => $currentStock - $deduct,
                            'cost_price' => $batch->cost_price,
                            'reference' => $ref,
                        ]);
                        $currentStock -= $deduct;
                    }
                }
            }
        }

        $count->fill($payload);
        $count->synced_at = now();
        $count->save();
    }

    public function getServerTimestamp(): ?Carbon
    {
        try {
            $response = $this->http()
                ->get(config('desktop.server_url') . '/api/v1/sync/timestamp');

            if ($response->successful()) {
                return Carbon::parse($response->json('timestamp'));
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    public function resolveConflict(SyncConflict $conflict, string $resolution, int $userId): bool
    {
        switch ($resolution) {
            case 'server_wins':
                return $conflict->resolveWithServer($userId);

            case 'local_wins':
                $log = SyncLog::where('syncable_type', $conflict->syncable_type)
                    ->where('syncable_id', $conflict->syncable_id)
                    ->where('sync_status', 'conflict')
                    ->first();

                if ($log) {
                    $log->resetForRetry();
                }

                return $conflict->resolveWithLocal($userId);

            default:
                return false;
        }
    }

    public function getSyncStatus(string $deviceId): array
    {
        $device = DeviceRegistration::where('device_id', $deviceId)->first();

        $pending = SyncLog::where('device_id', $deviceId)
            ->where('sync_status', 'pending')
            ->count();

        $failed = SyncLog::where('device_id', $deviceId)
            ->where('sync_status', 'failed')
            ->count();

        $conflicts = SyncConflict::where('device_id', $deviceId)
            ->where('resolution', 'pending')
            ->count();

        return [
            'device_id' => $deviceId,
            'last_sync' => $device?->last_sync_at?->toIso8601String(),
            'pending_count' => $pending,
            'failed_count' => $failed,
            'conflict_count' => $conflicts,
        ];
    }

    public function retryFailed(string $deviceId): int
    {
        return SyncLog::where('device_id', $deviceId)
            ->where('sync_status', 'failed')
            ->where('retry_count', '<', 5)
            ->update([
                'sync_status' => 'pending',
                'error_message' => null,
            ]);
    }
}
