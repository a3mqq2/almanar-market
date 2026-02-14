<?php

namespace App\Console\Commands;

use App\Models\DeviceRegistration;
use App\Services\SyncService;
use Illuminate\Console\Command;

class SyncDevices extends Command
{
    protected $signature = 'sync:devices {--device= : Specific device ID to sync}';

    protected $description = 'Sync all active devices with the server';

    public function handle(SyncService $syncService): int
    {
        $deviceId = $this->option('device');

        if ($deviceId) {
            $devices = DeviceRegistration::where('device_id', $deviceId)->get();
        } else {
            $devices = DeviceRegistration::active()->get();
        }

        if ($devices->isEmpty()) {
            $this->warn('No active devices found.');
            return 0;
        }

        foreach ($devices as $device) {
            $this->info("Syncing device: {$device->device_id}");

            $pushResult = $syncService->pushChanges($device->device_id);
            if ($pushResult['success']) {
                $this->info("  Pushed: {$pushResult['pushed']} changes");
            } else {
                $this->error("  Push failed: " . ($pushResult['message'] ?? 'Unknown error'));
            }

            $pullResult = $syncService->pullChanges($device->device_id, $device->last_sync_at);
            if ($pullResult['success']) {
                $this->info("  Pulled: {$pullResult['pulled']} changes");
            } else {
                $this->error("  Pull failed: " . ($pullResult['message'] ?? 'Unknown error'));
            }
        }

        $this->info('Sync completed.');
        return 0;
    }
}
