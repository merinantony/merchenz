<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // If no specific roles are required, allow authenticated user
        if (empty($roles)) {
            return $next($request);
        }

        // Super Admin has access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user's role matches any allowed role
        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to this resource.');
    }
}

