<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Permission extends Model
{
    use SoftDeletes, LogsActivity;

    /**
     * The log name for activity logging.
     */
    protected static string $activityLogName = 'permission-management';

    /**
     * The attributes to log for activity.
     */
    protected static array $loggableAttributes = [
        'name',
        'slug',
        'description',
        'module',
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
        'module',
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
        static::creating(function ($permission) {
            if (empty($permission->slug)) {
                $permission->slug = Str::slug($permission->name);
            }
        });

        // Prevent deletion of system permissions
        static::deleting(function ($permission) {
            if ($permission->is_system) {
                return false;
            }

            // Check if permission is in use
            if ($permission->roles()->exists()) {
                return false;
            }

            return true;
        });
    }

    /**
     * Get the roles for the permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include system permissions.
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to exclude system permissions.
     */
    public function scopeNonSystem(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope a query to filter by module.
     */
    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Get permissions grouped by module.
     */
    public static function getGroupedByModule(): array
    {
        return static::query()
            ->get()
            ->groupBy('module')
            ->map(function ($permissions) {
                return $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'slug' => $permission->slug,
                        'description' => $permission->description,
                    ];
                });
            })
            ->toArray();
    }

    /**
     * Create multiple permissions at once.
     */
    public static function createBulk(array $permissions): void
    {
        foreach ($permissions as $permission) {
            static::firstOrCreate(
                ['slug' => $permission['slug'] ?? Str::slug($permission['name'])],
                $permission
            );
        }
    }
}