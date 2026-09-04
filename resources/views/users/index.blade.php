<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                    <svg class="w-6 h-6 me-2.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    {{ __('User Management') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Manage workspace user accounts, assign roles, and control access permissions. Public registration is disabled.
                </p>
            </div>
            <div class="flex items-center space-x-2.5">
                <a href="{{ route('permissions.index') }}" class="inline-flex items-center px-3.5 py-2.5 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-sm font-semibold border border-purple-200 transition">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Permission Manager
                </a>
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add New User
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Success Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-md flex items-center text-emerald-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Flash Error Message -->
            @if(session('error'))
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-md flex items-center text-red-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Generated Magic Invitation Link Banner (Quick Share via WhatsApp, Teams, Slack) -->
            @if(session('generated_invite_link'))
                <div x-data="{ copied: false, link: '{{ session('generated_invite_link') }}' }" class="p-5 bg-gradient-to-r from-indigo-50 via-purple-50 to-white border-2 border-indigo-200 rounded-2xl shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">
                                🔗
                            </span>
                            <div>
                                <h4 class="font-black text-sm text-indigo-950">24-Hour Sign-in Invitation Link Ready for {{ session('invited_user_name') ?? 'User' }}</h4>
                                <p class="text-xs text-indigo-700">Share this direct link via WhatsApp, Slack, Teams, or SMS so the user can set their password and log in immediately.</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="text" readonly :value="link" @click="$el.select()" class="flex-1 text-xs font-mono bg-white rounded-xl border-indigo-200 text-gray-800 py-2 px-3 shadow-2xs focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="button" @click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2500)"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            <template x-if="!copied">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                    Copy Invite Link
                                </span>
                            </template>
                            <template x-if="copied">
                                <span class="flex items-center text-emerald-200 font-black">
                                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Copied to Clipboard!
                                </span>
                            </template>
                        </button>
                    </div>
                </div>
            @endif

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Accounts</span>
                    <span class="text-2xl font-black text-gray-900 mt-1 block">{{ $stats['total'] }}</span>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-purple-100 bg-purple-50/20">
                    <span class="text-xs font-semibold text-purple-600 uppercase tracking-wider block">Administrators</span>
                    <span class="text-2xl font-black text-purple-900 mt-1 block">{{ $stats['admins'] }}</span>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-blue-100 bg-blue-50/20">
                    <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block">Editors</span>
                    <span class="text-2xl font-black text-blue-900 mt-1 block">{{ $stats['editors'] }}</span>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Viewers (Read-Only)</span>
                    <span class="text-2xl font-black text-gray-700 mt-1 block">{{ $stats['viewers'] }}</span>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <form action="{{ route('users.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div class="w-full md:w-80 relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    <div class="flex items-center space-x-2 text-xs">
                        <a href="{{ route('users.index') }}" class="px-3 py-1.5 rounded-lg font-semibold {{ !request('role') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            All Roles
                        </a>
                        <a href="{{ route('users.index', ['role' => 'admin']) }}" class="px-3 py-1.5 rounded-lg font-semibold {{ request('role') === 'admin' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700 hover:bg-purple-100' }}">
                            Admins
                        </a>
                        <a href="{{ route('users.index', ['role' => 'editor']) }}" class="px-3 py-1.5 rounded-lg font-semibold {{ request('role') === 'editor' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                            Editors
                        </a>
                        <a href="{{ route('users.index', ['role' => 'viewer']) }}" class="px-3 py-1.5 rounded-lg font-semibold {{ request('role') === 'viewer' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            Viewers
                        </a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left">User</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Role & Permissions</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Created Date</th>
                                <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($users as $user)
                                <tr class="hover:bg-slate-50 transition">
                                    <!-- User Name & Email -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-9 h-9 rounded-full bg-gradient-to-br {{ $user->isAdmin() ? 'from-purple-500 to-indigo-600' : ($user->isEditor() ? 'from-blue-500 to-indigo-500' : 'from-gray-400 to-gray-600') }} text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900 flex items-center space-x-2">
                                                    <span>{{ $user->name }}</span>
                                                    @if($user->id === Auth::id())
                                                        <span class="px-1.5 py-0.2 text-[10px] font-bold bg-gray-100 text-gray-600 rounded">You</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-500 font-mono">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Role Badge -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1">
                                            @if($user->role === 'admin')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200 w-fit">
                                                    <svg class="w-3 h-3 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                                    Administrator
                                                </span>
                                            @elseif($user->role === 'editor')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200 w-fit">
                                                    Editor
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 w-fit">
                                                    Viewer (Read-Only)
                                                </span>
                                            @endif

                                            @if($user->must_set_password)
                                                <span class="inline-flex items-center text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 w-fit">
                                                    Invited (Pending Password)
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Created Date -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                        @if($user->id !== Auth::id())
                                            <!-- Direct Copy Invite to Clipboard Component -->
                                            <div x-data="{
                                                copied: false,
                                                loading: false,
                                                copyInvite() {
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
                                                    .catch(err => {
                                                        alert('Could not copy link: ' + err);
                                                    })
                                                    .finally(() => {
                                                        this.loading = false;
                                                    });
                                                }
                                            }" class="inline-block">
                                                <button type="button" @click="copyInvite()"
                                                        :class="copied ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border-indigo-200'"
                                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border transition shadow-2xs"
                                                        title="Copy 24-hour invite link to share directly via WhatsApp, Slack, Teams">
                                                    <template x-if="loading">
                                                        <span class="flex items-center">
                                                            <svg class="animate-spin w-3 h-3 me-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                                            Copying...
                                                        </span>
                                                    </template>
                                                    <template x-if="!loading && !copied">
                                                        <span class="flex items-center">
                                                            <svg class="w-3 h-3 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                            Copy Link
                                                        </span>
                                                    </template>
                                                    <template x-if="!loading && copied">
                                                        <span class="flex items-center text-emerald-800 font-black">
                                                            <svg class="w-3 h-3 me-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Copied!
                                                        </span>
                                                    </template>
                                                </button>
                                            </div>

                                            @if($user->must_set_password)
                                                <form action="{{ route('users.resend-invitation', $user) }}" method="POST" class="inline-block"
                                                      data-confirm="Resend a 24-hour invitation link to {{ $user->name }} ({{ $user->email }})?"
                                                      data-confirm-title="Resend Invitation Email"
                                                      data-confirm-button="Resend Email"
                                                      data-confirm-type="warning">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 font-medium border border-amber-200 transition" title="Resend 24-hour invitation email">
                                                        Email
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('users.resend-invitation', $user) }}" method="POST" class="inline-block"
                                                      data-confirm="Send a 24-hour magic sign-in invitation link to {{ $user->name }} ({{ $user->email }})?"
                                                      data-confirm-title="Send Magic Sign-in Link"
                                                      data-confirm-button="Send Email"
                                                      data-confirm-type="primary">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium border border-slate-200 transition" title="Send 24-hour magic login link via email">
                                                        <svg class="w-3 h-3 me-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                        Email
                                                    </button>
                                                </form>
                                            @endif
                                        @endif

                                        <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold transition">
                                            Edit
                                        </a>

                                        @if(!$user->isAdmin())
                                            <a href="{{ route('permissions.edit', $user) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold transition" title="Manage granular permissions">
                                                Permissions
                                            </a>
                                        @endif

                                        @if($user->isAdmin())
                                            <!-- Protected Admin: Deletion strictly prevented -->
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-400 font-bold cursor-not-allowed border border-slate-200" title="Administrator accounts are strictly protected from deletion">
                                                <svg class="w-3 h-3 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                Protected Admin
                                            </span>
                                        @elseif($user->id === Auth::id())
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-400 font-bold cursor-not-allowed border border-slate-200" title="You cannot delete yourself">
                                                Current User
                                            </span>
                                        @else
                                            <form action="{{ route('users.destroy', $user) }}"
                                                  method="POST"
                                                  class="inline-block"
                                                  data-confirm="Are you sure you want to permanently delete user account '{{ $user->name }}' ({{ $user->email }})? This action cannot be undone."
                                                  data-confirm-title="Delete User Account"
                                                  data-confirm-button="Yes, Delete User"
                                                  data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 font-bold border border-red-200 transition">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                        No users found matching your search.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
