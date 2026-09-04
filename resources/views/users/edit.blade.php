<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Edit User: ') }} <span class="text-indigo-600">{{ $user->name }}</span>
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Update user account details, role permissions, or reset their password.
                </p>
            </div>
            <a href="{{ route('users.index') }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                &larr; Back to Users
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('email')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Role & Permissions <span class="text-red-500">*</span>
                        </label>
                        <select name="role" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($roles as $value => $label)
                                <option value="{{ $value }}" {{ old('role', $user->role) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Optional Password Change -->
                    <div class="pt-4 border-t border-gray-100 space-y-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-gray-500">
                            Reset Password <span class="text-gray-400 font-normal lowercase">(leave blank to keep existing password)</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    New Password
                                </label>
                                <input type="password" name="password" minlength="8" placeholder="Minimum 8 characters" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('password')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Confirm New Password
                                </label>
                                <input type="password" name="password_confirmation" minlength="8" placeholder="Re-enter new password" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                        <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Card 2: Send 24-Hour Magic Invitation / Sign-In Link to User -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="flex items-start space-x-4 mb-4">
                    <div class="p-2.5 bg-indigo-100 text-indigo-700 rounded-xl flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            {{ __('Email Magic Sign-in / Invitation Link') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Send a secure, single-use <strong>24-hour magic sign-in link</strong> directly to <strong class="text-gray-700 font-mono">{{ $user->email }}</strong>. The user can click the link in their email to log in immediately.
                        </p>
                    </div>
                </div>

                <form action="{{ route('users.resend-invitation', $user) }}"
                      method="POST"
                      class="space-y-4 pt-2 border-t border-gray-100"
                      data-confirm="Send a 24-hour magic sign-in invitation email to {{ $user->name }} ({{ $user->email }})?"
                      data-confirm-title="Send Magic Login Invitation"
                      data-confirm-button="Yes, Send Email"
                      data-confirm-type="primary">
                    @csrf

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   name="reset_password"
                                   value="1"
                                   {{ $user->must_set_password ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500">
                            <span class="ms-2.5 text-xs font-bold text-gray-800">
                                Require user to choose a new password upon opening the link
                            </span>
                        </label>
                        <p class="text-[11px] text-gray-500 mt-1 ms-6">
                            If checked, the user is redirected to create a new password immediately after logging in. If unchecked, they enter their workspace directly.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-2">
                        @if($user->hasValidInvitation())
                            <div class="space-y-1.5 w-full sm:w-2/3" x-data="{ copied: false, link: '{{ $user->invitation_link }}' }">
                                <div class="text-xs text-amber-700 flex items-center font-bold">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 me-2 animate-pulse"></span>
                                    <span>Active link pending (expires {{ $user->invitation_expires_at->diffForHumans() }})</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <input type="text" readonly :value="link" @click="$el.select()" class="flex-1 text-xs font-mono bg-gray-50 rounded-lg border-gray-300 py-1.5 px-2.5">
                                    <button type="button" @click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2500)"
                                            class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition">
                                        <template x-if="!copied">
                                            <span>Copy Link</span>
                                        </template>
                                        <template x-if="copied">
                                            <span>Copied!</span>
                                        </template>
                                    </button>
                                </div>
                            </div>
                        @else
                            <div x-data="{
                                copied: false,
                                loading: false,
                                copyNewLink() {
                                    this.loading = true;
                                    fetch('{{ route('users.invitation-link', $user) }}', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json'
                                        }
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.link) {
                                            navigator.clipboard.writeText(data.link);
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 2500);
                                        }
                                    })
                                    .finally(() => { this.loading = false; });
                                }
                            }">
                                <button type="button" @click="copyNewLink()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold border border-indigo-200 transition">
                                    <template x-if="!copied && !loading">
                                        <span class="flex items-center">
                                            <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                            Generate & Copy Link (WhatsApp/Slack)
                                        </span>
                                    </template>
                                    <template x-if="loading">
                                        <span>Generating...</span>
                                    </template>
                                    <template x-if="copied">
                                        <span class="text-emerald-700 font-bold">Copied to Clipboard!</span>
                                    </template>
                                </button>
                            </div>
                        @endif

                        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition self-end sm:self-auto">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            {{ __('Send Email Invitation') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
