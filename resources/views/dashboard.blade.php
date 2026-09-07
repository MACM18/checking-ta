<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold text-indigo-600 uppercase tracking-wider mb-1">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Executive Operations Hub</span>
                    <span class="text-gray-300">•</span>
                    <span class="text-gray-500 font-mono">{{ now()->format('l, F j, Y') }}</span>
                </div>
                <h2 class="font-black text-2xl md:text-3xl text-gray-900 tracking-tight flex items-center">
                    Welcome back, {{ Auth::user()->name }}
                </h2>
                <p class="text-xs md:text-sm text-gray-500 mt-1">
                    Centralized operational dashboard with live document activity, active shipment logistics, warehouse shortages, and the unified report export hub.
                </p>
            </div>

            <!-- Global Quick Actions Bar -->
            <div class="flex flex-wrap items-center gap-2">
                @if(Auth::user()->canEdit())
                    <!-- New Document Button with Type Popover -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false" type="button" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>New Document</span>
                            <svg class="w-3.5 h-3.5 ms-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50 text-xs">
                            <div class="px-3 py-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Select Document Type</div>
                            <a href="{{ route('documents.create', ['type' => 'proforma_invoice']) }}" class="flex items-center px-3.5 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 font-semibold transition">
                                <span class="w-2 h-2 rounded-full bg-blue-500 me-2.5"></span>
                                Proforma Invoice (E / EL)
                            </a>
                            <a href="{{ route('documents.create', ['type' => 'invoice']) }}" class="flex items-center px-3.5 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 font-semibold transition">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 me-2.5"></span>
                                Commercial Invoice (N)
                            </a>
                            <a href="{{ route('documents.create', ['type' => 'packing_list']) }}" class="flex items-center px-3.5 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 font-semibold transition">
                                <span class="w-2 h-2 rounded-full bg-amber-500 me-2.5"></span>
                                Packing List (W)
                            </a>
                            <a href="{{ route('documents.create', ['type' => 'reserve']) }}" class="flex items-center px-3.5 py-2 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 font-semibold transition">
                                <span class="w-2 h-2 rounded-full bg-purple-500 me-2.5"></span>
                                Reserve Document (R)
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('documents.create') }}" class="flex items-center px-3.5 py-2 text-indigo-600 hover:bg-indigo-50 font-bold transition">
                                <svg class="w-3.5 h-3.5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                Standard Create Form
                            </a>
                        </div>
                    </div>
                @endif

                @if(Auth::user()->canManageShipments())
                    <a href="{{ route('shipment-orders.create') }}" class="inline-flex items-center px-3.5 py-2.5 bg-white hover:bg-slate-50 text-gray-700 border border-gray-200 rounded-xl text-xs font-bold transition shadow-2xs">
                        <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        <span>New Shipment</span>
                    </a>
                @endif

                @if(Auth::user()->canManageReservations())
                    <a href="{{ route('order-reservations.create') }}" class="inline-flex items-center px-3.5 py-2.5 bg-white hover:bg-slate-50 text-gray-700 border border-gray-200 rounded-xl text-xs font-bold transition shadow-2xs">
                        <svg class="w-4 h-4 me-1.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span>New Reservation</span>
                    </a>
                @endif

                @if(Auth::user()->canViewReports())
                    <a href="#centralized-export-hub" class="inline-flex items-center px-3.5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200/80 rounded-xl text-xs font-bold transition shadow-2xs">
                        <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Exports Hub</span>
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- 1. High-Impact Centralized KPI Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Documents KPI Card -->
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100 hover:border-indigo-200 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Documents</span>
                        <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-black text-gray-900">{{ number_format($totalDocuments) }}</span>
                        <span class="text-xs font-mono font-bold text-gray-500">USD {{ number_format($totalFinancialValue, 2) }}</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px]">
                        <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full">{{ $issuedDocsCount }} Issued</span>
                        <span class="text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded-full">{{ $draftDocsCount }} Drafts</span>
                        <a href="{{ route('documents.index') }}" class="text-indigo-600 hover:text-indigo-800 font-bold inline-flex items-center">
                            View all →
                        </a>
                    </div>
                </div>

                <!-- Shipments KPI Card -->
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100 hover:border-indigo-200 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-indigo-500 uppercase tracking-wider">Active Shipments</span>
                        <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-black text-indigo-700">{{ $activeShipmentsCount }}</span>
                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ $completedShipmentsCount }} Completed</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px]">
                        <span class="text-amber-700 font-semibold">{{ $pendingPaymentShipmentsCount }} pending payment</span>
                        <a href="{{ route('shipment-orders.index') }}" class="text-indigo-600 hover:text-indigo-800 font-bold inline-flex items-center">
                            Tracker →
                        </a>
                    </div>
                </div>

                <!-- Warehouse Shortage KPI Card -->
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100 hover:border-rose-200 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Warehouse Shortages</span>
                        <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-black text-rose-600">{{ number_format($totalShortParts, 2) }}</span>
                        <span class="text-xs font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">{{ $shortageReservationsCount }} with Shortages</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px]">
                        <span class="text-gray-500 font-medium">{{ $shortItemsCount }} short SKU items</span>
                        <a href="{{ route('order-reservations.index') }}" class="text-rose-600 hover:text-rose-800 font-bold inline-flex items-center">
                            Resolutions →
                        </a>
                    </div>
                </div>

                <!-- Price Tracker & SKUs KPI Card -->
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100 hover:border-emerald-200 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Price Database</span>
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-black text-emerald-700">{{ number_format($priceTrackerCount) }}</span>
                        <span class="text-xs font-bold text-gray-500">{{ $priceListsCount }} Price Lists</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px]">
                        <span class="text-gray-400 truncate">{{ $latestPriceUpdate ? 'Updated ' . \Carbon\Carbon::parse($latestPriceUpdate)->diffForHumans() : 'No price list uploaded' }}</span>
                        @if(Auth::user()->canManagePriceTracker())
                            <a href="{{ route('price-tracker.index') }}" class="text-emerald-700 hover:text-emerald-900 font-bold inline-flex items-center">
                                Prices →
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 2. Live Operations Overview Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Left 7 Cols: Recent Documents -->
                <div class="lg:col-span-7 bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                                <h3 class="font-black text-sm text-gray-900 uppercase tracking-wider">Recent Documents</h3>
                            </div>
                            <a href="{{ route('documents.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                                All Documents ({{ $totalDocuments }}) →
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-xs">
                                <thead class="bg-slate-50/60 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left">Document #</th>
                                        <th class="px-4 py-2.5 text-left">Type / Company</th>
                                        <th class="px-4 py-2.5 text-right">Amount / Net Wt</th>
                                        <th class="px-4 py-2.5 text-center">Status</th>
                                        <th class="px-4 py-2.5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 font-medium">
                                    @forelse($recentDocuments as $doc)
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="px-4 py-2.5 font-mono font-bold text-gray-900 whitespace-nowrap">
                                                <a href="{{ route('documents.show', $doc) }}" class="hover:text-indigo-600 hover:underline">
                                                    {{ $doc->document_number }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-gray-900 truncate max-w-[180px]">{{ $doc->company_name }}</span>
                                                    <span class="text-[10px] text-gray-400 capitalize">{{ str_replace('_', ' ', $doc->document_type) }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-mono font-bold text-gray-900 whitespace-nowrap">
                                                @if(in_array($doc->document_type, ['packing_list', 'reserve']))
                                                    {{ number_format($doc->total_net_weight ?? 0, 3) }} kg
                                                @else
                                                    {{ $doc->currency }} {{ number_format($doc->final_total, 2) }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                                @if($doc->status === 'draft')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">Draft</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Issued</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right whitespace-nowrap space-x-1.5 font-bold text-[11px]">
                                                <a href="{{ route('documents.show', $doc) }}" class="text-indigo-600 hover:text-indigo-800">View</a>
                                                <a href="{{ route('documents.print', $doc) }}" target="_blank" class="text-gray-500 hover:text-gray-800" title="Print document">Print</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-gray-400">No documents found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="px-6 py-3 bg-slate-50 border-t border-gray-100 text-xs flex justify-between items-center text-gray-500">
                        <span>Showing {{ $recentDocuments->count() }} most recent records</span>
                        <a href="{{ route('documents.create') }}" class="font-bold text-indigo-600 hover:text-indigo-800">+ Create Document</a>
                    </div>
                </div>

                <!-- Right 5 Cols: Active Shipments Tracker -->
                <div class="lg:col-span-5 bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                <h3 class="font-black text-sm text-gray-900 uppercase tracking-wider">Active Shipments In Transit</h3>
                            </div>
                            <a href="{{ route('shipment-orders.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition">
                                All ({{ $activeShipmentsCount }}) →
                            </a>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse($recentShipments as $shipment)
                                @php
                                    $completedMilestones = $shipment->milestones->where('is_completed', true)->count();
                                    $totalMilestones = max(1, $shipment->milestones->count() ?: 6);
                                    $progressPct = round(($completedMilestones / $totalMilestones) * 100);
                                @endphp
                                <div class="p-4 hover:bg-slate-50/80 transition">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <a href="{{ route('shipment-orders.show', $shipment) }}" class="font-mono font-bold text-sm text-gray-900 hover:text-indigo-600">
                                                {{ $shipment->order_number }}
                                            </a>
                                            <div class="text-xs font-bold text-gray-700 truncate max-w-[200px]">{{ $shipment->company_name }}</div>
                                            <div class="text-[11px] text-gray-400 mt-0.5">
                                                {{ $shipment->carrier_method ?: 'Carrier TBD' }} 
                                                @if($shipment->tracking_awb_no)
                                                    • <span class="font-mono font-bold text-gray-600">AWB: {{ $shipment->tracking_awb_no }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right flex flex-col items-end">
                                            @if($shipment->status === 'completed')
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Completed</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Stage {{ $shipment->current_stage }}/6</span>
                                            @endif
                                            <span class="text-[10px] font-bold mt-1 {{ $shipment->payment_status === 'fully_paid' ? 'text-emerald-600' : 'text-amber-600' }}">
                                                {{ str_replace('_', ' ', $shipment->payment_status) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="mt-2.5">
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-500" style="width: {{ $progressPct }}%"></div>
                                        </div>
                                        <div class="flex justify-between items-center text-[10px] text-gray-400 mt-1 font-semibold">
                                            <span>{{ $progressPct }}% Complete</span>
                                            <span>{{ $shipment->delivery_date ? 'Delivery: ' . $shipment->delivery_date->format('M d, Y') : 'In progress' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-gray-400 text-xs">
                                    No active shipments being tracked right now.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="px-6 py-3 bg-slate-50 border-t border-gray-100 text-xs flex justify-between items-center text-gray-500">
                        <span>Milestone delivery tracking</span>
                        <a href="{{ route('shipment-orders.create') }}" class="font-bold text-indigo-600 hover:text-indigo-800">+ New Shipment</a>
                    </div>
                </div>
            </div>

            <!-- 3. Warehouse Urgent Stock Shortages (Shown when shortage items exist) -->
            @if($urgentShortages->isNotEmpty())
                <div class="bg-rose-50/40 rounded-2xl p-6 border border-rose-200/80 shadow-xs">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-4">
                        <div class="flex items-center space-x-2.5">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            </span>
                            <h3 class="font-black text-sm text-rose-900 uppercase tracking-wider">
                                Urgent Warehouse Stock Shortages (Missing Parts Alert)
                            </h3>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('reports.master-shortage', ['format' => 'excel']) }}" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-xs">
                                <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Master Shortage (Excel)
                            </a>
                            <a href="{{ route('reports.master-shortage', ['format' => 'pdf']) }}" class="inline-flex items-center px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition shadow-xs">
                                PDF Report
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($urgentShortages as $res)
                            <div class="bg-white rounded-xl p-4 border border-rose-200/80 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-mono font-black text-xs text-rose-950">{{ $res->reservation_number }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                            {{ $res->short_items_count }} Short Item(s)
                                        </span>
                                    </div>
                                    <div class="font-bold text-xs text-gray-900 mt-1 truncate">{{ $res->company_name }}</div>
                                    <div class="text-[11px] text-gray-500 font-mono mt-0.5">
                                        Reserve Doc: {{ $res->reserve_document_number ?: 'N/A' }}
                                    </div>
                                    <div class="mt-2 text-[11px] text-rose-700 font-bold bg-rose-50 px-2 py-1 rounded-lg">
                                        Total Missing Units: {{ number_format($res->total_short_qty, 2) }}
                                    </div>
                                </div>
                                <div class="mt-3 pt-2.5 border-t border-gray-100 flex items-center justify-between text-xs font-bold">
                                    <a href="{{ route('order-reservations.show', $res) }}" class="text-indigo-600 hover:text-indigo-800">View Details →</a>
                                    <a href="{{ route('order-reservations.print-shortage', $res) }}" target="_blank" class="text-rose-600 hover:text-rose-800 flex items-center">
                                        <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        Picking Slip
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 4. Centralized Reports & PDF Generation Hub -->
            <div id="centralized-export-hub" class="bg-white rounded-2xl shadow-sm border border-gray-200/90 overflow-hidden">
                
                <!-- Hub Header -->
                <div class="p-6 bg-gradient-to-r from-slate-900 to-indigo-950 text-white">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center backdrop-blur-xs text-indigo-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-black text-lg md:text-xl text-white leading-tight">
                                    Centralized Reports & Document Exports Center
                                </h3>
                                <p class="text-xs text-indigo-200 mt-1">
                                    Official high-fidelity Excel (.xlsx) spreadsheets & formatted PDF documents for operational auditing, freight logistics, and inventory management.
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                            Unified Export Hub
                        </span>
                    </div>
                </div>

                <!-- 3 Export Hub Generator Cards -->
                <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Generator 1: Freight & Weights Orders Log -->
                    <div class="bg-slate-50/70 rounded-2xl border border-gray-200/80 p-5 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-center space-x-2.5 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                    1
                                </div>
                                <h4 class="font-black text-sm text-gray-900">Freight & Weights Orders Log</h4>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed mb-3">
                                Complete order cargo log including Net & Gross weights, package counts, CBM, and quoted carrier freight charges (DHL, Air, Sea, Road, Courier).
                            </p>

                            <form id="dash-form-freight" action="{{ route('reports.freight-weights') }}" method="GET" class="space-y-2.5">
                                <input type="hidden" name="format" id="dash-format-freight" value="excel">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-0.5">From Date</label>
                                        <input type="date" name="start_date" class="w-full text-xs rounded-lg border-gray-300 py-1 px-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-gray-500 mb-0.5">To Date</label>
                                        <input type="date" name="end_date" class="w-full text-xs rounded-lg border-gray-300 py-1 px-2 focus:ring-indigo-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-0.5">Document Type</label>
                                    <select name="document_type" class="w-full text-xs rounded-lg border-gray-300 py-1 px-2 focus:ring-indigo-500">
                                        <option value="all">All Document Types</option>
                                        @foreach($documentTypes as $code => $label)
                                            <option value="{{ $code }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-0.5">Carrier Shipping Method</label>
                                    <select name="carrier_method" class="w-full text-xs rounded-lg border-gray-300 py-1 px-2 focus:ring-indigo-500">
                                        <option value="all">All Carrier Methods</option>
                                        @foreach($carrierMethods as $code => $label)
                                            <option value="{{ $code }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>

                        <div class="pt-3 border-t border-gray-200 grid grid-cols-2 gap-2">
                            <button type="button" onclick="document.getElementById('dash-format-freight').value='excel'; document.getElementById('dash-form-freight').submit();" class="w-full inline-flex items-center justify-center px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                <svg class="w-3.5 h-3.5 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                                Excel (.xlsx)
                            </button>
                            <button type="button" onclick="document.getElementById('dash-format-freight').value='pdf'; document.getElementById('dash-form-freight').submit();" class="w-full inline-flex items-center justify-center px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                <svg class="w-3.5 h-3.5 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                PDF (.pdf)
                            </button>
                        </div>
                    </div>

                    <!-- Generator 2: Ongoing Orders Progress Tracker -->
                    <div class="bg-slate-50/70 rounded-2xl border border-gray-200/80 p-5 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-center space-x-2.5 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                    2
                                </div>
                                <h4 class="font-black text-sm text-gray-900">Ongoing Orders & Shipments Log</h4>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed mb-3">
                                Live status of shipment orders, payment confirmations, draft documents sent, carrier method, AWB tracking, and milestone completion.
                            </p>

                            <form id="dash-form-ongoing" action="{{ route('reports.ongoing-orders') }}" method="GET" class="space-y-2.5">
                                <input type="hidden" name="format" id="dash-format-ongoing" value="excel">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-0.5">Order Status</label>
                                    <select name="status" class="w-full text-xs rounded-lg border-gray-300 py-1 px-2 focus:ring-indigo-500">
                                        <option value="active">Active Ongoing Shipments Only</option>
                                        <option value="completed">Completed Shipments Only</option>
                                        <option value="all">All Records (Active & Completed)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-0.5">Shipment Category</label>
                                    <select name="category" class="w-full text-xs rounded-lg border-gray-300 py-1 px-2 focus:ring-indigo-500">
                                        <option value="all">All Shipment Categories</option>
                                        <option value="Air Freight">Air Freight</option>
                                        <option value="Sea Freight">Sea Freight</option>
                                        <option value="Courier / Express">Courier / Express</option>
                                        <option value="Road Freight">Road Freight</option>
                                        <option value="Standard">Standard</option>
                                        <option value="Urgent / Priority">Urgent / Priority</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-0.5">Payment Verification Status</label>
                                    <select name="payment_status" class="w-full text-xs rounded-lg border-gray-300 py-1 px-2 focus:ring-indigo-500">
                                        <option value="all">All Payment Statuses</option>
                                        <option value="pending">Pending Payment</option>
                                        <option value="payment_submitted">Payment Submitted (Receipt Attached)</option>
                                        <option value="advance_received">Advance Payment Received</option>
                                        <option value="fully_paid">Fully Paid & Verified</option>
                                    </select>
                                </div>
                            </form>
                        </div>

                        <div class="pt-3 border-t border-gray-200 grid grid-cols-2 gap-2">
                            <button type="button" onclick="document.getElementById('dash-format-ongoing').value='excel'; document.getElementById('dash-form-ongoing').submit();" class="w-full inline-flex items-center justify-center px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                <svg class="w-3.5 h-3.5 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                                Excel (.xlsx)
                            </button>
                            <button type="button" onclick="document.getElementById('dash-format-ongoing').value='pdf'; document.getElementById('dash-form-ongoing').submit();" class="w-full inline-flex items-center justify-center px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                <svg class="w-3.5 h-3.5 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                PDF (.pdf)
                            </button>
                        </div>
                    </div>

                    <!-- Generator 3: Warehouse Master Consolidated Shortage Report -->
                    <div class="bg-slate-50/70 rounded-2xl border border-gray-200/80 p-5 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-center space-x-2.5 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs">
                                    3
                                </div>
                                <h4 class="font-black text-sm text-gray-900">Master Shortage Parts Report</h4>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed mb-3">
                                Master consolidated inventory shortage report aggregating missing and short parts across all Reserve (R) orders for warehouse procurement.
                            </p>

                            <form id="dash-form-shortage" action="{{ route('reports.master-shortage') }}" method="GET" class="space-y-2.5">
                                <input type="hidden" name="format" id="dash-format-shortage" value="excel">
                                <div class="p-3 bg-rose-50/80 border border-rose-100 rounded-xl space-y-1 text-xs">
                                    <div class="flex justify-between items-center text-rose-900 font-bold">
                                        <span>Active Shortage Orders:</span>
                                        <span>{{ $shortageReservationsCount }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-rose-800 font-medium text-[11px]">
                                        <span>Total Short SKU Lines:</span>
                                        <span>{{ $shortItemsCount }} items</span>
                                    </div>
                                    <div class="flex justify-between items-center text-rose-950 font-black text-xs pt-1 border-t border-rose-200/60">
                                        <span>Total Short Quantity:</span>
                                        <span>{{ number_format($totalShortParts, 2) }}</span>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="pt-3 border-t border-gray-200 grid grid-cols-2 gap-2">
                            <button type="button" onclick="document.getElementById('dash-format-shortage').value='excel'; document.getElementById('dash-form-shortage').submit();" class="w-full inline-flex items-center justify-center px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                <svg class="w-3.5 h-3.5 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                                Master (XLSX)
                            </button>
                            <button type="button" onclick="document.getElementById('dash-format-shortage').value='pdf'; document.getElementById('dash-form-shortage').submit();" class="w-full inline-flex items-center justify-center px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                <svg class="w-3.5 h-3.5 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                Master (PDF)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
