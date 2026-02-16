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
