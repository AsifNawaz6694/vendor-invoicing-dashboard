<?php

namespace App\Http\Responses;

use App\Notifications\EmailTwoFactorCodeNotification;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        // Check if user has email 2FA enabled
        if ($user && $user->hasEmailTwoFactor()) {
            // Generate and send the 2FA code
            $user->generateEmailTwoFactorCode();
            $user->notify(new EmailTwoFactorCodeNotification());

            // Store user ID and remember preference in session, then logout
            session([
                'login.id' => $user->id,
                'login.remember' => $request->boolean('remember'),
            ]);
            auth()->logout();

            // Redirect to 2FA challenge
            // Use Inertia::location for proper redirect handling with Inertia
            if ($request->wantsJson()) {
                return \Inertia\Inertia::location(route('two-factor.login'));
            }
            return redirect()->route('two-factor.login');
        }

        // Check if user needs to set up 2FA
        if ($user && $user->needsTwoFactorSetup()) {
            if ($request->wantsJson()) {
                return \Inertia\Inertia::location(route('two-factor.setup'));
            }
            return redirect()->route('two-factor.setup');
        }

        // Normal redirect to home/dashboard
        if ($request->wantsJson()) {
            return \Inertia\Inertia::location(config('fortify.home'));
        }
        return redirect()->intended(config('fortify.home'));
    }
}
