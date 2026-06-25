<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantStaffMember extends Model
{
    protected $fillable = [
        'restaurant_id',
        'user_id',
        'role',
        'permissions',
        'is_active',
        'invited_by',
        'last_access_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_access_at' => 'datetime',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function resolvedPermissions(): array
    {
        $rolePermissions = (array) config('restaurant_permissions.roles.' . $this->role, []);
        $customPermissions = array_values(array_filter((array) $this->permissions));

        return array_values(array_unique(array_merge($rolePermissions, $customPermissions)));
    }

    public function allows(string $permission): bool
    {
        $permissions = $this->resolvedPermissions();

        return in_array('all', $permissions, true)
            || in_array($permission, $permissions, true);
    }
}
