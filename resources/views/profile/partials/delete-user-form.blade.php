<section class="space-y-4">
    @if($user->isAdmin())
        <div class="flex items-start space-x-4 p-5 bg-purple-50/60 border border-purple-200 rounded-2xl">
            <div class="p-2.5 bg-purple-100 text-purple-700 rounded-xl flex-shrink-0 mt-0.5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h3 class="text-sm font-bold text-purple-950">
                        {{ __('Administrator Account Protected') }}
                    </h3>
                    <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-purple-200 text-purple-900 rounded-md">
                        Protected
                    </span>
                </div>
                <p class="text-xs text-purple-700 mt-1.5 leading-relaxed">
                    {{ __('This account holds active Administrator privileges for Checking TA. To safeguard workspace continuity, prevent accidental lockout, and maintain system integrity, administrator deletion is permanently locked.') }}
                </p>
            </div>
        </div>
    @else
        <header>
            <h2 class="text-base font-bold text-gray-900">
                {{ __('Delete Account') }}
            </h2>
            <p class="mt-1 text-xs text-gray-500">
                {{ __('Once your account is deleted, all of its resources and data will be permanently removed.') }}
            </p>
        </header>

        <x-danger-button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="text-xs font-bold px-4 py-2"
        >{{ __('Delete Account') }}</x-danger-button>

        <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
            <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-lg font-bold text-gray-900">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="mt-2 text-xs text-gray-500">
                    {{ __('Once your account is deleted, all of its resources will be permanently removed. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div class="mt-4">
                    <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-full text-sm"
                        placeholder="{{ __('Confirm with your password') }}"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="ms-3">
                        {{ __('Yes, Delete Account') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    @endif
</section>
