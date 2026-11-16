<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.security_questions_enabled', false)) {
            return $next($request);
        }
        $user = $request->user();

        // El SuperAdmin tiene privilegios máximos y nunca debe ser forzado a restablecer contraseña
        if ($user && $user->hasRole('SuperAdmin')) {
            return $next($request);
        }

        if ($user && $user->must_change_password) {
            // Allow access to password setup and logout routes only
            $allowedRoutes = ['password.setup', 'logout'];
            
            if (!in_array($request->route()?->getName(), $allowedRoutes) 
                && $request->path() !== 'password/setup') {
                return redirect()->route('password.setup');
            }
        }

        return $next($request);
    }
}