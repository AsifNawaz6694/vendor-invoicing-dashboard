<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Permission;
use App\Models\User;

class AuthorizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPermissionGates();
        $this->registerMiddlewareAliases();
    }

    /**
     * Register permission gates dynamically.
     */
    protected function registerPermissionGates(): void
    {
        try {
            // Get all permissions from database
            $permissions = Permission::all();

            foreach ($permissions as $permission) {
                Gate::define($permission->slug, function (User $user) use ($permission) {
                    // Super admin can do everything
                    if ($user->isSuperAdmin()) {
                        return true;
                    }

                    // Check if user has the permission through their role
                    return $user->hasPermission($permission->slug);
                });
            }

            // Define super admin gate
            Gate::define('super-admin', function (User $user) {
                return $user->isSuperAdmin();
            });

            // Define role-specific gates
            Gate::define('vendor', function (User $user) {
                return $user->isVendor();
            });

            Gate::define('accountant', function (User $user) {
                return $user->isAccountant();
            });

            // Before callback to bypass all checks for super admin
            Gate::before(function (User $user, string $ability) {
                if ($user->isSuperAdmin()) {
                    return true;
                }
            });

        } catch (\Exception $e) {
            // Log error if database is not available (during migrations, etc.)
            report($e);
        }
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddlewareAliases(): void
    {
        $router = app('router');

        // Register middleware aliases
        $router->aliasMiddleware('role', \App\Http\Middleware\CheckRole::class);
        $router->aliasMiddleware('permission', \App\Http\Middleware\CheckPermission::class);
    }
}