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
                $changes[] = [
                    'id' => $log->id,
                    'type' => $log->syncable_type,
                    'record_id' => $log->syncable_id,
                    'action' => $log->action,
                    'payload' => $log->payload,
                    'timestamp' => $log->local_timestamp->toIso8601String(),
                ];
            }
        }

        return $changes;
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
