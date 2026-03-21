<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
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
        // Now that this runs only on targeted routes AFTER auth middleware,
        // we can safely assume auth()->user() is resolved if authenticated.
        if (Auth::check() && Auth::user()->requires_password_change) {
            
            // Allow Livewire updates to pass through
            if ($request->is('livewire/*')) {
                return $next($request);
            }

            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
