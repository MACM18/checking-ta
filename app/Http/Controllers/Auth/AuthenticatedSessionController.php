<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\DeviceAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, DeviceAuthService $deviceAuthService): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $deviceCookie = $deviceAuthService->issueDeviceSession($request->user(), $request);

        return redirect()->intended(route('dashboard', absolute: false))->withCookie($deviceCookie);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, DeviceAuthService $deviceAuthService): RedirectResponse
    {
        $forgetCookie = $deviceAuthService->revokeCurrentDevice($request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->withCookie($forgetCookie);
    }
}
