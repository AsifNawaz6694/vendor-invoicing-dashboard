<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CurrentJobsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('users/{user}/resend-welcome', [UserController::class, 'resendWelcome'])->name('users.resend-welcome');
    Route::get('users-export', [UserController::class, 'export'])->name('users.export');

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
    Route::get('activity-logs-export', [ActivityLogController::class, 'export'])->name('activity-logs.export');

    // Current Jobs
    Route::get('current-jobs', [CurrentJobsController::class, 'index'])->name('current-jobs.index');
    Route::post('current-jobs/retry/{uuid}', [CurrentJobsController::class, 'retryJob'])->name('current-jobs.retry');
    Route::delete('current-jobs/failed/{uuid}', [CurrentJobsController::class, 'deleteFailedJob'])->name('current-jobs.delete-failed');
    Route::post('current-jobs/flush-failed', [CurrentJobsController::class, 'flushFailedJobs'])->name('current-jobs.flush-failed');
});
