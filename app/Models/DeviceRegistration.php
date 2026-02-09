<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DeviceRegistration extends Model
{
    protected $fillable = [
        'device_id',
        'device_name',
        'license_key',
        'api_token',
        'user_id',
        'last_sync_at',
        'last_seen_at',
        'ip_address',
        'app_version',
        'status',
        'activated_at',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    protected $hidden = [
        'api_token',
        'license_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'device_id', 'device_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByToken($query, string $token)
    {
        return $query->where('api_token', $token);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public static function generateDeviceId(): string
    {
        return Str::uuid()->toString();
    }

    public function isActive(): bool
    {
        return $this->status == 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status == 'suspended';
    }

    public function isRevoked(): bool
    {
        return $this->status == 'revoked';
    }

    public function updateLastSeen(string $ipAddress = null): bool
    {
        $data = ['last_seen_at' => now()];

        if ($ipAddress) {
            $data['ip_address'] = $ipAddress;
        }

        return $this->update($data);
    }

    public function updateLastSync(): bool
    {
        return $this->update(['last_sync_at' => now()]);
    }

    public function suspend(): bool
    {
        return $this->update(['status' => 'suspended']);
    }

    public function revoke(): bool
    {
        return $this->update(['status' => 'revoked']);
    }

    public function activate(): bool
    {
        return $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    public function regenerateToken(): string
    {
        $token = self::generateToken();
        $this->update(['api_token' => $token]);
        return $token;
    }

    public function getPendingSyncCount(): int
    {
        return $this->syncLogs()->pending()->count();
    }
}
