<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Add New Workspace User') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Create an authorized user account and assign their operational permission level.
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
                <form action="{{ route('users.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="e.g. John Doe" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="user@company.com" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
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
                                <option value="{{ $value }}" {{ old('role', 'editor') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ sendInvite: true }">
                        <!-- Invitation Checkbox -->
                        <div class="p-4 bg-indigo-50/70 border border-indigo-100 rounded-xl space-y-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="send_invitation" value="1" x-model="sendInvite" class="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500">
                                <span class="ms-2 text-xs font-bold text-indigo-950">
                                    Send 24-Hour Magic Invitation Link via Email
                                </span>
                            </label>
                            <p class="text-xs text-indigo-700" x-show="sendInvite">
                                The user will receive an email containing a secure 1-click login link valid for 24 hours. Upon signing in, they will be required to create their personal password.
                            </p>
                        </div>

                        <!-- Manual Password Input (if not sending invite) -->
                        <div x-show="!sendInvite" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Temporary Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="password" minlength="8" placeholder="Minimum 8 characters" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('password')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Confirm Temporary Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="password_confirmation" minlength="8" placeholder="Re-enter password" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                        <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm transition">
                            Create User Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
