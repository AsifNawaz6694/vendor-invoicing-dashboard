<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailTwoFactorCodeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorAuthenticatedSessionController extends Controller
{
    /**
     * Show the 2FA challenge page.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('login.id'));

        if (!$user) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/two-factor-challenge', [
            'method' => $user->getTwoFactorMethod(),
        ]);
    }

    /**
     * Verify the 2FA code.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('login.id'));

        if (!$user) {
            return redirect()->route('login');
        }

        // Handle recovery code
        if ($recoveryCode = $request->input('recovery_code')) {
            if ($user->hasTotpTwoFactor()) {
                // Use Fortify's recovery code validation for TOTP
                $validRecoveryCode = collect($user->recoveryCodes())->first(function ($code) use ($recoveryCode) {
                    return hash_equals($code, $recoveryCode);
                });

                if ($validRecoveryCode) {
                    $user->replaceRecoveryCode($validRecoveryCode);
                    return $this->loginAndRedirect($request, $user);
                }
            }

            throw ValidationException::withMessages([
                'recovery_code' => ['The provided recovery code is invalid.'],
            ]);
        }

        // Handle regular code
        $code = $request->input('code');

        if (!$code) {
            throw ValidationException::withMessages([
                'code' => ['Please enter a verification code.'],
            ]);
        }

        if ($user->hasEmailTwoFactor()) {
            // Verify email 2FA code
            if ($user->verifyEmailTwoFactorCode($code)) {
                return $this->loginAndRedirect($request, $user);
            }

            throw ValidationException::withMessages([
                'code' => ['The provided verification code is invalid or has expired.'],
            ]);
        }

        if ($user->hasTotpTwoFactor()) {
            // Verify TOTP code
            $valid = $user->validateTwoFactorCode($code);

            if ($valid) {
                return $this->loginAndRedirect($request, $user);
            }

            throw ValidationException::withMessages([
                'code' => ['The provided verification code is invalid.'],
            ]);
        }

        throw ValidationException::withMessages([
            'code' => ['Two-factor authentication is not configured.'],
        ]);
    }

    /**
     * Resend the email 2FA code.
     */
    public function resend(Request $request): RedirectResponse|JsonResponse
    {
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('login.id'));

        if (!$user || !$user->hasEmailTwoFactor()) {
            return redirect()->route('login');
        }

        // Generate and send new code
        $user->generateEmailTwoFactorCode();
        $user->notify(new EmailTwoFactorCodeNotification());

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Verification code sent.']);
        }

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    /**
     * Login the user and redirect.
     */
    protected function loginAndRedirect(Request $request, User $user): RedirectResponse
    {
        Auth::login($user, $request->session()->get('login.remember', false));

        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        // Check if user needs to set up 2FA (shouldn't happen, but just in case)
        if ($user->needsTwoFactorSetup()) {
            return redirect()->route('two-factor.setup');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
