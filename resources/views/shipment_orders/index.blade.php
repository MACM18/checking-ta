<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Shipment Order Progress Tracker') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    End-to-end lifecycle tracking: From Proforma Invoice (PI) & Customer PO to Payment, Invoices, Dispatch, and Delivery.
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.ongoing-orders', ['format' => 'excel', 'status' => 'active']) }}" class="inline-flex items-center px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition shadow-2xs" title="Export Ongoing Orders to Excel">
                    <svg class="w-3.5 h-3.5 me-1.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                    Export Orders (XLSX)
                </a>
                <a href="{{ route('reports.ongoing-orders', ['format' => 'pdf', 'status' => 'active']) }}" class="inline-flex items-center px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-xl text-xs font-bold transition shadow-2xs" title="Export Ongoing Orders to PDF">
                    <svg class="w-3.5 h-3.5 me-1.5 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                    PDF
                </a>
                <a href="{{ route('shipment-orders.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    New Shipment Tracker
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-md flex items-center text-emerald-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Trackers</span>
                    <span class="text-2xl font-black text-gray-800 mt-1 block">{{ $stats['total'] }}</span>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold text-indigo-500 uppercase tracking-wider block">In Progress</span>
                    <span class="text-2xl font-black text-indigo-700 mt-1 block">{{ $stats['active'] }}</span>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold text-amber-500 uppercase tracking-wider block">Awaiting Customer PO</span>
                    <span class="text-2xl font-black text-amber-700 mt-1 block">{{ $stats['awaiting_po'] }}</span>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold text-blue-500 uppercase tracking-wider block">Dispatched / Transit</span>
                    <span class="text-2xl font-black text-blue-700 mt-1 block">{{ $stats['dispatched'] }}</span>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <span class="text-xs font-semibold text-emerald-500 uppercase tracking-wider block">Completed Orders</span>
                    <span class="text-2xl font-black text-emerald-700 mt-1 block">{{ $stats['completed'] }}</span>
                </div>
            </div>

            <!-- Filters & Search Bar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <form action="{{ route('shipment-orders.index') }}" method="GET" class="flex flex-col lg:flex-row gap-3 items-center justify-between">
                    <!-- Search Input -->
                    <div class="w-full lg:w-80 relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Order #, PO #, AWB, Ref..." class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    <!-- Dropdown Filters: Company Name & Shipment Category -->
                    <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                        <select name="company_name" onchange="this.form.submit()" class="text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Companies</option>
                            @foreach($companies as $comp)
                                <option value="{{ $comp }}" {{ request('company_name') === $comp ? 'selected' : '' }}>
                                    {{ $comp }}
                                </option>
                            @endforeach
                        </select>

                        <select name="category" onchange="this.form.submit()" class="text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Status Tabs -->
                        <div class="flex items-center space-x-1 text-xs">
                            <a href="{{ route('shipment-orders.index', array_merge(request()->except('status'), [])) }}" class="px-2.5 py-1.5 rounded-lg font-semibold {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                All
                            </a>
                            <a href="{{ route('shipment-orders.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" class="px-2.5 py-1.5 rounded-lg font-semibold {{ request('status') === 'active' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                Active
                            </a>
                            <a href="{{ route('shipment-orders.index', array_merge(request()->except('status'), ['status' => 'completed'])) }}" class="px-2.5 py-1.5 rounded-lg font-semibold {{ request('status') === 'completed' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                Completed
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left">Order & Originating Doc</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Category</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Customer / Company</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Customer PO</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Payment Status</th>
                                <th scope="col" class="px-6 py-3.5 text-left w-52">Lifecycle Progress</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Carrier / AWB</th>
                                <th scope="col" class="sticky right-0 z-20 bg-gray-50 px-6 py-3.5 text-right shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($orders as $order)
                                <tr class="hover:bg-slate-50 transition group">
                                    <!-- Order & Originating PI / Ref -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('shipment-orders.show', $order) }}" class="font-mono font-bold text-indigo-600 hover:text-indigo-900 text-base block">
                                                {{ $order->order_number }}
                                            </a>
                                            @if($order->status === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                    ✓ Completed
                                                </span>
                                            @endif
                                        </div>
                                        @if($order->document)
                                            <a href="{{ route('documents.show', $order->document) }}" class="inline-flex items-center text-xs text-gray-500 hover:text-indigo-600 font-mono mt-0.5">
                                                <span>PI: {{ $order->document->document_number }}</span>
                                            </a>
                                        @elseif($order->proforma_invoice_no)
                                            <span class="inline-flex items-center text-xs text-gray-600 font-mono mt-0.5">
                                                <span>PI: {{ $order->proforma_invoice_no }}</span>
                                            </span>
                                        @elseif($order->document_reference)
                                            <span class="inline-flex items-center text-[11px] text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200 font-mono mt-0.5" title="Older/External Document Reference">
                                                Ref: {{ $order->document_reference }}
                                            </span>
                                        @else
                                            <span class="text-[11px] text-gray-400">Direct Order</span>
                                        @endif

                                        @if($order->custom_status_message)
                                            <div class="mt-1.5 inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100/90 text-amber-900 border border-amber-300 shadow-2xs">
                                                <span class="w-2 h-2 rounded-full bg-amber-500 mr-1.5 animate-pulse flex-shrink-0"></span>
                                                <span>{{ $order->custom_status_message }}</span>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Shipment Category -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold
                                            {{ $order->shipment_category === 'Air Freight' ? 'bg-sky-100 text-sky-800 border border-sky-200' : '' }}
                                            {{ $order->shipment_category === 'Sea Freight' ? 'bg-blue-100 text-blue-800 border border-blue-200' : '' }}
                                            {{ $order->shipment_category === 'Courier / Express' ? 'bg-amber-100 text-amber-800 border border-amber-200' : '' }}
                                            {{ $order->shipment_category === 'Urgent / Priority' ? 'bg-red-100 text-red-800 border border-red-200' : '' }}
                                            {{ in_array($order->shipment_category, ['Standard', 'Road Freight', null]) ? 'bg-gray-100 text-gray-700 border border-gray-200' : '' }}
                                        ">
                                            {{ $order->shipment_category ?? 'Standard' }}
                                        </span>
                                    </td>

                                    <!-- Customer & Country -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ $order->company_name }}</div>
                                        <div class="text-xs text-gray-500 flex items-center mt-0.5">
                                            <svg class="w-3.5 h-3.5 me-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ $order->country }}
                                        </div>
                                    </td>

                                    <!-- Customer PO -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        @if($order->customer_po_number)
                                            <span class="font-mono font-bold text-gray-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                                {{ $order->customer_po_number }}
                                            </span>
                                            @if($order->customer_po_date)
                                                <div class="text-[10px] text-gray-400 mt-1">{{ $order->customer_po_date->format('M d, Y') }}</div>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center text-amber-700 bg-amber-50 px-2 py-0.5 rounded text-[11px] font-semibold border border-amber-200">
                                                Awaiting PO
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Payment Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($order->payment_status === 'fully_paid')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 me-1.5"></span>
                                                Fully Paid
                                            </span>
                                        @elseif($order->payment_status === 'advance_received')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 me-1.5"></span>
                                                Advance Paid
                                            </span>
                                        @elseif($order->payment_status === 'payment_submitted')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 me-1.5 animate-pulse"></span>
                                                Payment Submitted
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                Payment Pending
                                            </span>
                                        @endif
                                        @if($order->payment_reference)
                                            <div class="text-[10px] text-gray-400 font-mono mt-1">Ref: {{ $order->payment_reference }}</div>
                                        @endif
                                    </td>

                                    <!-- Lifecycle Progress -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-bold text-gray-700">{{ $order->progress_percent }}% Complete</span>
                                            <span class="text-[11px] text-gray-400">{{ $order->completed_milestones_count }} / 8 Stages</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full transition-all duration-300 {{ $order->progress_percent === 100 ? 'bg-emerald-500' : 'bg-indigo-600' }}" style="width: {{ $order->progress_percent }}%"></div>
                                        </div>
                                    </td>

                                    <!-- Carrier & Tracking -->
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        @if($order->tracking_awb_no)
                                            <div class="font-mono font-bold text-gray-900">{{ $order->tracking_awb_no }}</div>
                                            <div class="text-[10px] text-gray-500 uppercase">{{ $order->carrier_method ?: 'Courier' }}</div>
                                        @elseif($order->dispatch_date)
                                            <span class="text-gray-600">Dispatched: {{ $order->dispatch_date->format('M d') }}</span>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>

                                    <!-- Action -->
                                    <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-6 py-4 whitespace-nowrap text-right text-xs font-medium shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                        <div class="flex items-center justify-end space-x-2">
                                            @if($order->status !== 'completed')
                                                <form action="{{ route('shipment-orders.complete', $order) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            data-confirm="Are you sure you want to mark order {{ $order->order_number }} as Completed? This will complete all remaining milestone stages immediately."
                                                            data-confirm-title="Complete Shipment Order"
                                                            data-confirm-btn="Mark as Completed"
                                                            data-confirm-type="success"
                                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold border border-emerald-200 transition"
                                                            title="One-click complete all milestones">
                                                        <svg class="w-3.5 h-3.5 me-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Complete
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('shipment-orders.show', $order) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold transition">
                                                Track &rarr;
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                        <p class="mt-2 text-sm font-semibold text-gray-700">No shipment order trackers created yet</p>
                                        <p class="text-xs text-gray-400 mt-1">Start tracking from a Proforma Invoice (PI) or create a new order.</p>
                                        <div class="mt-4">
                                            <a href="{{ route('shipment-orders.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700 transition">
                                                Create First Order Tracker
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($orders->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
