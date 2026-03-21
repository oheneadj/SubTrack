<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->requires_password_change) {
            
            // Allow the user to access the password change page, logout, or Livewire update routes natively
            if ($request->routeIs('password.force-change') || 
                $request->routeIs('logout') || 
                $request->is('livewire/*')) {
                return $next($request);
            }

            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
