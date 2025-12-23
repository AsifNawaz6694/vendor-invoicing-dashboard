<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

trait HasRoles
{
    /**
     * Boot the trait.
     */
    public static function bootHasRoles()
    {
        // Clear permission cache when user role changes
        static::updating(function ($model) {
            if ($model->isDirty('role_id')) {
                $model->clearPermissionCache();
            }
        });
    }

    /**
     * Get the role that the user belongs to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        if (!$this->role) {
            return false;
        }

        // Check by slug or name
        return $this->role->slug === $role || $this->role->name === $role;
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->isSuperAdmin();
    }

    /**
     * Check if the user is a vendor.
     */
    public function isVendor(): bool
    {
        return $this->role && $this->role->isVendor();
    }

    /**
     * Check if the user is an accountant.
     */
    public function isAccountant(): bool
    {
        return $this->role && $this->role->isAccountant();
    }

    /**
     * Get all permissions for the user through their role.
     */
    public function permissions()
    {
        if (!$this->role) {
            return collect();
        }

        return $this->role->permissions;
    }

    /**
     * Get cached permissions for the user.
     */
    public function getCachedPermissions()
    {
        if ($this->isSuperAdmin()) {
            // Super admin has all permissions
            return Permission::all();
        }

        $cacheKey = $this->getPermissionCacheKey();

        return Cache::remember($cacheKey, config('cache.ttl', 3600), function () {
            if (!$this->role) {
                return collect();
            }
            return $this->role->permissions()->get();
        });
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin bypasses all permission checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        // Use cached permissions for performance
        $permissions = $this->getCachedPermissions();

        return $permissions->contains(function ($perm) use ($permission) {
            return $perm->slug === $permission || $perm->name === $permission;
        });
    }

    /**
     * Check if the user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        // Super admin bypasses all permission checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        // Super admin bypasses all permission checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user can perform an action on a model.
     */
    public function canAccess(string $permission, $model = null): bool
    {
        // Super admin can access everything
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check base permission
        if (!$this->hasPermission($permission)) {
            return false;
        }

        // Additional checks for specific models
        if ($model && method_exists($this, 'canAccessModel')) {
            return $this->canAccessModel($model);
        }

        return true;
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole($role): void
    {
        if ($role instanceof Role) {
            $this->role()->associate($role);
        } else {
            $roleModel = Role::where('slug', $role)
                ->orWhere('name', $role)
                ->orWhere('id', $role)
                ->first();

            if ($roleModel) {
                $this->role()->associate($roleModel);
            }
        }

        $this->save();
        $this->clearPermissionCache();
    }

    /**
     * Sync roles (for compatibility with multiple roles interface).
     * Since we only support one role, this just assigns the first role.
     */
    public function syncRoles(array $roles): void
    {
        if (!empty($roles)) {
            $this->assignRole($roles[0]);
        } else {
            $this->removeRole();
        }
    }

    /**
     * Get roles collection (for compatibility).
     * Returns a collection with the single role.
     */
    public function roles()
    {
        return $this->role ? collect([$this->role]) : collect();
    }

    /**
     * Remove the user's role.
     */
    public function removeRole(): void
    {
        $this->role()->dissociate();
        $this->save();
        $this->clearPermissionCache();
    }

    /**
     * Get the cache key for user permissions.
     */
    protected function getPermissionCacheKey(): string
    {
        return 'user_permissions_' . $this->id;
    }

    /**
     * Clear the permission cache for the user.
     */
    public function clearPermissionCache(): void
    {
        Cache::forget($this->getPermissionCacheKey());
    }

    /**
     * Refresh the permission cache for the user.
     */
    public function refreshPermissionCache(): void
    {
        $this->clearPermissionCache();
        $this->getCachedPermissions();
    }
}