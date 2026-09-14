<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                    <svg class="w-7 h-7 me-2.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    {{ __('Supplier Orders & Shipment Tracker') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Manage B-number order sheets sent to suppliers, track incoming factory invoices/receipts, and reconcile pending items sheet-by-sheet.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if(Auth::user()->canViewReports())
                    <a href="{{ route('reports.purchase-orders', ['format' => 'pdf', 'status' => $statusFilter, 'search' => $searchQuery]) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-white hover:bg-gray-50 text-purple-700 border border-purple-200 rounded-xl text-xs font-bold transition shadow-2xs" title="Print Purchase Orders & Factory Shipments Report">
                        <svg class="w-4 h-4 me-1.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print PO Report (PDF)
                    </a>
                    <a href="{{ route('reports.purchase-orders', ['format' => 'excel', 'status' => $statusFilter, 'search' => $searchQuery]) }}" class="inline-flex items-center px-3 py-2 bg-white hover:bg-gray-50 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition shadow-2xs" title="Export Purchase Orders & Factory Shipments to Excel">
                        <svg class="w-4 h-4 me-1.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                        Export Excel
                    </a>
                @endif
                @if(Auth::user()->canEdit())
                    <a href="{{ route('documents.create', ['type' => 'factory_invoice']) }}" class="inline-flex items-center px-3.5 py-2 bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 rounded-xl text-xs font-bold transition shadow-2xs">
                        <svg class="w-4 h-4 me-1.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        + Add Factory Invoice (F)
                    </a>
                    <a href="{{ route('documents.create', ['type' => 'supplier_order']) }}" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        + New Supplier Order (B)
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center text-emerald-900 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Top KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Total B-Order Sheets</span>
                    <span class="text-3xl font-black text-gray-900 mt-1 block">{{ number_format($kpis['total_orders']) }}</span>
                    <span class="text-[11px] text-gray-500 mt-1 block">Purchase orders placed</span>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-purple-100">
                    <span class="text-xs font-bold text-purple-700 uppercase tracking-wider block">Ordered Quantity</span>
                    <span class="text-3xl font-black text-purple-900 mt-1 block">{{ number_format($kpis['total_ordered_qty']) }}</span>
                    <span class="text-[11px] text-purple-600 mt-1 block">Total units ordered</span>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-teal-100">
                    <span class="text-xs font-bold text-teal-700 uppercase tracking-wider block">Received from Factory</span>
                    <span class="text-3xl font-black text-teal-900 mt-1 block">{{ number_format($kpis['total_received_qty']) }}</span>
                    <span class="text-[11px] text-teal-600 mt-1 block">Verified via Factory Invoices</span>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-amber-100">
                    <span class="text-xs font-bold text-amber-800 uppercase tracking-wider block">Pending / Remaining</span>
                    <span class="text-3xl font-black text-amber-800 mt-1 block">{{ number_format($kpis['total_remaining_qty']) }}</span>
                    <span class="text-[11px] text-amber-700 mt-1 block">Units awaiting delivery</span>
                </div>
            </div>

            <!-- View Tabs: Order Sheets vs Pending Items Cross-Matrix -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50">
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('supplier-orders.index', ['tab' => 'sheets', 'status' => $statusFilter, 'q' => $searchQuery]) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 {{ $activeTab === 'sheets' ? 'bg-purple-600 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Order Sheets List ({{ $kpis['total_orders'] }})</span>
                        </a>
                        <a href="{{ route('supplier-orders.index', ['tab' => 'pending_items']) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 {{ $activeTab === 'pending_items' ? 'bg-amber-600 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Remaining Items Summary</span>
                        </a>
                    </div>

                    @if($activeTab === 'sheets')
                        <!-- Search & Status Filters -->
                        <form method="GET" action="{{ route('supplier-orders.index') }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                            <input type="hidden" name="tab" value="sheets">
                            <input type="hidden" name="status" value="{{ $statusFilter }}">
                            <div class="relative flex-1 sm:w-64">
                                <input type="text"
                                       name="q"
                                       value="{{ $searchQuery }}"
                                       placeholder="Search B-Number, supplier..."
                                       class="w-full text-xs font-medium rounded-xl border-gray-300 pl-8 pr-3 py-2 focus:ring-purple-500 focus:border-purple-500">
                                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            @if($searchQuery)
                                <a href="{{ route('supplier-orders.index', ['tab' => 'sheets', 'status' => $statusFilter]) }}" class="px-2.5 py-2 text-xs font-bold text-gray-500 hover:text-gray-700">Clear</a>
                            @endif
                        </form>
                    @endif
                </div>

                @if($activeTab === 'sheets')
                    <!-- Status Filter Pills -->
                    <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-bold text-gray-500 uppercase tracking-wider text-[11px] me-1">Status:</span>
                        <a href="{{ route('supplier-orders.index', ['tab' => 'sheets', 'status' => 'all', 'q' => $searchQuery]) }}"
                           class="px-3 py-1 rounded-lg font-bold transition {{ $statusFilter === 'all' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            All ({{ $kpis['total_orders'] }})
                        </a>
                        <a href="{{ route('supplier-orders.index', ['tab' => 'sheets', 'status' => 'partially_received', 'q' => $searchQuery]) }}"
                           class="px-3 py-1 rounded-lg font-bold transition {{ $statusFilter === 'partially_received' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                            Partially Received ({{ $kpis['partial_count'] }})
                        </a>
                        <a href="{{ route('supplier-orders.index', ['tab' => 'sheets', 'status' => 'pending', 'q' => $searchQuery]) }}"
                           class="px-3 py-1 rounded-lg font-bold transition {{ $statusFilter === 'pending' ? 'bg-slate-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            Pending ({{ $kpis['pending_count'] }})
                        </a>
                        <a href="{{ route('supplier-orders.index', ['tab' => 'sheets', 'status' => 'completed', 'q' => $searchQuery]) }}"
                           class="px-3 py-1 rounded-lg font-bold transition {{ $statusFilter === 'completed' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                            Fully Completed ({{ $kpis['completed_count'] }})
                        </a>
                    </div>

                    <!-- Order Sheets Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-3 text-left">Order Sheet (B-No)</th>
                                    <th class="px-4 py-3 text-left">Supplier / Factory</th>
                                    <th class="px-4 py-3 text-center">Date</th>
                                    <th class="px-4 py-3 text-right">Ordered</th>
                                    <th class="px-4 py-3 text-right">Received</th>
                                    <th class="px-4 py-3 text-right">Remaining</th>
                                    <th class="px-5 py-3 text-center w-36">Fulfillment</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-5 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($orderSheets as $sheet)
                                    @php
                                        $doc = $sheet['supplier_order'];
                                        $pct = $sheet['completion_percentage'];
                                    @endphp
                                    <tr class="hover:bg-slate-50 transition">
                                        <!-- Document Number -->
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('supplier-orders.show', $doc) }}" class="font-mono font-bold text-sm text-purple-700 hover:text-purple-900 hover:underline">
                                                    {{ $doc->document_number }}
                                                </a>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800">
                                                    B-Order
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-gray-500 block mt-0.5">{{ $sheet['items_count'] }} unique items</span>
                                        </td>

                                        <!-- Supplier Company -->
                                        <td class="px-4 py-3.5">
                                            <div class="font-semibold text-gray-900">{{ $doc->company_name }}</div>
                                            @if($doc->country)
                                                <span class="text-[11px] text-gray-500">{{ $doc->country }}</span>
                                            @endif
                                        </td>

                                        <!-- Date -->
                                        <td class="px-4 py-3.5 text-center text-gray-600 whitespace-nowrap font-medium">
                                            {{ $doc->document_date ? \Carbon\Carbon::parse($doc->document_date)->format('M d, Y') : '—' }}
                                        </td>

                                        <!-- Ordered -->
                                        <td class="px-4 py-3.5 text-right font-mono font-bold text-gray-900">
                                            {{ number_format($sheet['total_ordered_qty']) }}
                                        </td>

                                        <!-- Received -->
                                        <td class="px-4 py-3.5 text-right font-mono font-bold text-teal-700">
                                            {{ number_format($sheet['total_received_qty']) }}
                                            @if($sheet['factory_invoices_count'] > 0)
                                                <span class="block text-[10px] text-teal-600 font-normal">({{ $sheet['factory_invoices_count'] }} {{ \Illuminate\Support\Str::plural('shipment', $sheet['factory_invoices_count']) }})</span>
                                            @endif
                                        </td>

                                        <!-- Remaining -->
                                        <td class="px-4 py-3.5 text-right font-mono font-bold whitespace-nowrap">
                                            @if($sheet['total_remaining_qty'] > 0)
                                                <span class="text-amber-800 font-black">{{ number_format($sheet['total_remaining_qty']) }}</span>
                                            @else
                                                <span class="text-emerald-700 flex items-center justify-end">
                                                    <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    0
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Progress Bar -->
                                        <td class="px-5 py-3.5 text-center">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                                    <div class="h-2 rounded-full transition-all duration-500 {{ $pct >= 100 ? 'bg-emerald-500' : ($pct > 0 ? 'bg-amber-500' : 'bg-gray-300') }}"
                                                         style="width: {{ min(100, $pct) }}%"></div>
                                                </div>
                                                <span class="text-[11px] font-mono font-bold text-gray-700 min-w-[32px] text-right">{{ $pct }}%</span>
                                            </div>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            @if($sheet['overall_status'] === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    Completed
                                                </span>
                                            @elseif($sheet['overall_status'] === 'partially_received')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    Partial ({{ $pct }}%)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end space-x-1.5">
                                                <a href="{{ route('supplier-orders.show', $doc) }}"
                                                   class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg font-bold text-xs transition"
                                                   title="View reconciliation sheet & shipment logs">
                                                    Check Sheet
                                                </a>
                                                @if(Auth::user()->canEdit())
                                                    <a href="{{ route('documents.create', ['type' => 'factory_invoice', 'source_document_number' => $doc->document_number]) }}"
                                                       class="px-2 py-1 bg-teal-50 hover:bg-teal-100 text-teal-700 rounded-lg font-bold text-xs transition"
                                                       title="Receive items via Factory Invoice">
                                                        + Receive
                                                    </a>
                                                @endif
                                                <a href="{{ route('supplier-orders.print-sheet', $doc) }}"
                                                   target="_blank"
                                                   class="p-1 text-gray-400 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition"
                                                   title="Print Sheet">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            <p class="font-bold text-gray-700">No Supplier Order Sheets Found</p>
                                            <p class="text-xs text-gray-500 mt-1">Create a purchase order starting with "B" (e.g. B26001) or record a factory invoice.</p>
                                            @if(Auth::user()->canEdit())
                                                <a href="{{ route('documents.create', ['type' => 'supplier_order']) }}" class="inline-flex items-center px-4 py-2 mt-4 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                                                    + Create First B-Order Sheet
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <!-- Pending Items Matrix Tab -->
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">Unfulfilled / Pending Items Across All Orders</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Aggregated list of all items with remaining quantities awaiting delivery from suppliers.</p>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-48">Item Code</th>
                                        <th class="px-4 py-3 text-left">Description</th>
                                        <th class="px-4 py-3 text-right w-28">Total Ordered</th>
                                        <th class="px-4 py-3 text-right w-28">Total Received</th>
                                        <th class="px-4 py-3 text-right w-32">Remaining to Receive</th>
                                        <th class="px-5 py-3 text-left">Affected B-Order Sheets</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse($pendingItems as $pItem)
                                        <tr class="hover:bg-amber-50/40 transition">
                                            <td class="px-4 py-3 font-mono font-bold text-gray-900 whitespace-nowrap">
                                                {{ $pItem['item_code'] }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-700">
                                                {{ $pItem['description'] ?: '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-gray-800">
                                                {{ number_format($pItem['total_ordered']) }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-teal-700">
                                                {{ number_format($pItem['total_received']) }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-black text-amber-800">
                                                {{ number_format($pItem['total_remaining']) }}
                                            </td>
                                            <td class="px-5 py-3">
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($pItem['orders'] as $ord)
                                                        <a href="{{ route('supplier-orders.show', $ord['document_uuid']) }}"
                                                           class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200"
                                                           title="Ordered: {{ $ord['ordered'] }} | Received: {{ $ord['received'] }} | Remaining: {{ $ord['remaining'] }}">
                                                            <span class="font-mono">{{ $ord['document_number'] }}</span>
                                                            <span class="ms-1.5 text-[10px] text-amber-800 font-mono font-black">({{ $ord['remaining'] }} left)</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                                <svg class="w-12 h-12 mx-auto text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <p class="font-bold text-gray-800">All Items Fulfilled!</p>
                                                <p class="text-xs text-gray-500 mt-1">There are currently no pending remaining items across any open supplier orders.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
