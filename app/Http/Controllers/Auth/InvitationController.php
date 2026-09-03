<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\UserInvitationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    /**
     * Accept 24-hour invitation magic link and sign in.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $user = User::where('invitation_token', $token)->first();

        if (! $user || ! $user->invitation_expires_at || $user->invitation_expires_at->isPast()) {
            return redirect()->route('login')
                ->with('error', 'This invitation link is invalid or has expired (valid for 24 hours). Please request a fresh invitation from your administrator.');
        }

        // Invalidate token so it cannot be reused
        $user->update([
            'invitation_token' => null,
            'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
        ]);

        // Log out any prior user before logging in the invited user
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->must_set_password) {
            return redirect()->route('password.setup')
                ->with('info', "Welcome {$user->name}! Please set your personal account password to complete your account setup.");
        }

        return redirect()->route('documents.index');
    }

    /**
     * Resend 24-hour magic invitation email (Admin only).
     */
    public function resend(Request $request, User $user): RedirectResponse
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }

        $token = Str::random(64);
        $user->update([
            'invitation_token' => $token,
            'invitation_expires_at' => Carbon::now()->addHours(24),
            'must_set_password' => true,
        ]);

        $magicLink = route('invitation.accept', ['token' => $token]);

        try {
            Mail::to($user->email)->send(new UserInvitationMail($user, $magicLink));
            $msg = "24-hour magic invitation link resent to {$user->email}.";
        } catch (\Throwable $e) {
            $msg = "Invitation link generated (Email delivery queued/logged): {$magicLink}";
        }

        return redirect()->route('users.index')->with('success', $msg);
    }
}
