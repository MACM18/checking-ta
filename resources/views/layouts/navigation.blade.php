<!-- Sidebar Component: Desktop Fixed Sidebar + Mobile Slide-Over Drawer -->
<div x-data="{ sidebarOpen: false }">

    <!-- Mobile Top Navigation Bar -->
    <div class="md:hidden sticky top-0 z-30 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-3">
            <button @click="sidebarOpen = true" type="button" class="p-2 -ml-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg focus:outline-none transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <a href="{{ route('documents.index') }}" class="flex items-center space-x-2.5">
                <img src="https://storage.macm.dev/portfolio/favicons/cmj8uwynb0000nj0jnkb3tk15/1786703371734.webp" alt="Checking TA Logo" class="w-8 h-8 rounded-lg object-contain shadow-xs">
                <span class="font-black text-base text-gray-900 tracking-tight">Checking TA</span>
            </a>
        </div>

        @if(Auth::user()->canEdit())
            <a href="{{ route('documents.create') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition">
                <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New
            </a>
        @endif
    </div>

    <!-- Mobile Slide-Over Backdrop -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/60 z-40 md:hidden"
         @click="sidebarOpen = false"
         style="display: none;"></div>

    <!-- Mobile Slide-Over Panel -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 flex flex-col w-72 max-w-full bg-white z-50 md:hidden shadow-2xl"
         style="display: none;">
        
        <!-- Mobile Sidebar Header -->
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="https://storage.macm.dev/portfolio/favicons/cmj8uwynb0000nj0jnkb3tk15/1786703371734.webp" alt="Checking TA Logo" class="w-9 h-9 rounded-xl object-contain shadow-xs">
                <div>
                    <h1 class="font-black text-base text-gray-900 leading-tight">Checking TA</h1>
                    <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider block">Document & Orders</span>
                </div>
            </div>
            <button @click="sidebarOpen = false" type="button" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Mobile Navigation Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-6">
            @if(Auth::user()->canEdit())
                <a href="{{ route('documents.create') }}" class="w-full flex items-center justify-center space-x-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>New Document</span>
                </a>
            @endif

            <nav class="space-y-1">
                <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400">Workspace</div>
                
                <a href="{{ route('documents.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('documents.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('documents.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Documents</span>
                </a>

                @if(Auth::user()->canManageShipments())
                    <a href="{{ route('shipment-orders.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('shipment-orders.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('shipment-orders.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        <span>Shipment Tracker</span>
                    </a>
                @endif

                @if(Auth::user()->canManageReservations())
                    <a href="{{ route('order-reservations.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('order-reservations.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('order-reservations.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span>Order Reservations</span>
                    </a>
                @endif

                @if(Auth::user()->canManagePriceTracker())
                    <a href="{{ route('price-tracker.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('price-tracker.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('price-tracker.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span>Price Tracker</span>
                    </a>
                @endif

                @if(Auth::user()->canViewReports())
                    <a href="{{ route('reports.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('reports.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('reports.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Reports & Exports</span>
                    </a>
                @endif

                @if(Auth::user()->canManageChecklists())
                    <a href="{{ route('checklists.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('checklists.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('checklists.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        <span>Checklist Templates</span>
                    </a>
                @endif

                @if(Auth::user()->canManageDocumentTypes())
                    <a href="{{ route('document-types.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('document-types.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('document-types.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span>Document Types</span>
                    </a>
                @endif

                @if(Auth::user()->isAdmin())
                    <div class="pt-4 px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-purple-600">Administration</div>
                    <a href="{{ route('users.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('users.*') ? 'bg-purple-50 text-purple-800 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('users.*') ? 'text-purple-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            <span>User Management</span>
                        </div>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-100 text-purple-700">Admin</span>
                    </a>
                    <a href="{{ route('permissions.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('permissions.*') ? 'bg-purple-50 text-purple-800 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('permissions.*') ? 'text-purple-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span>Permissions</span>
                        </div>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-100 text-purple-700">Admin</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- Mobile User Profile Section -->
        <div class="p-4 border-t border-gray-100 bg-slate-50">
            <div class="flex items-center space-x-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name }}</div>
                    <div class="text-[11px] text-gray-500 font-mono truncate">{{ Auth::user()->email }}</div>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ Auth::user()->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                    {{ Auth::user()->role }}
                </span>
            </div>
            <div class="flex items-center justify-between text-xs font-semibold pt-2 border-t border-gray-200">
                <a href="{{ route('profile.edit') }}" class="text-gray-600 hover:text-indigo-600">Profile Settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-red-600 hover:text-red-800">Log Out</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Desktop Permanent Sidebar (Fixed, 64 Width / 256px) -->
    <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 bg-white border-r border-slate-200/80 z-30 shadow-xs">
        
        <!-- App Brand Header -->
        <div class="h-16 flex items-center justify-between px-5 border-b border-gray-100 bg-white">
            <a href="{{ route('documents.index') }}" class="flex items-center space-x-3 group">
                <img src="https://storage.macm.dev/portfolio/favicons/cmj8uwynb0000nj0jnkb3tk15/1786703371734.webp" alt="Checking TA Logo" class="w-9 h-9 rounded-xl object-contain shadow-xs group-hover:scale-105 transition">
                <div>
                    <span class="font-black text-base text-gray-900 tracking-tight block">Checking TA</span>
                    <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider block">Document & Orders</span>
                </div>
            </a>
        </div>

        <!-- Quick Action Button -->
        @if(Auth::user()->canEdit())
            <div class="px-4 pt-5 pb-2">
                <a href="{{ route('documents.create') }}" class="w-full flex items-center justify-center space-x-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-sm hover:shadow transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>New Document</span>
                </a>
            </div>
        @endif

        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto px-3 py-3 space-y-6">
            <nav class="space-y-1">
                <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400">Workspace</div>

                <!-- Documents -->
                <a href="{{ route('documents.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('documents.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-gray-600 hover:bg-slate-100/70 hover:text-gray-900' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('documents.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Documents</span>
                </a>

                <!-- Shipment Tracker -->
                @if(Auth::user()->canManageShipments())
                    <a href="{{ route('shipment-orders.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('shipment-orders.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-gray-600 hover:bg-slate-100/70 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('shipment-orders.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        <span>Shipment Tracker</span>
                    </a>
                @endif

                <!-- Order Reservations -->
                @if(Auth::user()->canManageReservations())
                    <a href="{{ route('order-reservations.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('order-reservations.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-gray-600 hover:bg-slate-100/70 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('order-reservations.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span>Order Reservations</span>
                    </a>
                @endif

                <!-- Price Tracker -->
                @if(Auth::user()->canManagePriceTracker())
                    <a href="{{ route('price-tracker.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('price-tracker.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-gray-600 hover:bg-slate-100/70 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('price-tracker.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span>Price Tracker</span>
                    </a>
                @endif

                <!-- Reports & Exports -->
                @if(Auth::user()->canViewReports())
                    <a href="{{ route('reports.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('reports.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-gray-600 hover:bg-slate-100/70 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('reports.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Reports & Exports</span>
                    </a>
                @endif

                <!-- Checklist Templates -->
                @if(Auth::user()->canManageChecklists())
                    <a href="{{ route('checklists.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('checklists.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-gray-600 hover:bg-slate-100/70 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('checklists.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        <span>Checklist Templates</span>
                    </a>
                @endif

                <!-- Document Types -->
                @if(Auth::user()->canManageDocumentTypes())
                    <a href="{{ route('document-types.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('document-types.*') ? 'bg-indigo-50 text-indigo-700 font-bold shadow-2xs' : 'text-gray-600 hover:bg-slate-100/70 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('document-types.*') ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span>Document Types</span>
                    </a>
                @endif

                <!-- Admin Section -->
                @if(Auth::user()->isAdmin())
                    <div class="pt-5 px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-purple-600">Administration</div>
                    
                    <a href="{{ route('users.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('users.*') ? 'bg-purple-50 text-purple-900 font-bold shadow-2xs' : 'text-gray-600 hover:bg-slate-100/70 hover:text-gray-900' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('users.*') ? 'text-purple-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            <span>User Management</span>
                        </div>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-100 text-purple-700">Admin</span>
                    </a>

                    <a href="{{ route('permissions.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('permissions.*') ? 'bg-purple-50 text-purple-900 font-bold shadow-2xs' : 'text-gray-600 hover:bg-slate-100/70 hover:text-gray-900' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('permissions.*') ? 'text-purple-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span>Permissions</span>
                        </div>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-100 text-purple-700">Admin</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- Bottom User Card & Session Actions -->
        <div class="p-4 border-t border-gray-100 bg-slate-50/70">
            <div class="flex items-center space-x-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br {{ Auth::user()->isAdmin() ? 'from-purple-500 to-indigo-600' : (Auth::user()->isEditor() ? 'from-blue-500 to-indigo-500' : 'from-gray-400 to-gray-600') }} text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name }}</div>
                    <div class="text-[11px] text-gray-400 font-mono truncate">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-2.5 border-t border-gray-200/80">
                <div class="flex items-center space-x-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ Auth::user()->role === 'admin' ? 'bg-purple-100 text-purple-700' : (Auth::user()->role === 'editor' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                        {{ Auth::user()->role }}
                    </span>
                    <a href="{{ route('profile.edit') }}" class="text-gray-500 hover:text-gray-800 text-[11px] font-semibold">
                        Profile
                    </a>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-red-600 hover:text-red-800 text-[11px] font-bold transition">
                        Log Out
                    </button>
                </form>
            </div>
        </div>

    </aside>
</div>
