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

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::beginTransaction();

        try {
            foreach ($changes as $change) {
                $result = $this->processChange($change, $deviceId);

                if ($result['status'] == 'synced') {
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
                }
            }

            DB::commit();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return response()->json([
                'success' => true,
                'synced' => $synced,
                'conflicts' => $conflicts,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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

                if ($existing) {
                    return [
                        'status' => 'synced',
                        'server_id' => $existing->id,
                    ];
                }

                $this->resolveUniqueConflict($modelClass, $payload);

                $model = new $modelClass();
                $model->fill($payload);
                $model->device_id = $deviceId;
                $model->synced_at = now();
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
                    return ['status' => 'error', 'message' => 'Record not found'];
                }

                if ($model->updated_at > $timestamp) {
                    return [
                        'status' => 'conflict',
                        'server_data' => $model->toArray(),
                    ];
                }

                $this->resolveUniqueConflict($modelClass, $payload, $model->id);

                $model->fill($payload);
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
