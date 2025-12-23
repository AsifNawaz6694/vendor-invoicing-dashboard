<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @param  string|null  $guard
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $guard = null): Response
    {
        // Check if user is authenticated
        if (!auth($guard)->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
            return redirect()->route('login');
        }

        $user = auth($guard)->user();

        // Super admin can bypass all checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has the required permission
        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        // User doesn't have the required permission
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You do not have the required permission to access this resource.'
            ], 403);
        }

        abort(403, 'Unauthorized action. You do not have the required permission.');
    }
}