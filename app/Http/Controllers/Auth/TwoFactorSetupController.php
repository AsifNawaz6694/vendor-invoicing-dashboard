<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\EmailTwoFactorCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorSetupController extends Controller
{
    /**
     * Show the 2FA setup page.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // If user already has 2FA, redirect to dashboard
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/two-factor-setup', [
            'defaultMethod' => config('two-factor.default_method', 'email'),
        ]);
    }

    /**
     * Store the 2FA setup (enable email 2FA).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'method' => ['required', 'in:email,totp'],
        ]);

        $user = $request->user();

        if ($request->method === 'email') {
            // Enable email 2FA
            $user->enableEmailTwoFactor();

            return redirect()->route('dashboard')
                ->with('success', 'Two-factor authentication enabled successfully.');
        }

        // For TOTP, redirect to the standard 2FA settings page to complete setup
        return redirect()->route('settings.two-factor')
            ->with('info', 'Please complete the authenticator app setup.');
    }
}
