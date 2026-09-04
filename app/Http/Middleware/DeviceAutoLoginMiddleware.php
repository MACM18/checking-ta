<?php

namespace App\Http\Middleware;

use App\Services\DeviceAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DeviceAutoLoginMiddleware
{
    public function __construct(
        protected DeviceAuthService $deviceAuthService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guest()) {
            $user = $this->deviceAuthService->attemptAutoLogin($request);
            if ($user && $request->hasSession()) {
                $request->session()->regenerate();
            }
        }

        return $next($request);
    }
}
