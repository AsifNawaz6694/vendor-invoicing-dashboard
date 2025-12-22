<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordSetupToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordSetupController extends Controller
{
    /**
     * Show the password setup form.
     */
    public function show(string $token): Response|RedirectResponse
    {
        $tokenRecord = PasswordSetupToken::findValidToken($token);

        if (!$tokenRecord) {
            return redirect()->route('login')
                ->with('error', 'This password setup link has expired or is invalid. Please contact your administrator.');
        }

        return Inertia::render('auth/password-setup', [
            'token' => $token,
            'email' => $tokenRecord->user->email,
        ]);
    }

    /**
     * Store the new password.
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $tokenRecord = PasswordSetupToken::findValidToken($token);

        if (!$tokenRecord) {
            return redirect()->route('login')
                ->with('error', 'This password setup link has expired or is invalid. Please contact your administrator.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $tokenRecord->user;

        // Update user password and verify email
        $user->update([
            'password' => Hash::make($request->password),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        // Delete the token
        $tokenRecord->delete();

        // Log the user in
        Auth::login($user);

        // Check if user needs 2FA setup
        if ($user->needsTwoFactorSetup()) {
            return redirect()->route('two-factor.setup')
                ->with('success', 'Password set successfully. Please set up two-factor authentication.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Password set successfully. Welcome!');
    }
}
