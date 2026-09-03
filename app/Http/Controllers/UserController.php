<?php

namespace App\Http\Controllers;

use App\Mail\UserInvitationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

        $sendInvite = $request->boolean('send_invitation', true);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', 'in:admin,editor,viewer'],
            'send_invitation' => ['nullable', 'boolean'],
        ];

        if (! $sendInvite || $request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

        $token = $sendInvite ? Str::random(64) : null;
        $expiresAt = $sendInvite ? Carbon::now()->addHours(24) : null;
        $rawPassword = $validated['password'] ?? Str::random(24);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($rawPassword),
            'role' => $validated['role'],
            'invitation_token' => $token,
            'invitation_expires_at' => $expiresAt,
            'must_set_password' => $sendInvite,
        ]);

        $message = "User '{$user->name}' created successfully as {$user->role}.";

        if ($sendInvite) {
            $magicLink = route('invitation.accept', ['token' => $token]);
            try {
                Mail::to($user->email)->send(new UserInvitationMail($user, $magicLink));
                $message .= ' A 24-hour magic login link has been sent to their email.';

                return redirect()->route('users.index')->with('success', $message);
            } catch (\Throwable $e) {
                Log::error("SMTP Error sending invitation to {$user->email}: ".$e->getMessage(), ['exception' => $e]);
                $message .= " However, email delivery failed: {$e->getMessage()}. Direct magic link: {$magicLink}";

                return redirect()->route('users.index')->with('error', $message);
            }
        }

        return redirect()->route('users.index')->with('success', $message);
    }

    /**
     * Send or resend 24-hour magic invitation link to a new or existing user.
     */
    public function resendInvitation(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $forcePasswordReset = $request->has('reset_password')
            ? $request->boolean('reset_password')
            : $user->must_set_password;

        $token = Str::random(64);
        $user->update([
            'invitation_token' => $token,
            'invitation_expires_at' => Carbon::now()->addHours(24),
            'must_set_password' => $forcePasswordReset,
        ]);

        $magicLink = route('invitation.accept', ['token' => $token]);

        try {
            Mail::to($user->email)->send(new UserInvitationMail($user, $magicLink));
            $msg = "24-hour invitation / magic sign-in link successfully sent to {$user->email}.";

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error("SMTP Error sending invitation link to {$user->email}: ".$e->getMessage(), ['exception' => $e]);
            $msg = "Invitation token generated, but email sending failed ({$e->getMessage()}). Direct link: {$magicLink}";

            return back()->with('error', $msg);
        }
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
