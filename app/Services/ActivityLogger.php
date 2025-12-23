<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    protected ?string $logName = 'default';
    protected ?string $description = null;
    protected ?Model $subject = null;
    protected ?array $properties = null;
    protected ?User $causer = null;

    /**
     * Set the log name/category.
     */
    public function inLog(string $logName): self
    {
        $this->logName = $logName;
        return $this;
    }

    /**
     * Set the description.
     */
    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set the subject model.
     */
    public function on(Model $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set additional properties.
     */
    public function withProperties(array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Set the user who caused this activity.
     */
    public function by(?User $user): self
    {
        $this->causer = $user;
        return $this;
    }

    /**
     * Log the activity.
     */
    public function log(string $description = null): ActivityLog
    {
        $description = $description ?? $this->description ?? 'No description';
        $user = $this->causer ?? auth()->user();

        $activity = ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_email' => $user?->email,
            'log_name' => $this->logName,
            'description' => $description,
            'subject_type' => $this->subject ? get_class($this->subject) : null,
            'subject_id' => $this->subject?->id,
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->id,
            'properties' => $this->properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Reset state
        $this->reset();

        return $activity;
    }

    /**
     * Reset the logger state.
     */
    protected function reset(): void
    {
        $this->logName = 'default';
        $this->description = null;
        $this->subject = null;
        $this->properties = null;
        $this->causer = null;
    }

    /**
     * Create a new instance (for static usage).
     */
    public static function make(): self
    {
        return new self();
    }
}
