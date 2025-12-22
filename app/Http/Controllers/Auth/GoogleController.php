<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            \Log::error('Google OAuth failed: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('login')->with('error', 'Google authentication failed: ' . $e->getMessage());
        }

        // Check if user exists with this Google ID
        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            // Check if user exists with same email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Link Google account to existing user
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            } else {
                // Don't create new users via OAuth - they must be created by admin
                return redirect()->route('login')->with('error', 'No account found with this email. Please contact your administrator.');
            }
        }

        // Check if user is active
        if (!$user->isActive()) {
            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        // Check if user needs 2FA verification
        if ($user->hasTwoFactorEnabled()) {
            // Store user ID in session for 2FA challenge
            session(['login.id' => $user->id]);

            // If email 2FA, send code
            if ($user->hasEmailTwoFactor()) {
                $user->generateEmailTwoFactorCode();
                $user->notify(new \App\Notifications\EmailTwoFactorCodeNotification());
            }

            return redirect()->route('two-factor.login');
        }

        // If no 2FA, check if user needs to set it up
        Auth::login($user, true);

        if ($user->needsTwoFactorSetup()) {
            return redirect()->route('two-factor.setup');
        }

        return redirect()->intended(config('fortify.home'));
    }

    /**
     * Link Google account to current user.
     */
    public function link(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle linking callback.
     */
    public function linkCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('settings.profile')->with('error', 'Failed to link Google account.');
        }

        $user = Auth::user();

        // Check if Google ID is already linked to another user
        $existingUser = User::where('google_id', $googleUser->getId())->first();
        if ($existingUser && $existingUser->id !== $user->id) {
            return redirect()->route('settings.profile')->with('error', 'This Google account is already linked to another user.');
        }

        $user->update([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
        ]);

        return redirect()->route('settings.profile')->with('status', 'Google account linked successfully.');
    }

    /**
     * Unlink Google account from current user.
     */
    public function unlink(): RedirectResponse
    {
        $user = Auth::user();

        // Don't allow unlinking if user doesn't have a password set
        if (empty($user->password)) {
            return redirect()->route('settings.profile')->with('error', 'Cannot unlink Google account. Please set a password first.');
        }

        $user->update([
            'google_id' => null,
            'avatar' => null,
        ]);

        return redirect()->route('settings.profile')->with('status', 'Google account unlinked successfully.');
    }
}
