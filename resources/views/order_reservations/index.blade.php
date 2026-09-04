<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                    <svg class="w-7 h-7 me-2.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    {{ __('Order Reservations & Stock Availability Tracker') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Track warehouse physical stock verification, confirm all available items, and record short parts/missing items for all Reserve (R) orders.
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.master-shortage', ['format' => 'excel']) }}" class="inline-flex items-center px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition shadow-2xs" title="Download Master Shortage Report (Excel)">
                    <svg class="w-3.5 h-3.5 me-1.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                    Master Shortage (XLSX)
                </a>
                <a href="{{ route('reports.master-shortage', ['format' => 'pdf']) }}" class="inline-flex items-center px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-xl text-xs font-bold transition shadow-2xs" title="Download Master Shortage Report (PDF)">
                    <svg class="w-3.5 h-3.5 me-1.5 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                    PDF
                </a>
                <a href="{{ route('order-reservations.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Record Old / External (R)
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center text-emerald-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Top Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Total Reservations</span>
                    <span class="text-2xl font-black text-gray-900 mt-1 block">{{ $metrics['total'] }}</span>
                    <span class="text-[11px] text-gray-500 mt-0.5 block">Reserve (ends with R) docs</span>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Pending Warehouse Check</span>
                    <span class="text-2xl font-black text-slate-700 mt-1 block">{{ $metrics['pending'] }}</span>
                    <span class="text-[11px] text-slate-400 mt-0.5 block">Awaiting physical check</span>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block">All Items Available</span>
                    <span class="text-2xl font-black text-emerald-600 mt-1 block">{{ $metrics['available'] }}</span>
                    <span class="text-[11px] text-emerald-700/70 mt-0.5 block">100% verified in warehouse</span>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                    <span class="text-xs font-bold text-amber-600 uppercase tracking-wider block">Shortage / Missing Parts</span>
                    <span class="text-2xl font-black text-amber-600 mt-1 block">{{ $metrics['shortage'] }}</span>
                    <span class="text-[11px] text-amber-700/70 mt-0.5 block">Has missing items recorded</span>
                </div>
            </div>

            <!-- Filter Tabs & Search Bar -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-4 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Status Filter Tabs -->
                    <div class="flex flex-wrap items-center gap-1.5 p-1 bg-gray-50 rounded-xl border border-gray-200/60">
                        <a href="{{ route('order-reservations.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'all' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                            All ({{ $metrics['total'] }})
                        </a>
                        <a href="{{ route('order-reservations.index', array_merge(request()->except('status', 'page'), ['status' => 'pending_check'])) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'pending_check' ? 'bg-white text-slate-800 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                            Pending Check ({{ $metrics['pending'] }})
                        </a>
                        <a href="{{ route('order-reservations.index', array_merge(request()->except('status', 'page'), ['status' => 'all_available'])) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'all_available' ? 'bg-white text-emerald-700 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                            All Available ({{ $metrics['available'] }})
                        </a>
                        <a href="{{ route('order-reservations.index', array_merge(request()->except('status', 'page'), ['status' => 'has_shortage'])) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'has_shortage' ? 'bg-white text-amber-700 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                            Has Shortage ({{ $metrics['shortage'] }})
                        </a>
                    </div>

                    <!-- Search Input Form -->
                    <form action="{{ route('order-reservations.index') }}" method="GET" class="flex items-center gap-2">
                        @if($status !== 'all')
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        <div class="relative w-full sm:w-72">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Reserve #, Client, Item..." class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                            Filter
                        </button>
                        @if(request('search'))
                            <a href="{{ route('order-reservations.index', ['status' => $status]) }}" class="text-xs text-gray-400 hover:text-gray-600 underline">
                                Clear
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50/80 text-gray-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5 text-left">Reserve Doc / ID</th>
                                <th class="px-5 py-3.5 text-left">Company & Country</th>
                                <th class="px-5 py-3.5 text-left">Warehouse Status</th>
                                <th class="px-5 py-3.5 text-center">Items Checked</th>
                                <th class="px-5 py-3.5 text-right">Requested Qty</th>
                                <th class="px-5 py-3.5 text-right">Available Qty</th>
                                <th class="px-5 py-3.5 text-right">Short Parts</th>
                                <th class="px-5 py-3.5 text-left">Warehouse Verification</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($reservations as $res)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('order-reservations.show', $res) }}" class="font-mono font-bold text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                                                {{ $res->reserve_document_number }}
                                            </a>
                                            @if($res->is_legacy_record)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200" title="Historical / External R Document">
                                                    Old / External
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-gray-400 font-mono block mt-0.5">{{ $res->reservation_number }}</span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-gray-900">{{ $res->company_name ?: '-' }}</div>
                                        @if($res->country)
                                            <div class="text-[11px] text-gray-400">{{ $res->country }}</div>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $res->status_badge_classes }}">
                                            @if($res->status === 'all_available')
                                                <svg class="w-3.5 h-3.5 me-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            @elseif($res->status === 'has_shortage')
                                                <svg class="w-3.5 h-3.5 me-1 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            @else
                                                <svg class="w-3.5 h-3.5 me-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            @endif
                                            {{ $res->status_label }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap text-center font-mono">
                                        <span class="font-bold text-gray-800">{{ $res->items_count }} items</span>
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap text-right font-mono font-semibold text-gray-700">
                                        {{ number_format($res->total_requested_qty, 2) }}
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap text-right font-mono font-bold text-emerald-700">
                                        {{ number_format($res->total_available_qty, 2) }}
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap text-right font-mono">
                                        @if($res->total_short_qty > 0 || $res->short_items_count > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-black bg-rose-100 text-rose-800 border border-rose-200">
                                                -{{ number_format($res->total_short_qty, 2) }} ({{ $res->short_items_count }} short)
                                            </span>
                                        @else
                                            <span class="text-gray-400 font-normal">0.00</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap">
                                        @if($res->warehouse_confirmed_at)
                                            <div class="text-[11px] font-semibold text-emerald-700 flex items-center">
                                                <svg class="w-3.5 h-3.5 me-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Verified {{ $res->warehouse_confirmed_at->format('M d, Y') }}
                                            </div>
                                            <div class="text-[10px] text-gray-400">By {{ $res->confirmedBy?->name ?? 'Warehouse' }}</div>
                                        @else
                                            <span class="text-[11px] text-gray-400 italic">Not yet confirmed</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap text-right space-x-2">
                                        <a href="{{ route('order-reservations.show', $res) }}" class="inline-flex items-center px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg font-bold text-xs transition">
                                            Audit Cockpit &rarr;
                                        </a>

                                        @if($res->short_items_count > 0 || $res->total_short_qty > 0)
                                            <a href="{{ route('order-reservations.print-shortage', $res) }}" target="_blank" class="inline-flex items-center px-2 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg font-bold text-xs transition" title="Print Missing Parts Sheet">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                            </a>
                                        @endif

                                        @if($res->document_id)
                                            <a href="{{ route('documents.show', $res->document_id) }}" class="inline-flex items-center px-2 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-lg text-xs transition" title="View Source Reserve Document">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                        <div class="max-w-md mx-auto space-y-3">
                                            <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                            <div class="font-bold text-gray-800 text-base">No Order Reservations Found</div>
                                            <p class="text-xs text-gray-500">
                                                When a Reserve document (ends with R) is created, it will automatically appear here for warehouse stock availability audit. You can also record shortages for old/external R documents.
                                            </p>
                                            <div class="pt-2">
                                                <a href="{{ route('order-reservations.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                    Record Old / External Reserve (R)
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($reservations->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $reservations->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
