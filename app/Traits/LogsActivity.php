<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Boot the trait.
     */
    public static function bootLogsActivity(): void
    {
        // Log when model is created
        static::created(function (Model $model) {
            static::logModelActivity($model, 'created');
        });

        // Log when model is updated
        static::updated(function (Model $model) {
            static::logModelActivity($model, 'updated', $model->getChanges(), $model->getOriginal());
        });

        // Log when model is deleted
        static::deleted(function (Model $model) {
            static::logModelActivity($model, 'deleted');
        });

        // Log when model is restored (if using SoftDeletes)
        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                static::logModelActivity($model, 'restored');
            });
        }

        // Log when model is force deleted (if using SoftDeletes)
        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(function (Model $model) {
                static::logModelActivity($model, 'force_deleted');
            });
        }
    }

    /**
     * Log a model activity.
     */
    protected static function logModelActivity(Model $model, string $action, array $changes = [], array $original = []): void
    {
        $user = auth()->user();
        $modelName = class_basename($model);
        $logName = static::getActivityLogName();

        // Build properties
        $properties = [
            'model' => get_class($model),
            'id' => $model->getKey(),
        ];

        if ($action === 'updated' && !empty($changes)) {
            // Only log specific attributes that changed
            $loggableAttributes = static::getLoggableAttributes();
            $filteredChanges = [];
            $filteredOriginal = [];

            foreach ($changes as $key => $value) {
                if (empty($loggableAttributes) || in_array($key, $loggableAttributes)) {
                    // Don't log sensitive fields
                    if (!in_array($key, ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])) {
                        $filteredChanges[$key] = $value;
                        $filteredOriginal[$key] = $original[$key] ?? null;
                    }
                }
            }

            if (!empty($filteredChanges)) {
                $properties['changes'] = $filteredChanges;
                $properties['old'] = $filteredOriginal;
            }
        }

        if ($action === 'created') {
            $properties['attributes'] = static::getLoggableAttributeValues($model);
        }

        // Build description
        $description = static::buildActivityDescription($model, $action);

        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_email' => $user?->email,
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->id,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get the log name for this model.
     */
    protected static function getActivityLogName(): string
    {
        // Can be overridden in model
        if (property_exists(static::class, 'activityLogName')) {
            return static::$activityLogName;
        }

        // Default to model name in kebab case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', class_basename(static::class))) . '-management';
    }

    /**
     * Get the attributes that should be logged.
     * Return empty array to log all non-sensitive attributes.
     */
    protected static function getLoggableAttributes(): array
    {
        if (property_exists(static::class, 'loggableAttributes')) {
            return static::$loggableAttributes;
        }

        return [];
    }

    /**
     * Get loggable attribute values from a model.
     */
    protected static function getLoggableAttributeValues(Model $model): array
    {
        $loggable = static::getLoggableAttributes();
        $attributes = $model->getAttributes();

        // Remove sensitive fields
        $sensitiveFields = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
        foreach ($sensitiveFields as $field) {
            unset($attributes[$field]);
        }

        if (empty($loggable)) {
            return $attributes;
        }

        return array_intersect_key($attributes, array_flip($loggable));
    }

    /**
     * Build the activity description.
     */
    protected static function buildActivityDescription(Model $model, string $action): string
    {
        $modelName = class_basename($model);

        // Check for custom description method
        if (method_exists($model, 'getActivityDescription')) {
            return $model->getActivityDescription($action);
        }

        // Check for a name/title attribute to make description more meaningful
        $identifier = $model->name ?? $model->title ?? $model->email ?? "#{$model->getKey()}";

        return strtolower($modelName) . '.' . $action;
    }

    /**
     * Manually log a custom activity.
     */
    public function logActivity(string $description, string $logName = null, array $properties = []): ActivityLog
    {
        $user = auth()->user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_email' => $user?->email,
            'log_name' => $logName ?? static::getActivityLogName(),
            'description' => $description,
            'subject_type' => get_class($this),
            'subject_id' => $this->getKey(),
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->id,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
