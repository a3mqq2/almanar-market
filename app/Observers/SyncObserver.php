<?php

namespace App\Observers;

use App\Models\SyncLog;
use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

abstract class SyncObserver
{
    protected function shouldSync(): bool
    {
        return config('desktop.mode', false) && config('desktop.device_id');
    }

    protected function logSync(Model $model, string $action): void
    {
        if (!$this->shouldSync()) {
            return;
        }

        if (in_array(Syncable::class, class_uses_recursive($model))) {
            return;
        }

        SyncLog::create([
            'device_id' => config('desktop.device_id'),
            'syncable_type' => get_class($model),
            'syncable_id' => $model->id,
            'action' => $action,
            'payload' => $this->getPayload($model),
            'local_timestamp' => now(),
            'sync_status' => 'pending',
        ]);
    }

    protected function getPayload(Model $model): array
    {
        return $model->toArray();
    }

    public function created(Model $model): void
    {
        $this->logSync($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->logSync($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->logSync($model, 'deleted');
    }
}
