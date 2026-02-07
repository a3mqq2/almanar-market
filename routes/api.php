<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('device')->group(function () {
        Route::post('/register', [DeviceController::class, 'register']);
        Route::post('/activate', [DeviceController::class, 'activate']);
    });

    Route::prefix('sync')->middleware('verify.device.token')->group(function () {
        Route::post('/push', [SyncController::class, 'push']);
        Route::get('/pull', [SyncController::class, 'pull']);
        Route::get('/timestamp', [SyncController::class, 'timestamp']);
        Route::get('/status', [SyncController::class, 'status']);
        Route::post('/resolve-conflict', [SyncController::class, 'resolveConflict']);
        Route::post('/retry-failed', [SyncController::class, 'retryFailed']);
    });

    Route::middleware('verify.device.token')->group(function () {
        Route::get('/device/status', [DeviceController::class, 'status']);
        Route::post('/device/heartbeat', [DeviceController::class, 'heartbeat']);
    });
});
