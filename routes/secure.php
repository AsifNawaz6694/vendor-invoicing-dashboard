<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;

/*
|--------------------------------------------------------------------------
| Secure Routes with Role & Permission Based Access Control
|--------------------------------------------------------------------------
|
| These routes demonstrate best security practices with proper grouping,
| middleware stacking, and permission-based access control.
|
*/

// ====================================================================
// SUPER ADMIN ROUTES - Complete System Administration
// ====================================================================

Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard - redirects to main dashboard
        // Route::get('/dashboard', function () {
        //     return redirect()->route('dashboard');
        // })->name('dashboard');

        // Role Management - Full CRUD
        Route::resource('roles', RoleController::class);
        Route::get('roles-export', [RoleController::class, 'export'])->name('roles.export');
        Route::prefix('roles/{role}')->name('roles.')->group(function () {
            Route::post('permissions/assign', [RoleController::class, 'assignPermissions'])
                ->name('permissions.assign');
            Route::post('permissions/remove', [RoleController::class, 'removePermissions'])
                ->name('permissions.remove');
        });

        // Permission Management - Full CRUD
        Route::resource('permissions', PermissionController::class);
        Route::get('permissions-export', [PermissionController::class, 'export'])->name('permissions.export');
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('grouped', [PermissionController::class, 'getGroupedByModule'])
                ->name('grouped');
            Route::post('bulk', [PermissionController::class, 'bulkStore'])
                ->name('bulk');
            Route::post('sync-module', [PermissionController::class, 'syncModule'])
                ->name('sync-module');
        });

        // User Management - with role assignment
        // Route::resource('users', UserController::class);
        // Route::post('users/{user}/assign-role', [UserController::class, 'assignRole'])
        //     ->name('users.assign-role');
        // Route::post('users/{user}/remove-role', [UserController::class, 'removeRole'])
        //     ->name('users.remove-role');

        // System Settings
        // Route::prefix('settings')->name('settings.')->group(function () {
        //     Route::get('/', [SettingsController::class, 'index'])->name('index');
        //     Route::post('update', [SettingsController::class, 'update'])->name('update');
        // });

        // Audit Logs
        // Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        //     Route::get('/', [AuditLogController::class, 'index'])->name('index');
        //     Route::get('export', [AuditLogController::class, 'export'])->name('export');
        // });
    });

// ====================================================================
// ACCOUNTANT ROUTES - Financial Management
// ====================================================================

Route::middleware(['auth', 'verified', 'role:accountant,super_admin'])
    ->prefix('accounting')
    ->name('accounting.')
    ->group(function () {

        // Accounting Dashboard
        // Route::get('/dashboard', [AccountingController::class, 'dashboard'])
        //     ->name('dashboard');

        // Invoice Management - All Invoices
        // Route::middleware(['permission:invoices.view'])->group(function () {
        //     Route::get('invoices', [InvoiceController::class, 'index'])
        //         ->name('invoices.index');
        //     Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])
        //         ->name('invoices.show');
        // });

        // Invoice Approval/Rejection
        // Route::middleware(['permission:invoices.approve'])->group(function () {
        //     Route::get('invoices/pending', [InvoiceController::class, 'pending'])
        //         ->name('invoices.pending');
        //     Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])
        //         ->name('invoices.approve');
        // });

        // Route::middleware(['permission:invoices.reject'])->group(function () {
        //     Route::post('invoices/{invoice}/reject', [InvoiceController::class, 'reject'])
        //         ->name('invoices.reject');
        // });

        // Payment Processing
        // Route::middleware(['permission:payments.process'])->group(function () {
        //     Route::get('payments/pending', [PaymentController::class, 'pending'])
        //         ->name('payments.pending');
        //     Route::post('payments/process', [PaymentController::class, 'processBatch'])
        //         ->name('payments.process-batch');
        //     Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])
        //         ->name('invoices.mark-paid');
        // });

        // Financial Reports
        // Route::middleware(['permission:reports.generate'])->group(function () {
        //     Route::get('reports', [ReportController::class, 'index'])
        //         ->name('reports.index');
        //     Route::get('reports/generate', [ReportController::class, 'generate'])
        //         ->name('reports.generate');
        //     Route::get('reports/export', [ReportController::class, 'export'])
        //         ->name('reports.export');
        // });
    });

// ====================================================================
// VENDOR ROUTES - Vendor Self-Service
// ====================================================================

Route::middleware(['auth', 'verified', 'role:vendor'])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function () {

        // Vendor Dashboard
        // Route::get('/dashboard', [VendorController::class, 'dashboard'])
        //     ->name('dashboard');

        // Invoice Management - Own Invoices Only
        // Route::middleware(['permission:invoices.view'])->group(function () {
        //     Route::get('invoices', [VendorInvoiceController::class, 'index'])
        //         ->name('invoices.index');
        //     Route::get('invoices/{invoice}', [VendorInvoiceController::class, 'show'])
        //         ->name('invoices.show')
        //         ->middleware('can:view,invoice'); // Additional policy check
        // });

        // Route::middleware(['permission:invoices.create'])->group(function () {
        //     Route::get('invoices/create', [VendorInvoiceController::class, 'create'])
        //         ->name('invoices.create');
        //     Route::post('invoices', [VendorInvoiceController::class, 'store'])
        //         ->name('invoices.store');
        // });

        // Route::middleware(['permission:invoices.update'])->group(function () {
        //     Route::get('invoices/{invoice}/edit', [VendorInvoiceController::class, 'edit'])
        //         ->name('invoices.edit')
        //         ->middleware('can:update,invoice'); // Additional policy check
        //     Route::put('invoices/{invoice}', [VendorInvoiceController::class, 'update'])
        //         ->name('invoices.update')
        //         ->middleware('can:update,invoice');
        // });

        // Route::middleware(['permission:invoices.delete'])->group(function () {
        //     Route::delete('invoices/{invoice}', [VendorInvoiceController::class, 'destroy'])
        //         ->name('invoices.destroy')
        //         ->middleware('can:delete,invoice'); // Additional policy check
        // });

        // Payment Status - View Only
        // Route::middleware(['permission:payments.view'])->group(function () {
        //     Route::get('payments', [VendorPaymentController::class, 'index'])
        //         ->name('payments.index');
        //     Route::get('payments/{payment}', [VendorPaymentController::class, 'show'])
        //         ->name('payments.show');
        // });

        // Vendor Reports
        // Route::middleware(['permission:reports.view'])->group(function () {
        //     Route::get('reports', [VendorReportController::class, 'index'])
        //         ->name('reports.index');
        //     Route::get('reports/export', [VendorReportController::class, 'export'])
        //         ->name('reports.export')
        //         ->middleware('permission:reports.export');
        // });
    });

// ====================================================================
// SHARED AUTHENTICATED ROUTES - Common Features
// ====================================================================

// Profile and common routes are defined in settings.php

// ====================================================================
// API ROUTES - With Authentication & Permission Checks
// ====================================================================

Route::prefix('api/v1')
    ->middleware(['auth:sanctum'])
    ->name('api.')
    ->group(function () {

        // Roles API - Super Admin Only
        // Route::middleware(['role:super_admin'])->group(function () {
        //     Route::apiResource('roles', Api\RoleController::class);
        //     Route::post('roles/{role}/permissions', [Api\RoleController::class, 'syncPermissions']);
        // });

        // Permissions API - Super Admin Only
        // Route::middleware(['role:super_admin'])->group(function () {
        //     Route::apiResource('permissions', Api\PermissionController::class);
        // });

        // Invoices API - Permission Based
        // Route::middleware(['permission:invoices.view'])->group(function () {
        //     Route::get('invoices', [Api\InvoiceController::class, 'index']);
        //     Route::get('invoices/{invoice}', [Api\InvoiceController::class, 'show']);
        // });

        // Route::middleware(['permission:invoices.create'])->group(function () {
        //     Route::post('invoices', [Api\InvoiceController::class, 'store']);
        // });

        // Route::middleware(['permission:invoices.update'])->group(function () {
        //     Route::put('invoices/{invoice}', [Api\InvoiceController::class, 'update']);
        // });

        // Route::middleware(['permission:invoices.delete'])->group(function () {
        //     Route::delete('invoices/{invoice}', [Api\InvoiceController::class, 'destroy']);
        // });

        // Current User Info
        Route::get('user', function () {
            return auth()->user()->load(['role.permissions']);
        });

        Route::get('user/permissions', function () {
            return auth()->user()->getCachedPermissions();
        });
    });

// ====================================================================
// RATE LIMITED PUBLIC ROUTES
// ====================================================================

// Route::middleware(['throttle:10,1'])->group(function () {
//     Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');
//     Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
// });

// ====================================================================
// FALLBACK ROUTE
// ====================================================================

Route::fallback(function () {
    return response()->json(['message' => 'Route not found.'], 404);
});