<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Role extends Model
{
    use SoftDeletes, LogsActivity;

    /**
     * The log name for activity logging.
     */
    protected static string $activityLogName = 'role-management';

    /**
     * The attributes to log for activity.
     */
    protected static array $loggableAttributes = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::creating(function ($role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });

        // Prevent deletion of system roles
        static::deleting(function ($role) {
            if ($role->is_system) {
                return false;
            }

            // Check if role is in use
            if ($role->users()->exists()) {
                return false;
            }

            return true;
        });
    }

    /**
     * Get the permissions for the role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Get the users for the role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if the role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Check by slug or name
        return $this->permissions()
            ->where(function ($query) use ($permission) {
                $query->where('slug', $permission)
                    ->orWhere('name', $permission);
            })
            ->exists();
    }

    /**
     * Check if the role has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the role has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign permissions to the role.
     */
    public function assignPermissions(array $permissions): void
    {
        $permissionIds = Permission::whereIn('slug', $permissions)
            ->orWhereIn('id', $permissions)
            ->pluck('id');

        $this->permissions()->syncWithoutDetaching($permissionIds);
    }

    /**
     * Remove permissions from the role.
     */
    public function removePermissions(array $permissions): void
    {
        $permissionIds = Permission::whereIn('slug', $permissions)
            ->orWhereIn('id', $permissions)
            ->pluck('id');

        $this->permissions()->detach($permissionIds);
    }

    /**
     * Sync permissions for the role.
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = Permission::whereIn('slug', $permissions)
            ->orWhereIn('id', $permissions)
            ->pluck('id');

        $this->permissions()->sync($permissionIds);
    }

    /**
     * Scope a query to only include system roles.
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to exclude system roles.
     */
    public function scopeNonSystem(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    /**
     * Check if this is the super admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->slug === 'super_admin';
    }

    /**
     * Check if this is the vendor role.
     */
    public function isVendor(): bool
    {
        return $this->slug === 'vendor';
    }

    /**
     * Check if this is the accountant role.
     */
    public function isAccountant(): bool
    {
        return $this->slug === 'accountant';
    }
}