<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'key',
        'group',
        'label',
        'parent_key',
        'sort_order',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')->withTimestamps();
    }

    public function children()
    {
        return self::where('parent_key', $this->key)->orderBy('sort_order')->get();
    }

    public function isParent(): bool
    {
        return is_null($this->parent_key);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_key');
    }

    public function scopeChildren($query, string $parentKey)
    {
        return $query->where('parent_key', $parentKey);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public static function grouped()
    {
        $permissions = self::orderBy('sort_order')->get();

        $grouped = [];
        foreach ($permissions as $permission) {
            if ($permission->isParent()) {
                $grouped[$permission->key] = [
                    'permission' => $permission,
                    'children' => [],
                ];
            }
        }

        foreach ($permissions as $permission) {
            if (!$permission->isParent() && isset($grouped[$permission->parent_key])) {
                $grouped[$permission->parent_key]['children'][] = $permission;
            }
        }

        return $grouped;
    }
}
