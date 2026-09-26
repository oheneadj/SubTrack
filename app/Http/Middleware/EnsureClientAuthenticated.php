<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards client portal routes — checks for a client session set by magic link auth.
 * Redirects to the client login page if no valid session is present.
 */
class EnsureClientAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('client_id')) {
            return redirect()->route('client.login')
                ->withErrors(['auth' => 'Please log in to access your invoices.']);
        }

        return $next($request);
    }
}
