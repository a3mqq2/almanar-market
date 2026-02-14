<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Syncable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function cashboxes(): BelongsToMany
    {
        return $this->belongsToMany(Cashbox::class, 'user_cashboxes')->withTimestamps();
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function isManager(): bool
    {
        return $this->role == 'manager';
    }

    public function isCashier(): bool
    {
        return $this->role == 'cashier';
    }

    public function isPriceChecker(): bool
    {
        return $this->role == 'price_checker';
    }

    public function isActive(): bool
    {
        return $this->status == true;
    }

    public function canAccessCashbox(int $cashboxId): bool
    {
        if ($this->isManager()) {
            return true;
        }

        return $this->cashboxes()->where('cashbox_id', $cashboxId)->exists();
    }

    public function getAccessibleCashboxes()
    {
        if ($this->isManager()) {
            return Cashbox::active()->get();
        }

        return $this->cashboxes()->where('status', true)->get();
    }

    public function getRoleArabicAttribute(): string
    {
        return match ($this->role) {
            'manager' => 'مدير',
            'cashier' => 'كاشير',
            'price_checker' => 'جهاز الأسعار',
            default => $this->role,
        };
    }

    public function getStatusArabicAttribute(): string
    {
        return $this->status ? 'نشط' : 'غير نشط';
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeManagers($query)
    {
        return $query->where('role', 'manager');
    }

    public function scopeCashiers($query)
    {
        return $query->where('role', 'cashier');
    }
}
