<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super admin can bypass all checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has any of the required roles
        if (empty($roles) || $user->hasAnyRole($roles)) {
            return $next($request);
        }

        // User doesn't have the required role
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You do not have the required role to access this resource.'
            ], 403);
        }

        abort(403, 'Unauthorized action. You do not have the required role.');
    }
}