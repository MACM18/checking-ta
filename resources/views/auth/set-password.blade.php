<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-gray-900">Set Account Password</h2>
        <p class="text-xs text-gray-500 mt-1">
            Welcome <strong class="text-indigo-600">{{ $user->name }}</strong>! Please create a secure personal password to finish your workspace setup.
        </p>
    </div>

    <!-- Session Status / Alerts -->
    @if(session('info'))
        <div class="mb-4 p-3 rounded-lg bg-indigo-50 border border-indigo-200 text-xs text-indigo-700">
            {{ session('info') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.setup.store') }}" class="space-y-4">
        @csrf

        <!-- Email (Read-Only) -->
        <div>
            <x-input-label for="email" :value="__('Your Workspace Email')" />
            <x-text-input id="email" class="block mt-1 w-full bg-gray-50 text-gray-500 cursor-not-allowed text-sm font-mono" type="email" :value="$user->email" disabled />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="block mt-1 w-full text-sm"
                            type="password"
                            name="password"
                            required autocomplete="new-password"
                            placeholder="Minimum 8 characters"
                            autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full text-sm"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password"
                            placeholder="Re-enter your password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center py-2.5 bg-indigo-600 hover:bg-indigo-700 text-sm font-bold">
                {{ __('Set Password & Enter Workspace') }} &rarr;
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
