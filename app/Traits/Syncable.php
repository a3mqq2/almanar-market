<?php

namespace App\Traits;

use App\Models\SyncLog;
use Illuminate\Support\Str;

trait Syncable
{
    public static function bootSyncable(): void
    {
        static::creating(function ($model) {
            if (config('desktop.mode') && empty($model->local_uuid)) {
                $model->local_uuid = Str::uuid()->toString();
                $model->device_id = config('desktop.device_id');
            }
        });

        static::created(function ($model) {
            if (config('desktop.mode')) {
                $model->logSyncEvent('created');
            }
        });

        static::updated(function ($model) {
            if (config('desktop.mode')) {
                $model->logSyncEvent('updated');
            }
        });

        static::deleted(function ($model) {
            if (config('desktop.mode')) {
                $model->logSyncEvent('deleted');
            }
        });
    }

    public function logSyncEvent(string $action): void
    {
        SyncLog::create([
            'device_id' => config('desktop.device_id'),
            'syncable_type' => get_class($this),
            'syncable_id' => $this->id,
            'action' => $action,
            'payload' => $this->getSyncPayload(),
            'local_timestamp' => now(),
            'sync_status' => 'pending',
        ]);
    }

    public function getSyncPayload(): array
    {
        $attributes = $this->getAttributes();

        unset($attributes['synced_at']);

        if (method_exists($this, 'getSyncRelations')) {
            foreach ($this->getSyncRelations() as $relation) {
                if ($this->relationLoaded($relation)) {
                    $attributes[$relation] = $this->$relation->toArray();
                }
            }
        }

        return $attributes;
    }

    public function applySyncData(array $data): bool
    {
        $syncFields = ['device_id', 'local_uuid', 'synced_at', 'sync_version'];

        foreach ($syncFields as $field) {
            unset($data[$field]);
        }

        return $this->update($data);
    }

    public function needsSync(): bool
    {
        return $this->synced_at === null || $this->updated_at > $this->synced_at;
    }

    public function markAsSynced(): bool
    {
        return $this->update([
            'synced_at' => now(),
            'sync_version' => $this->sync_version + 1,
        ]);
    }

    public function syncLogs()
    {
        return $this->morphMany(SyncLog::class, 'syncable');
    }

    public function getLatestSyncLog()
    {
        return $this->syncLogs()->latest()->first();
    }

    public function hasPendingSync(): bool
    {
        return $this->syncLogs()->pending()->exists();
    }
}
