# Roles & Permissions System Documentation

## Overview
This system implements a robust role-based access control (RBAC) for the Vendor Invoice & Payment Release platform with three initial roles:
- **Super Admin**: Complete system access, bypasses all permission checks
- **Vendor**: Can manage their own invoices
- **Accountant**: Can manage all invoices and process payments

## Installation

### 1. Register the Service Provider
Add the AuthorizationServiceProvider to `bootstrap/providers.php`:

```php
return [
    // ... other providers
    App\Providers\AuthorizationServiceProvider::class,
];
```

### 2. Run Migrations
```bash
php artisan migrate
```

### 3. Seed Initial Data
```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
```

## Usage Examples

### Secure Routes Configuration

Create a routes file `routes/admin.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;

// Admin routes group - requires authentication and super_admin role
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {

    // Role Management
    Route::resource('roles', RoleController::class);
    Route::post('roles/{role}/permissions/assign', [RoleController::class, 'assignPermissions'])->name('roles.permissions.assign');
    Route::post('roles/{role}/permissions/remove', [RoleController::class, 'removePermissions'])->name('roles.permissions.remove');

    // Permission Management
    Route::resource('permissions', PermissionController::class);
    Route::get('permissions/grouped', [PermissionController::class, 'getGroupedByModule'])->name('permissions.grouped');
    Route::post('permissions/bulk', [PermissionController::class, 'bulkStore'])->name('permissions.bulk');
    Route::post('permissions/sync-module', [PermissionController::class, 'syncModule'])->name('permissions.sync-module');
});

// Invoice routes - role-based access
Route::middleware(['auth'])->group(function () {

    // Invoices - using permission middleware
    Route::middleware(['permission:invoices.view'])->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    });

    Route::middleware(['permission:invoices.create'])->group(function () {
        Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    });

    Route::middleware(['permission:invoices.update'])->group(function () {
        Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    });

    Route::middleware(['permission:invoices.delete'])->group(function () {
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });

    // Accountant-only actions
    Route::middleware(['role:accountant,super_admin'])->group(function () {
        Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');
        Route::post('invoices/{invoice}/reject', [InvoiceController::class, 'reject'])->name('invoices.reject');
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
    });
});
```

### Controller Examples

#### Using Policies in Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct()
    {
        // Authorize resource actions using policy
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    public function index()
    {
        $invoices = Invoice::query()
            ->when(auth()->user()->isVendor(), function ($query) {
                // Vendors only see their own invoices
                $query->where('vendor_id', auth()->id());
            })
            ->paginate();

        return view('invoices.index', compact('invoices'));
    }

    public function approve(Invoice $invoice)
    {
        // Policy check
        $this->authorize('approve', $invoice);

        $invoice->approve(auth()->user());

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice approved successfully');
    }
}
```

### Blade Template Examples

```blade
@can('invoices.create')
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
        Create New Invoice
    </a>
@endcan

@can('approve', $invoice)
    <form method="POST" action="{{ route('invoices.approve', $invoice) }}">
        @csrf
        <button type="submit" class="btn btn-success">Approve Invoice</button>
    </form>
@endcan

@role('super_admin')
    <div class="admin-panel">
        <!-- Super admin only content -->
    </div>
@endrole

@hasrole('vendor')
    <p>Welcome, Vendor!</p>
@endhasrole

@hasanyrole(['accountant', 'super_admin'])
    <div class="financial-controls">
        <!-- Financial controls for accountants and super admins -->
    </div>
@endhasanyrole
```

### API Usage Examples

```php
// Check if user has a role
if ($user->hasRole('vendor')) {
    // Vendor-specific logic
}

// Check if user has a permission
if ($user->hasPermission('invoices.approve')) {
    // User can approve invoices
}

// Check multiple permissions
if ($user->hasAllPermissions(['invoices.view', 'invoices.create'])) {
    // User has both permissions
}

if ($user->hasAnyPermission(['invoices.approve', 'invoices.reject'])) {
    // User has at least one of these permissions
}

// Assign role to user
$user->assignRole('vendor');

// Get user permissions
$permissions = $user->getCachedPermissions();

// Clear permission cache
$user->clearPermissionCache();
```

### Managing Roles & Permissions

```php
// Create a new role
$role = Role::create([
    'name' => 'Finance Manager',
    'slug' => 'finance_manager',
    'description' => 'Manages financial operations'
]);

// Assign permissions to role
$role->assignPermissions(['invoices.view', 'invoices.approve', 'payments.process']);

// Remove permissions from role
$role->removePermissions(['payments.process']);

// Sync permissions (replaces existing)
$role->syncPermissions(['invoices.view', 'invoices.approve']);

// Create a new permission
$permission = Permission::create([
    'name' => 'View Financial Reports',
    'slug' => 'reports.financial.view',
    'description' => 'View financial reports',
    'module' => 'Reports'
]);
```

### Using Gates

```php
// In controllers
if (Gate::allows('invoices.approve')) {
    // User can approve invoices
}

if (Gate::denies('invoices.delete', $invoice)) {
    abort(403);
}

// Using Gate facade
Gate::authorize('invoices.create');

// In service classes
if (Gate::forUser($user)->allows('invoices.view')) {
    // Specific user check
}
```

## Security Best Practices

1. **Always use middleware** for route protection:
   ```php
   Route::middleware(['auth', 'role:vendor'])->group(function () {
       // Protected routes
   });
   ```

2. **Use policies** for model-specific authorization:
   ```php
   $this->authorize('update', $invoice);
   ```

3. **Cache permissions** to reduce database queries:
   ```php
   $permissions = $user->getCachedPermissions();
   ```

4. **Never trust client-side checks** - always validate on the server:
   ```php
   // Always check in controller even if UI hides buttons
   if (!$user->hasPermission('invoices.delete')) {
       abort(403);
   }
   ```

5. **Use database transactions** for role/permission changes:
   ```php
   DB::transaction(function () use ($role, $permissions) {
       $role->syncPermissions($permissions);
       // Clear cache after changes
       $role->users->each->clearPermissionCache();
   });
   ```

## Troubleshooting

### Permission Cache Issues
If permissions don't update immediately:
```php
// Clear specific user cache
$user->clearPermissionCache();

// Clear all caches
Cache::flush();
```

### Migration Issues
If you encounter foreign key constraints:
1. Ensure roles table is created before role_permission table
2. Run migrations in correct order:
   ```bash
   php artisan migrate:fresh --seed
   ```

### Testing Permissions
```php
// In tests
$this->actingAs($user)
    ->get('/admin/roles')
    ->assertStatus(403); // Should fail for non-admin

$admin = User::factory()->create();
$admin->assignRole('super_admin');

$this->actingAs($admin)
    ->get('/admin/roles')
    ->assertStatus(200); // Should succeed for admin
```

## Performance Optimization

1. **Eager load relationships** to avoid N+1 queries:
   ```php
   $roles = Role::with('permissions', 'users')->get();
   ```

2. **Use caching** for frequently accessed permissions:
   - Permissions are cached per user
   - Cache TTL is configurable in config/cache.php

3. **Index foreign keys** for better query performance:
   - All foreign keys have indexes in migrations

## Extending the System

### Adding Custom Guards
```php
// In AuthorizationServiceProvider
Gate::define('custom-ability', function ($user, $model) {
    return $user->id === $model->user_id;
});
```

### Creating New Modules
1. Add permissions to PermissionSeeder
2. Run: `php artisan db:seed --class=PermissionSeeder`
3. Assign to roles as needed
4. Clear permission cache

### Custom Middleware
```php
// Create app/Http/Middleware/CustomPermissionCheck.php
class CustomPermissionCheck
{
    public function handle($request, Closure $next, $permission)
    {
        if (!auth()->user()->hasPermission($permission)) {
            abort(403);
        }
        return $next($request);
    }
}
```

## Support

For issues or questions, please refer to the system administrator or create a support ticket.