<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsSet
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_set_password) {
            $exemptRoutes = [
                'password.setup',
                'password.setup.store',
                'logout',
            ];

            if (! in_array($request->route()?->getName(), $exemptRoutes)) {
                return redirect()->route('password.setup');
            }
        }

        return $next($request);
    }
}
