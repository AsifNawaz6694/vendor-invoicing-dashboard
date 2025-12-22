<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        // Check if user needs to set up 2FA (shouldn't happen after 2FA login, but just in case)
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
