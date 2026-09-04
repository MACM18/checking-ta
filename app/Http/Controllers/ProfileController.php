<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ChecklistTemplate;
use App\Models\Document;
use App\Models\ShipmentOrder;
use App\Models\User;
use App\Services\DeviceAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $adminStats = null;

        if ($user->isAdmin()) {
            $adminStats = [
                'total_users' => User::count(),
                'total_documents' => Document::count(),
                'active_shipments' => ShipmentOrder::where('status', 'active')->count(),
                'total_templates' => ChecklistTemplate::count(),
            ];
        }

        $devices = $user->devices()
            ->where('is_revoked', false)
            ->where('expires_at', '>', now())
            ->orderByDesc('last_active_at')
            ->get();

        $cookieValue = $request->cookie(DeviceAuthService::COOKIE_NAME);
        $currentDeviceUuid = null;
        if ($cookieValue && is_string($cookieValue) && str_contains($cookieValue, '|')) {
            [$currentDeviceUuid] = explode('|', $cookieValue, 2);
        }

        return view('profile.edit', [
            'user' => $user,
            'adminStats' => $adminStats,
            'devices' => $devices,
            'currentDeviceUuid' => $currentDeviceUuid,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return back()->with('error', 'Administrator accounts cannot be deleted under any circumstances.');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
