<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('device')->group(function () {
        Route::post('/register', [DeviceController::class, 'register']);
        Route::post('/activate', [DeviceController::class, 'activate']);
    });

    Route::get('/sync/timestamp', [SyncController::class, 'timestamp']);

    Route::prefix('sync')->middleware('verify.device.token')->group(function () {
        Route::post('/push', [SyncController::class, 'push']);
        Route::get('/pull', [SyncController::class, 'pull']);
        Route::get('/status', [SyncController::class, 'status']);
        Route::post('/resolve-conflict', [SyncController::class, 'resolveConflict']);
        Route::post('/retry-failed', [SyncController::class, 'retryFailed']);
    });

    Route::middleware('verify.device.token')->group(function () {
        Route::get('/device/status', [DeviceController::class, 'status']);
        Route::post('/device/heartbeat', [DeviceController::class, 'heartbeat']);
    });

    Route::get('/sync/compare', function () {
        $sales = \App\Models\Sale::select('id', 'invoice_number', 'total', 'status', 'local_uuid', 'device_id', 'created_at')
            ->orderBy('id')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'invoice_number' => $s->invoice_number,
                'total' => (float) $s->total,
                'status' => $s->status,
                'local_uuid' => $s->local_uuid,
                'device_id' => $s->device_id,
                'created_at' => $s->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'sales_count' => $sales->count(),
            'sale_items_count' => \App\Models\SaleItem::count(),
            'sales' => $sales,
        ]);
    })->middleware('verify.device.token');
});
