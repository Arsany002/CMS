<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClinicScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super Admins operate globally, they bypass the clinic scope restriction
        if ($user->role === UserRole::SUPER_ADMIN) {
            return $next($request);
        }

        // For Doctors and Assistants, forcefully inject their clinic_id into the request data
        // This ensures they cannot pass a fake clinic_id in their JSON payload
        $request->merge([
            'clinic_id' => $user->clinic_id
        ]);

        return $next($request);    
    }
}
