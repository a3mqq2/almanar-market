<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SyncLog extends Model
{
    protected $fillable = [
        'device_id',
        'syncable_type',
        'syncable_id',
        'action',
        'payload',
        'local_timestamp',
        'synced_at',
        'server_id',
        'sync_status',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'payload' => 'array',
        'local_timestamp' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function syncable(): MorphTo
    {
        return $this->morphTo();
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(DeviceRegistration::class, 'device_id', 'device_id');
    }

    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }

    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('syncable_type', $type);
    }

    public function markAsSynced(int $serverId = null): bool
    {
        return $this->update([
            'sync_status' => 'synced',
            'synced_at' => now(),
            'server_id' => $serverId,
        ]);
    }

    public function markAsFailed(string $message): bool
    {
        return $this->update([
            'sync_status' => 'failed',
            'error_message' => $message,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function markAsConflict(): bool
    {
        return $this->update([
            'sync_status' => 'conflict',
        ]);
    }

    public function resetForRetry(): bool
    {
        return $this->update([
            'sync_status' => 'pending',
            'error_message' => null,
        ]);
    }
}
