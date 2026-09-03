<x-guest-layout>
    <div class="mb-5 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 mb-3">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900">Enter Verification Code</h2>
        <p class="text-xs text-gray-500 mt-1">
            We sent a 6-digit code to <strong class="text-indigo-600 font-mono">{{ $email }}</strong>. The code expires in <strong>15 minutes</strong>.
        </p>
    </div>

    <!-- Status Notice -->
    @if(session('status'))
        <div class="mb-4 p-3 bg-indigo-50 border border-indigo-200 rounded-lg text-xs text-indigo-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.otp.reset') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        <!-- 6-Digit Code Input -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <x-input-label for="otp" :value="__('6-Digit Verification Code')" />
                <span class="text-[11px] font-bold text-red-500">15 min expiration</span>
            </div>
            <x-text-input id="otp"
                          class="block w-full text-center font-mono text-2xl font-black tracking-widest text-indigo-700 py-2.5 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                          type="text"
                          name="otp"
                          required
                          autofocus
                          maxlength="6"
                          placeholder="------"
                          autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <!-- New Password -->
        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="block mt-1 w-full text-sm"
                            type="password"
                            name="password"
                            required autocomplete="new-password"
                            placeholder="Minimum 8 characters" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full text-sm"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password"
                            placeholder="Re-enter new password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-2 space-y-3">
            <x-primary-button class="w-full justify-center py-2.5 bg-indigo-600 hover:bg-indigo-700 text-sm font-bold shadow-xs">
                {{ __('Verify Code & Set New Password') }}
            </x-primary-button>
        </div>
    </form>

    <!-- Resend Code Option -->
    <div class="mt-6 pt-4 border-t border-gray-100 text-center flex items-center justify-between text-xs">
        <a href="{{ route('password.request') }}" class="text-gray-500 hover:text-gray-700 font-semibold">
            Change email
        </a>
        <form method="POST" action="{{ route('password.otp.resend') }}" class="inline">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="text-indigo-600 hover:text-indigo-800 font-bold">
                Resend Code
            </button>
        </form>
    </div>
</x-guest-layout>
