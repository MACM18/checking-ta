<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Account Profile') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Manage your personal credentials, workspace role, and security settings.') }}
                </p>
            </div>
            @if($user->isAdmin())
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                    <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Workspace Administrator
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Error / Success Alerts -->
            @if(session('error'))
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-sm shadow-xs flex items-center">
                    <svg class="w-5 h-5 me-2 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-sm shadow-xs flex items-center">
                    <svg class="w-5 h-5 me-2 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Top Identity Hero Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                    <div class="flex items-center space-x-5">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr {{ $user->isAdmin() ? 'from-purple-600 to-indigo-600 text-white' : 'from-indigo-600 to-blue-500 text-white' }} flex items-center justify-center text-xl font-black shadow-md flex-shrink-0">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="flex items-center space-x-3">
                                <h1 class="text-xl font-extrabold text-gray-900">{{ $user->name }}</h1>
                                @if($user->isAdmin())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                        Administrator
                                    </span>
                                @elseif($user->isEditor())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        Editor
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                        Viewer
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 font-mono mt-0.5">{{ $user->email }}</p>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400 mt-2">
                                <span>Member since <strong>{{ $user->created_at->format('M d, Y') }}</strong></span>
                                <span>&bull;</span>
                                <span class="text-emerald-600 font-semibold flex items-center">
                                    <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Active Account
                                </span>
                                @if($user->isAdmin())
                                    <span>&bull;</span>
                                    <span class="text-purple-700 font-bold flex items-center">
                                        <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        Protected from Deletion
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($user->isAdmin())
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('permissions.index') }}" class="inline-flex items-center px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-xl text-xs font-bold border border-purple-200 transition">
                                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Permissions
                            </a>
                            <a href="{{ route('users.index') }}" class="inline-flex items-center px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-xl text-xs font-bold border border-purple-200 transition">
                                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                Manage Users
                            </a>
                            <a href="{{ route('users.create') }}" class="inline-flex items-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                + Invite User
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Special Admin Section: Workspace Overview & Quick Controls -->
            @if($user->isAdmin() && $adminStats)
                <div class="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-2xl p-6 text-white shadow-md border border-slate-800">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <span class="p-1.5 bg-indigo-500/20 text-indigo-400 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </span>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-indigo-200">
                                Administrator Workspace Controls
                            </h3>
                        </div>
                        <span class="text-[11px] text-indigo-300 font-mono">Full Privileges Active</span>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <a href="{{ route('users.index') }}" class="p-4 bg-white/5 hover:bg-white/10 rounded-xl border border-white/10 transition block">
                            <span class="text-xs text-indigo-300 block">Workspace Users</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $adminStats['total_users'] }}</span>
                            <span class="text-[11px] text-indigo-400 hover:text-white mt-1 block">&rarr; Manage Team</span>
                        </a>

                        <a href="{{ route('documents.index') }}" class="p-4 bg-white/5 hover:bg-white/10 rounded-xl border border-white/10 transition block">
                            <span class="text-xs text-indigo-300 block">Total Documents</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $adminStats['total_documents'] }}</span>
                            <span class="text-[11px] text-indigo-400 hover:text-white mt-1 block">&rarr; Browse All</span>
                        </a>

                        <a href="{{ route('shipment-orders.index') }}" class="p-4 bg-white/5 hover:bg-white/10 rounded-xl border border-white/10 transition block">
                            <span class="text-xs text-indigo-300 block">Active Shipments</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $adminStats['active_shipments'] }}</span>
                            <span class="text-[11px] text-indigo-400 hover:text-white mt-1 block">&rarr; Order Tracker</span>
                        </a>

                        <a href="{{ route('checklists.index') }}" class="p-4 bg-white/5 hover:bg-white/10 rounded-xl border border-white/10 transition block">
                            <span class="text-xs text-indigo-300 block">Checklist Templates</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $adminStats['total_templates'] }}</span>
                            <span class="text-[11px] text-indigo-400 hover:text-white mt-1 block">&rarr; Edit Checklist</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- Profile Details & Forms -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Update Profile Info -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <!-- Update Password -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Trusted Devices & 7-Day Auto-Login Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base text-gray-900 leading-tight">
                                {{ __('Trusted Devices & 7-Day Auto-Login') }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ __('Sessions are secured by a device signature (OS, browser & cryptographic HMAC). Auto-login stays active for 7 days.') }}
                            </p>
                        </div>
                    </div>

                    @if(isset($devices) && $devices->count() > 1)
                        <form action="{{ route('profile.devices.revoke-others') }}" method="POST"
                              data-confirm="Are you sure you want to sign out all other devices? Only this current browser will remain logged in."
                              data-confirm-title="Log Out Other Devices"
                              data-confirm-button="Log Out Others"
                              data-confirm-type="warning">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition">
                                <svg class="w-3.5 h-3.5 me-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                {{ __('Sign Out All Other Devices') }}
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Devices List -->
                <div class="space-y-3">
                    @forelse($devices ?? [] as $device)
                        @php
                            $isCurrent = isset($currentDeviceUuid) && $device->device_uuid === $currentDeviceUuid;
                        @endphp
                        <div class="p-4 rounded-xl border {{ $isCurrent ? 'bg-indigo-50/40 border-indigo-200' : 'bg-slate-50/60 border-gray-200/70' }} flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-9 h-9 rounded-xl {{ $isCurrent ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600' }} flex items-center justify-center flex-shrink-0 shadow-xs">
                                    @if(str_contains(strtolower($device->device_name), 'ios') || str_contains(strtolower($device->device_name), 'android'))
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    @endif
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-sm text-gray-900">{{ $device->device_name }}</span>
                                        @if($isCurrent)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                This Device (Current)
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-500 font-mono mt-0.5 space-x-2">
                                        @if($device->ip_address)
                                            <span>IP: {{ $device->ip_address }}</span>
                                            <span>&bull;</span>
                                        @endif
                                        <span>Active {{ $device->last_active_at ? $device->last_active_at->diffForHumans() : 'Just now' }}</span>
                                        <span>&bull;</span>
                                        <span class="text-indigo-600 font-medium">Valid until {{ $device->expires_at->format('M d, Y') }}</span>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ route('profile.devices.destroy', $device) }}" method="POST"
                                  data-confirm="Revoke {{ $device->device_name }}? {{ $isCurrent ? 'You will be signed out from this current browser.' : 'That device will be logged out and require password login.' }}"
                                  data-confirm-title="Revoke Device Session"
                                  data-confirm-button="Revoke Device"
                                  data-confirm-type="danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-200 transition">
                                    {{ $isCurrent ? __('Sign Out') : __('Revoke') }}
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="text-center py-6 text-gray-400 text-xs">
                            {{ __('No active device sessions found. A device session is established upon your next login.') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Account Protection & Deletion Safeguard Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>
