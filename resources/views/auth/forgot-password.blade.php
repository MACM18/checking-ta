<x-guest-layout>
    <div class="mb-5 text-center">
        <h2 class="text-xl font-bold text-gray-900">Forgot Password?</h2>
        <p class="text-xs text-gray-500 mt-1">
            Enter your workspace email address to receive a <strong>6-digit verification code</strong> (valid for 15 minutes).
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Account Email Address')" />
            <x-text-input id="email" class="block mt-1 w-full text-sm" type="email" name="email" :value="old('email')" required autofocus placeholder="your.name@company.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-2 flex items-center justify-between">
            <a class="underline text-xs text-gray-600 hover:text-gray-900 font-semibold" href="{{ route('login') }}">
                &larr; Back to login
            </a>

            <x-primary-button class="py-2.5 bg-indigo-600 hover:bg-indigo-700 text-xs font-bold shadow-xs">
                {{ __('Send 6-Digit Code') }} &rarr;
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
