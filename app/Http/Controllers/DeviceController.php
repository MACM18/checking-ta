<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use App\Services\DeviceAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceAuthService $deviceAuthService
    ) {}

    /**
     * Revoke a specific trusted device.
     */
    public function destroy(Request $request, UserDevice $device): RedirectResponse
    {
        if ($device->user_id !== Auth::id()) {
            abort(403);
        }

        $isCurrent = $this->deviceAuthService->isCurrentDevice($request, $device->device_uuid);
        $device->revoke();

        $response = back()->with('success', "Device '{$device->device_name}' has been revoked.");

        if ($isCurrent) {
            $forgetCookie = $this->deviceAuthService->revokeCurrentDevice($request);
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->withCookie($forgetCookie)->with('info', 'Your current device session was revoked and you have been signed out.');
        }

        return $response;
    }

    /**
     * Revoke all other active devices for the current user.
     */
    public function revokeOthers(Request $request): RedirectResponse
    {
        $count = $this->deviceAuthService->revokeOtherDevices($request->user(), $request);

        return back()->with('success', "Successfully revoked {$count} other active device session(s).");
    }
}
