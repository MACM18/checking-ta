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

            <!-- Account Protection & Deletion Safeguard Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>
