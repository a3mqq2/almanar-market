<?php

namespace App\Services;

use App\Models\DeviceRegistration;
use App\Models\SyncConflict;
use App\Models\SyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncService
{
    protected function curlRequest(string $method, string $url, array $data = [], ?string $token = null): array
    {
        $ch = curl_init();

        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $attempts = 0;
        $maxAttempts = 3;
        $response = false;

        while ($attempts < $maxAttempts) {
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response !== false && empty($error)) {
                break;
            }

            $attempts++;
            if ($attempts < $maxAttempts) {
                usleep(200000);
            }
        }

        curl_close($ch);

        if ($response === false || !empty($error)) {
            return [
                'successful' => false,
                'status' => 0,
                'body' => null,
                'error' => $error ?? 'Request failed',
            ];
        }

        $decoded = json_decode($response, true);

        return [
            'successful' => $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'body' => $decoded ?? $response,
            'error' => null,
        ];
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
            $response = $this->curlRequest(
                'POST',
                config('desktop.server_url') . '/api/v1/sync/push',
                ['device_id' => $deviceId, 'changes' => $changes],
                $device->api_token
            );

            if ($response['successful']) {
                $result = $response['body'];
                $this->processPushResponse($result);
                $device->updateLastSync();

                return [
                    'success' => true,
                    'pushed' => count($changes),
                    'synced' => $result['synced'] ?? [],
                    'conflicts' => $result['conflicts'] ?? [],
                ];
            }

            return ['success' => false, 'message' => $response['error'] ?? json_encode($response['body'])];
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

            $response = $this->curlRequest(
                'GET',
                config('desktop.server_url') . '/api/v1/sync/pull',
                $params,
                $device->api_token
            );

            if ($response['successful']) {
                $result = $response['body'];
                $applied = $this->applyPulledChanges($result['changes'] ?? []);
                $device->updateLastSync();

                return [
                    'success' => true,
                    'pulled' => count($result['changes'] ?? []),
                    'applied' => $applied,
                ];
            }

            return ['success' => false, 'message' => $response['error'] ?? json_encode($response['body'])];
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

                        $model->fill($payload);
                        $model->synced_at = now();
                        $model->save();
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

    public function getServerTimestamp(): ?Carbon
    {
        try {
            $response = $this->curlRequest(
                'GET',
                config('desktop.server_url') . '/api/v1/sync/timestamp'
            );

            if ($response['successful'] && isset($response['body']['timestamp'])) {
                return Carbon::parse($response['body']['timestamp']);
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
