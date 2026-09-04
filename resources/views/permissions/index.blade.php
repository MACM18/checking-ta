<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Permission Manager') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Granular capability access control for workspace members (Checklists, Documents, Shipments).') }}
                </p>
            </div>
            <a href="{{ route('users.index') }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                &larr; Back to Users
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Status Messages -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-sm shadow-xs flex items-center">
                    <svg class="w-5 h-5 me-2 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('info'))
                <div class="p-4 bg-indigo-50 border-l-4 border-indigo-500 rounded-r-xl text-indigo-800 text-sm shadow-xs flex items-center">
                    <svg class="w-5 h-5 me-2 flex-shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('info') }}
                </div>
            @endif

            <!-- Info Guide Banner -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 flex items-start space-x-4">
                <div class="p-3 bg-indigo-50 text-indigo-700 rounded-xl flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">About Granular Capability Permissions</h3>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                        Administrators can grant custom capability permissions to editors or viewers (for example, allowing an editor or viewer to specifically manage and edit checklist templates, create shipments, or delete documents). Administrators always inherently possess all permissions.
                    </p>
                </div>
            </div>

            <!-- Permissions Matrix Table -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left whitespace-nowrap">User & Role</th>
                                @foreach($availablePermissions as $permKey => $permMeta)
                                    <th scope="col" class="px-3 py-3.5 text-center whitespace-nowrap" title="{{ $permMeta['description'] }}">
                                        {{ $permMeta['name'] }}
                                    </th>
                                @endforeach
                                <th scope="col" class="sticky right-0 z-20 bg-gray-50 px-6 py-3.5 text-right whitespace-nowrap shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200">Configure</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($users as $u)
                                <tr class="hover:bg-slate-50 transition group">
                                    <!-- User Info -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr {{ $u->isAdmin() ? 'from-purple-600 to-indigo-600' : 'from-indigo-600 to-blue-500' }} text-white font-bold flex items-center justify-center text-xs">
                                                {{ strtoupper(substr($u->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900 text-sm flex items-center space-x-2">
                                                    <span>{{ $u->name }}</span>
                                                    @if($u->isAdmin())
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200">
                                                            Admin
                                                        </span>
                                                    @elseif($u->isEditor())
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                            Editor
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                                            Viewer
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $u->email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    @if($u->isAdmin())
                                        <!-- Admin Inherently Has All -->
                                        <td colspan="{{ count($availablePermissions) }}" class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                <svg class="w-3.5 h-3.5 me-1 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Full System Master Permissions (Inherent)
                                            </span>
                                        </td>
                                    @else
                                        @foreach($availablePermissions as $permKey => $permMeta)
                                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                                @if($u->canAccess($permKey))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold {{ $permKey === \App\Models\User::PERM_DELETE_DOCUMENTS ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                                        Granted
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-300 font-bold">&mdash;</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    @endif

                                    <!-- Configure Action -->
                                    <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-6 py-4 whitespace-nowrap text-right text-xs font-medium shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                        @if(!$u->isAdmin())
                                            <a href="{{ route('permissions.edit', $u) }}" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold transition">
                                                <svg class="w-3.5 h-3.5 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                Configure
                                            </a>
                                        @else
                                            <span class="text-[11px] text-gray-400 italic">Locked (Admin)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
