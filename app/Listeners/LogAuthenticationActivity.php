<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;

class LogAuthenticationActivity
{
    /**
     * Handle user login event.
     */
    public function handleLogin(Login $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'user_name' => $event->user->name,
            'user_email' => $event->user->email,
            'log_name' => 'authentication',
            'description' => 'user.login',
            'subject_type' => get_class($event->user),
            'subject_id' => $event->user->id,
            'causer_type' => get_class($event->user),
            'causer_id' => $event->user->id,
            'properties' => [
                'guard' => $event->guard,
                'remember' => $event->remember,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle user logout event.
     */
    public function handleLogout(Logout $event): void
    {
        if (!$event->user) {
            return;
        }

        ActivityLog::create([
            'user_id' => $event->user->id,
            'user_name' => $event->user->name,
            'user_email' => $event->user->email,
            'log_name' => 'authentication',
            'description' => 'user.logout',
            'subject_type' => get_class($event->user),
            'subject_id' => $event->user->id,
            'causer_type' => get_class($event->user),
            'causer_id' => $event->user->id,
            'properties' => [
                'guard' => $event->guard,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle failed login attempt.
     */
    public function handleFailed(Failed $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user?->id,
            'user_name' => $event->user?->name ?? 'Unknown',
            'user_email' => $event->credentials['email'] ?? null,
            'log_name' => 'authentication',
            'description' => 'user.login_failed',
            'subject_type' => $event->user ? get_class($event->user) : null,
            'subject_id' => $event->user?->id,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? null,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle password reset.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'user_name' => $event->user->name,
            'user_email' => $event->user->email,
            'log_name' => 'authentication',
            'description' => 'user.password_reset',
            'subject_type' => get_class($event->user),
            'subject_id' => $event->user->id,
            'causer_type' => get_class($event->user),
            'causer_id' => $event->user->id,
            'properties' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
