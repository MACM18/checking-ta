<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetOtpController extends Controller
{
    /**
     * Display the email entry form to request an OTP.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Generate a 15-minute 6-digit OTP and send via email.
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Only send OTP if user actually exists in the system
        if ($user) {
            $otp = sprintf('%06d', random_int(100000, 999999));

            // Clear any old OTPs for this email
            PasswordResetOtp::where('email', $email)->delete();

            // Create new OTP valid for 15 minutes
            PasswordResetOtp::create([
                'email' => $email,
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(15),
                'created_at' => Carbon::now(),
            ]);

            try {
                Mail::to($user->email)->send(new PasswordResetOtpMail($otp, 15));
            } catch (\Throwable $e) {
                // In local dev/testing without active SMTP server, message remains queued/logged
            }
        }

        return redirect()->route('password.otp.verify', ['email' => $email])
            ->with('status', 'If an account exists with this email, a 6-digit verification code has been sent (valid for 15 minutes).');
    }

    /**
     * Display the OTP verification and password reset screen.
     */
    public function showVerify(Request $request): View
    {
        $email = $request->query('email', old('email'));

        return view('auth.verify-otp', compact('email'));
    }

    /**
     * Verify the 6-digit OTP and update the user's password.
     */
    public function verifyAndReset(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $email = strtolower(trim($request->email));
        $otp = trim($request->otp);

        $record = PasswordResetOtp::where('email', $email)
            ->where('otp', $otp)
            ->first();

        if (! $record || $record->isExpired()) {
            return back()->withInput($request->only('email'))
                ->withErrors(['otp' => 'Invalid or expired 6-digit code. Codes expire after 15 minutes. Please request a new code.']);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Unable to find an account with this email address.']);
        }

        // Update password & clear OTP records
        $user->update([
            'password' => Hash::make($request->password),
            'must_set_password' => false,
        ]);

        PasswordResetOtp::where('email', $email)->delete();

        return redirect()->route('login')
            ->with('status', 'Your password has been reset successfully! You can now log in.');
    }

    /**
     * Resend a fresh 6-digit OTP.
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        return $this->sendOtp($request);
    }
}
