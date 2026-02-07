<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SyncConflict extends Model
{
    protected $fillable = [
        'device_id',
        'syncable_type',
        'syncable_id',
        'local_data',
        'server_data',
        'resolution',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'local_data' => 'array',
        'server_data' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function syncable(): MorphTo
    {
        return $this->morphTo();
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(DeviceRegistration::class, 'device_id', 'device_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending($query)
    {
        return $query->where('resolution', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('resolution', '!=', 'pending');
    }

    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function isPending(): bool
    {
        return $this->resolution === 'pending';
    }

    public function resolveWithServer(int $userId): bool
    {
        $model = $this->syncable;

        if ($model) {
            $model->update($this->server_data);
        }

        return $this->update([
            'resolution' => 'server_wins',
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    public function resolveWithLocal(int $userId): bool
    {
        return $this->update([
            'resolution' => 'local_wins',
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    public function resolveWithMerge(array $mergedData, int $userId): bool
    {
        $model = $this->syncable;

        if ($model) {
            $model->update($mergedData);
        }

        return $this->update([
            'resolution' => 'merged',
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    public function getResolutionArabicAttribute(): string
    {
        return match ($this->resolution) {
            'pending' => 'قيد الانتظار',
            'server_wins' => 'بيانات السيرفر',
            'local_wins' => 'البيانات المحلية',
            'merged' => 'دمج البيانات',
            default => $this->resolution,
        };
    }
}
