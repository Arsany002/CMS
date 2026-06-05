<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next,string $role): Response
    {
        $user = $request->user();

        // 1. Check if user exists
        // 2. Check if their Enum value matches the required route role
        if (! $user || $user->role->value !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: You do not have the required permissions.'
            ], 403);
        }

        return $next($request);
    }
}
