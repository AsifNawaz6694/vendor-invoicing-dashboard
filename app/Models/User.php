<?php

namespace App\Models;

use App\Traits\HasRoles;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, LogsActivity;

    /**
     * The log name for activity logging.
     */
    protected static string $activityLogName = 'user-management';

    /**
     * The attributes to log for activity.
     */
    protected static array $loggableAttributes = [
        'name',
        'email',
        'is_admin',
        'is_active',
        'two_factor_method',
        'role_id',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'email_two_factor_code',
        'email_two_factor_expires_at',
        'two_factor_method',
        'is_admin',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'email_two_factor_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'email_two_factor_expires_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user who created this user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the users created by this user.
     */
    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * Check if user has a linked Google account.
     */
    public function hasGoogleAccount(): bool
    {
        return !empty($this->google_id);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if user has email-based 2FA enabled.
     */
    public function hasEmailTwoFactor(): bool
    {
        return $this->two_factor_method === 'email';
    }

    /**
     * Check if user has TOTP-based 2FA enabled.
     */
    public function hasTotpTwoFactor(): bool
    {
        return $this->two_factor_method === 'totp' && $this->hasEnabledTwoFactorAuthentication();
    }

    /**
     * Check if user has any form of 2FA enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->hasEmailTwoFactor() || $this->hasTotpTwoFactor();
    }

    /**
     * Get the user's 2FA method.
     */
    public function getTwoFactorMethod(): ?string
    {
        return $this->two_factor_method;
    }

    /**
     * Check if user needs to set up 2FA.
     */
    public function needsTwoFactorSetup(): bool
    {
        return !$this->hasTwoFactorEnabled();
    }

    /**
     * Generate a 6-digit email 2FA code.
     */
    public function generateEmailTwoFactorCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiryMinutes = config('two-factor.email.expiry_minutes', 10);

        $this->forceFill([
            'email_two_factor_code' => $code,
            'email_two_factor_expires_at' => now()->addMinutes($expiryMinutes),
        ])->save();

        return $code;
    }

    /**
     * Verify the email 2FA code.
     */
    public function verifyEmailTwoFactorCode(string $code): bool
    {
        if (empty($this->email_two_factor_code)) {
            return false;
        }

        if ($this->email_two_factor_expires_at && $this->email_two_factor_expires_at->isPast()) {
            $this->clearEmailTwoFactorCode();
            return false;
        }

        if ($this->email_two_factor_code !== $code) {
            return false;
        }

        $this->clearEmailTwoFactorCode();
        return true;
    }

    /**
     * Clear the email 2FA code.
     */
    public function clearEmailTwoFactorCode(): void
    {
        $this->forceFill([
            'email_two_factor_code' => null,
            'email_two_factor_expires_at' => null,
        ])->save();
    }

    /**
     * Enable email-based 2FA for this user.
     */
    public function enableEmailTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_method' => 'email',
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Disable all 2FA for this user.
     */
    public function disableTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_method' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'email_two_factor_code' => null,
            'email_two_factor_expires_at' => null,
        ])->save();
    }
}
