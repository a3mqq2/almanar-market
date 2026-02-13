<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    use Syncable;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $action,
        ?string $description = null,
        ?int $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public function getActionArabicAttribute(): string
    {
        return match ($this->action) {
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
            'login_failed' => 'فشل تسجيل الدخول',
            'password_changed' => 'تغيير كلمة المرور',
            'role_changed' => 'تغيير الصلاحية',
            'cashbox_assigned' => 'تعيين خزينة',
            'cashbox_removed' => 'إزالة خزينة',
            'status_changed' => 'تغيير الحالة',
            'shift_opened' => 'فتح وردية',
            'shift_closed' => 'إغلاق وردية',
            'sale_completed' => 'إتمام بيع',
            'inventory_count_started' => 'بدء جرد مخزون',
            'inventory_count_completed' => 'اكتمال جرد مخزون',
            'inventory_count_approved' => 'اعتماد جرد مخزون',
            'inventory_count_cancelled' => 'إلغاء جرد مخزون',
            'inventory_variance_recorded' => 'تسجيل فروقات جرد',
            default => $this->action,
        };
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
