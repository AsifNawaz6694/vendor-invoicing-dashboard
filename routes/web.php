<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\PasswordSetupController;
use App\Http\Controllers\Auth\TwoFactorAuthenticatedSessionController;
use App\Http\Controllers\Auth\TwoFactorSetupController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Google OAuth routes (public)
Route::get('auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

// Password setup for admin-created users (public)
Route::get('password/setup/{token}', [PasswordSetupController::class, 'show'])->name('password.setup');
Route::post('password/setup/{token}', [PasswordSetupController::class, 'store'])->name('password.setup.store');

// Custom 2FA challenge routes (supports both TOTP and Email 2FA)
Route::get('two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('two-factor.login');
Route::post('two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:two-factor']);
Route::post('two-factor-challenge/resend', [TwoFactorAuthenticatedSessionController::class, 'resend'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('two-factor.resend');

Route::middleware(['auth', 'verified', '2fa.setup'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// 2FA setup route (requires auth but not 2fa.setup middleware)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('auth/two-factor-setup', [TwoFactorSetupController::class, 'show'])->name('two-factor.setup');
    Route::post('auth/two-factor-setup', [TwoFactorSetupController::class, 'store'])->name('two-factor.setup.store');
});

require __DIR__.'/settings.php';
