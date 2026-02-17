<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncConflict;
use App\Services\SyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncController extends Controller
{
    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string|size:36',
            'changes' => 'required|array',
            'changes.*.type' => 'required|string',
            'changes.*.record_id' => 'required|integer',
            'changes.*.action' => 'required|in:created,updated,deleted',
            'changes.*.payload' => 'required|array',
            'changes.*.timestamp' => 'required|date',
        ]);

        $deviceId = $request->input('device_id');
        $changes = $request->input('changes');

        $synced = [];
        $conflicts = [];
        $errors = [];
        $idMap = [];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::beginTransaction();

        foreach ($changes as $change) {
            try {
                $change['payload'] = $this->remapForeignKeys($change['type'], $change['payload'], $idMap, $deviceId);
                $result = $this->processChange($change, $deviceId);

                if ($result['status'] == 'synced') {
                    $idMap[$change['type']][$change['record_id']] = $result['server_id'];
                    $synced[] = [
                        'log_id' => $change['id'] ?? null,
                        'local_id' => $change['record_id'],
                        'server_id' => $result['server_id'],
                    ];
                } elseif ($result['status'] == 'conflict') {
                    $conflicts[] = [
                        'log_id' => $change['id'] ?? null,
                        'local_id' => $change['record_id'],
                        'server_data' => $result['server_data'],
                    ];
                } elseif ($result['status'] == 'error') {
                    $errors[] = [
                        'log_id' => $change['id'] ?? null,
                        'type' => $change['type'],
                        'action' => $change['action'],
                        'record_id' => $change['record_id'],
                        'message' => $result['message'] ?? 'Unknown error',
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'log_id' => $change['id'] ?? null,
                    'type' => $change['type'] ?? '',
                    'action' => $change['action'] ?? '',
                    'record_id' => $change['record_id'] ?? 0,
                    'message' => $e->getMessage(),
                ];
            }
        }

        DB::commit();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'success' => true,
            'synced' => $synced,
            'conflicts' => $conflicts,
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function processChange(array $change, string $deviceId): array
    {
        $modelClass = $change['type'];
        $action = $change['action'];
        $payload = $change['payload'];
        $localId = $change['record_id'];
        $timestamp = Carbon::parse($change['timestamp']);

        if (!class_exists($modelClass)) {
            return ['status' => 'error', 'message' => 'Unknown model type'];
        }

        switch ($action) {
            case 'created':
                $existing = $modelClass::where('local_uuid', $payload['local_uuid'] ?? null)
                    ->where('device_id', $deviceId)
                    ->first();

                if (!$existing) {
                    $existing = $this->findByCompositeKey($modelClass, $payload);
                }

                if ($existing) {
                    $existing->fill($payload);
                    $existing->local_uuid = $payload['local_uuid'] ?? null;
                    $existing->device_id = $deviceId;
                    $existing->synced_at = now();
                    $existing->save();

                    return [
                        'status' => 'synced',
                        'server_id' => $existing->id,
                    ];
                }

                $this->resolveUniqueConflict($modelClass, $payload);

                $model = new $modelClass();
                $model->fill($payload);
                $model->local_uuid = $payload['local_uuid'] ?? null;
                $model->device_id = $deviceId;
                $model->synced_at = now();
                if (isset($payload['created_at']) && $payload['created_at']) {
                    $model->created_at = $payload['created_at'];
                }
                $model->save();

                return [
                    'status' => 'synced',
                    'server_id' => $model->id,
                ];

            case 'updated':
                $model = $modelClass::where('local_uuid', $payload['local_uuid'] ?? null)
                    ->where('device_id', $deviceId)
                    ->first();

                if (!$model) {
                    $model = $modelClass::find($localId);
                }

                if (!$model) {
                    $model = $this->findByCompositeKey($modelClass, $payload);
                }

                if (!$model) {
                    $this->resolveUniqueConflict($modelClass, $payload);

                    $model = new $modelClass();
                    $model->fill($payload);
                    $model->local_uuid = $payload['local_uuid'] ?? null;
                    $model->device_id = $deviceId;
                    $model->synced_at = now();
                    if (isset($payload['created_at']) && $payload['created_at']) {
                        $model->created_at = $payload['created_at'];
                    }
                    $model->save();

                    return [
                        'status' => 'synced',
                        'server_id' => $model->id,
                    ];
                }

                $this->resolveUniqueConflict($modelClass, $payload, $model->id);

                if ($modelClass === 'App\Models\Sale' && ($payload['status'] ?? '') === 'cancelled' && $model->status !== 'cancelled') {
                    $this->handleSaleCancellation($model, $payload);
                    return ['status' => 'synced', 'server_id' => $model->id];
                }

                if ($modelClass === 'App\Models\Purchase' && ($payload['status'] ?? '') === 'cancelled' && $model->status !== 'cancelled') {
                    $this->handlePurchaseCancellation($model, $payload);
                    return ['status' => 'synced', 'server_id' => $model->id];
                }

                if ($modelClass === 'App\Models\SalesReturn') {
                    $this->handleSalesReturn($model, $payload);
                    return ['status' => 'synced', 'server_id' => $model->id];
                }

                if ($modelClass === 'App\Models\InventoryCount' && ($payload['status'] ?? '') === 'approved' && $model->status !== 'approved') {
                    $this->handleInventoryCountApproval($model, $payload);
                    return ['status' => 'synced', 'server_id' => $model->id];
                }

                $model->fill($payload);
                $model->local_uuid = $payload['local_uuid'] ?? null;
                $model->synced_at = now();
                $model->save();

                return [
                    'status' => 'synced',
                    'server_id' => $model->id,
                ];

            case 'deleted':
                $model = $modelClass::where('local_uuid', $payload['local_uuid'] ?? null)
                    ->where('device_id', $deviceId)
                    ->first();

                if (!$model) {
                    $model = $modelClass::find($localId);
                }

                if ($model) {
                    $model->delete();
                }

                return [
                    'status' => 'synced',
                    'server_id' => $localId,
                ];
        }

        return ['status' => 'error', 'message' => 'Unknown action'];
    }

    protected function getCompositeUniqueKeys(string $modelClass): ?array
    {
        $map = [
            'App\Models\ProductUnit' => ['product_id', 'unit_id'],
            'App\Models\ProductBarcode' => ['product_id', 'barcode'],
            'App\Models\ShiftCashbox' => ['shift_id', 'cashbox_id'],
            'App\Models\InventoryCountItem' => ['inventory_count_id', 'product_id'],
        ];

        return $map[$modelClass] ?? null;
    }

    protected function findByCompositeKey(string $modelClass, array $payload, int $excludeId = 0)
    {
        $keys = $this->getCompositeUniqueKeys($modelClass);
        if (!$keys) {
            return null;
        }

        $query = $modelClass::query();
        foreach ($keys as $key) {
            if (!isset($payload[$key])) {
                return null;
            }
            $query->where($key, $payload[$key]);
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
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

    protected function resolveUniqueConflict(string $modelClass, array $payload, int $excludeId = 0): void
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
                ->update([$uniqueField => $payload[$uniqueField] . '-S' . $conflicting->id]);
        }
    }

    protected function getForeignKeyMapping(): array
    {
        return [
            'App\Models\SaleItem' => ['sale_id' => 'App\Models\Sale'],
            'App\Models\SalePayment' => ['sale_id' => 'App\Models\Sale'],
            'App\Models\SaleReturnItem' => ['sales_return_id' => 'App\Models\SalesReturn'],
            'App\Models\PurchaseItem' => ['purchase_id' => 'App\Models\Purchase'],
            'App\Models\ShiftCashbox' => ['shift_id' => 'App\Models\Shift'],
            'App\Models\CashboxTransaction' => ['shift_id' => 'App\Models\Shift'],
            'App\Models\StockMovement' => ['sale_id' => 'App\Models\Sale', 'purchase_id' => 'App\Models\Purchase'],
            'App\Models\InventoryCountItem' => ['inventory_count_id' => 'App\Models\InventoryCount'],
        ];
    }

    protected function remapForeignKeys(string $modelClass, array $payload, array $idMap, string $deviceId): array
    {
        $fkMapping = $this->getForeignKeyMapping();
        if (!isset($fkMapping[$modelClass])) {
            return $payload;
        }

        foreach ($fkMapping[$modelClass] as $fkField => $parentClass) {
            if (!isset($payload[$fkField]) || empty($payload[$fkField])) {
                continue;
            }

            $localId = $payload[$fkField];

            if (isset($idMap[$parentClass][$localId])) {
                $payload[$fkField] = $idMap[$parentClass][$localId];
                continue;
            }

            $parent = $parentClass::find($localId);
            if ($parent) {
                continue;
            }

            $parentByUuid = null;
            if (isset($payload['local_uuid'])) {
                $tableName = (new $parentClass)->getTable();
                if (Schema::hasColumn($tableName, 'device_id')) {
                    $parentByUuid = $parentClass::where('device_id', $deviceId)
                        ->where('id', '!=', $localId)
                        ->orderBy('id', 'desc')
                        ->first();
                }
            }

            if ($parentByUuid) {
                $payload[$fkField] = $parentByUuid->id;
            }
        }

        return $payload;
    }

    protected function handleSaleCancellation($sale, array $payload): void
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
                        'reason' => "إلغاء فاتورة #{$sale->invoice_number} (sync)",
                        'quantity' => $item->base_quantity,
                        'before_quantity' => $currentStock,
                        'after_quantity' => $currentStock + $item->base_quantity,
                        'cost_price' => $batch->cost_price,
                        'reference' => $ref,
                        'user_id' => $payload['cancelled_by'] ?? null,
                    ]);
                }
            }

            $hasReversal = \App\Models\CashboxTransaction::where('reference_type', \App\Models\Sale::class)
                ->where('reference_id', $sale->id)
                ->where('type', 'out')
                ->exists();

            if (!$hasReversal) {
                $originalTxns = \App\Models\CashboxTransaction::where('reference_type', \App\Models\Sale::class)
                    ->where('reference_id', $sale->id)
                    ->where('type', 'in')
                    ->get();

                foreach ($originalTxns as $original) {
                    $cashbox = \App\Models\Cashbox::find($original->cashbox_id);
                    if ($cashbox) {
                        \App\Models\CashboxTransaction::create([
                            'cashbox_id' => $cashbox->id,
                            'shift_id' => $original->shift_id,
                            'type' => 'out',
                            'amount' => $original->amount,
                            'balance_after' => $cashbox->current_balance - $original->amount,
                            'description' => "إلغاء فاتورة #{$sale->invoice_number} (sync)",
                            'reference_type' => \App\Models\Sale::class,
                            'reference_id' => $sale->id,
                            'user_id' => $payload['cancelled_by'] ?? null,
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
                            'description' => "إلغاء فاتورة #{$sale->invoice_number} (sync)",
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

    protected function handlePurchaseCancellation($purchase, array $payload): void
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
                ->whereRaw("description LIKE '%إلغاء%'")
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

    protected function handleSalesReturn($returnModel, array $payload): void
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

    protected function handleInventoryCountApproval($count, array $payload): void
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

    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string|size:36',
            'since' => 'nullable|date',
        ]);

        $deviceId = $request->input('device_id');
        $since = $request->input('since') ? Carbon::parse($request->input('since')) : null;

        $syncableModels = [
            \App\Models\User::class,
            \App\Models\Unit::class,
            \App\Models\Supplier::class,
            \App\Models\Customer::class,
            \App\Models\Cashbox::class,
            \App\Models\PaymentMethod::class,
            \App\Models\ExpenseCategory::class,
            \App\Models\Product::class,
            \App\Models\ProductUnit::class,
            \App\Models\ProductBarcode::class,
            \App\Models\InventoryBatch::class,
            \App\Models\Purchase::class,
            \App\Models\PurchaseItem::class,
            \App\Models\Shift::class,
            \App\Models\ShiftCashbox::class,
            \App\Models\Sale::class,
            \App\Models\SaleItem::class,
            \App\Models\SalePayment::class,
            \App\Models\SalesReturn::class,
            \App\Models\SaleReturnItem::class,
            \App\Models\Expense::class,
            \App\Models\StockMovement::class,
            \App\Models\CashboxTransaction::class,
            \App\Models\CustomerTransaction::class,
            \App\Models\SupplierTransaction::class,
            \App\Models\InventoryCount::class,
            \App\Models\InventoryCountItem::class,
            \App\Models\UserActivityLog::class,
        ];

        $changes = [];

        foreach ($syncableModels as $modelClass) {
            try {
                $tableName = (new $modelClass)->getTable();
                $hasDeviceId = Schema::hasColumn($tableName, 'device_id');

                $query = $modelClass::query();

                if ($since) {
                    $query->where('updated_at', '>', $since);
                }

                if ($hasDeviceId) {
                    $query->where(function ($q) use ($deviceId) {
                        $q->whereNull('device_id')
                            ->orWhere('device_id', '!=', $deviceId);
                    });
                }

                $records = $query->get();

                foreach ($records as $record) {
                    $changes[] = [
                        'id' => $record->id,
                        'type' => $modelClass,
                        'action' => 'updated',
                        'payload' => $record->makeVisible($record->getHidden())->toArray(),
                        'timestamp' => $record->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json([
            'success' => true,
            'changes' => $changes,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function timestamp(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string|size:36',
        ]);

        $status = $this->syncService->getSyncStatus($request->input('device_id'));

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    public function resolveConflict(Request $request): JsonResponse
    {
        $request->validate([
            'conflict_id' => 'required|integer|exists:sync_conflicts,id',
            'resolution' => 'required|in:server_wins,local_wins',
        ]);

        $conflict = SyncConflict::findOrFail($request->input('conflict_id'));
        $device = $request->attributes->get('device');

        $result = $this->syncService->resolveConflict(
            $conflict,
            $request->input('resolution'),
            $device->user_id
        );

        return response()->json([
            'success' => $result,
        ]);
    }

    public function retryFailed(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string|size:36',
        ]);

        $count = $this->syncService->retryFailed($request->input('device_id'));

        return response()->json([
            'success' => true,
            'retried' => $count,
        ]);
    }
}
