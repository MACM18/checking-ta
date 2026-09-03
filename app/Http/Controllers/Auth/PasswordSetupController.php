<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordSetupController extends Controller
{
    /**
     * Show the first-time password setup form.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->must_set_password) {
            return redirect()->route('documents.index');
        }

        return view('auth.set-password', compact('user'));
    }

    /**
     * Store the chosen password and activate the account.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_set_password' => false,
        ]);

        return redirect()->route('documents.index')
            ->with('success', 'Your password has been successfully established. Welcome to Checking TA!');
    }
}
