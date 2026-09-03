<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PermissionController extends Controller
{
    protected function authorizeAdmin(): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Administrator access required.');
        }
    }

    /**
     * Display the Permissions Matrix overview for all workspace users.
     */
    public function index(): View
    {
        $this->authorizeAdmin();

        $users = User::orderBy('name')->get();
        $availablePermissions = User::AVAILABLE_PERMISSIONS;

        return view('permissions.index', compact('users', 'availablePermissions'));
    }

    /**
     * Show the permission editor form for a specific user.
     */
    public function edit(User $user): View
    {
        $this->authorizeAdmin();

        $availablePermissions = User::AVAILABLE_PERMISSIONS;
        $userPermissions = $user->permissions ?? [];

        return view('permissions.edit', compact('user', 'availablePermissions', 'userPermissions'));
    }

    /**
     * Update the granular permissions assigned to a user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($user->isAdmin()) {
            return redirect()->route('permissions.index')
                ->with('info', "User '{$user->name}' is an Administrator and inherently has full system access.");
        }

        $validKeys = array_keys(User::AVAILABLE_PERMISSIONS);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', $validKeys)],
        ]);

        $user->update([
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return redirect()->route('permissions.index')
            ->with('success', "Granular permissions updated successfully for '{$user->name}'.");
    }
}
