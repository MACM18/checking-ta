<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Authorize admin-only access.
     */
    protected function authorizeAdmin(): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Unauthorized. Administrator access required.');
        }
    }

    /**
     * Display a listing of users with search and role filters.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $query = User::query()->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', User::ROLE_ADMIN)->count(),
            'editors' => User::where('role', User::ROLE_EDITOR)->count(),
            'viewers' => User::where('role', User::ROLE_VIEWER)->count(),
        ];

        return view('users.index', compact('users', 'stats'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $this->authorizeAdmin();

        $roles = [
            User::ROLE_ADMIN => 'Administrator (Full Access & User Management)',
            User::ROLE_EDITOR => 'Editor (Create & Edit Documents & Orders)',
            User::ROLE_VIEWER => 'Viewer (Read-Only Access)',
        ];

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:admin,editor,viewer'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')
            ->with('success', "User '{$user->name}' created successfully as {$user->role}.");
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $this->authorizeAdmin();

        $roles = [
            User::ROLE_ADMIN => 'Administrator (Full Access & User Management)',
            User::ROLE_EDITOR => 'Editor (Create & Edit Documents & Orders)',
            User::ROLE_VIEWER => 'Viewer (Read-Only Access)',
        ];

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'string', 'in:admin,editor,viewer'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // Safety: Prevent removing the last admin
        if ($user->isAdmin() && $validated['role'] !== User::ROLE_ADMIN) {
            $adminCount = User::where('role', User::ROLE_ADMIN)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => 'Cannot demote the only remaining administrator.'])->withInput();
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Remove the specified user from storage.
     * Enforces strict "prevent admin deletion" policy.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        // 1. Strict Rule: Prevent Admin Deletion Force
        if ($user->isAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'Deletion Blocked: Administrator accounts are strictly protected and cannot be deleted.');
        }

        // 2. Prevent self-deletion
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'Deletion Blocked: You cannot delete your own active account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User '{$name}' deleted successfully.");
    }
}
