<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('order-reservations.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">&larr; Back to Order Reservations</a>
                    <span class="text-gray-300">/</span>
                    <span class="text-xs font-mono font-bold text-gray-500">{{ $orderReservation->reservation_number }}</span>
                </div>
                <div class="flex items-center space-x-3 mt-1">
                    <h2 class="font-black text-2xl text-gray-900 leading-tight font-mono">
                        {{ $orderReservation->reserve_document_number }}
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $orderReservation->status_badge_classes }}">
                        {{ $orderReservation->status_label }}
                    </span>
                    @if($orderReservation->is_legacy_record)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                            Old / External Record
                        </span>
                    @endif
                </div>
            </div>

            <!-- Top Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2.5">
                @if($orderReservation->document_id)
                    <a href="{{ route('documents.show', $orderReservation->document_id) }}" class="inline-flex items-center px-3.5 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-bold shadow-2xs transition">
                        <svg class="w-4 h-4 me-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        View Reserve Doc
                    </a>
                @endif

                @if($orderReservation->short_items_count > 0 || $orderReservation->total_short_qty > 0)
                    <a href="{{ route('reports.reservation-shortage', ['orderReservation' => $orderReservation, 'format' => 'excel']) }}" class="inline-flex items-center px-3 py-2 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-bold shadow-2xs transition" title="Export Shortage to Excel">
                        <svg class="w-3.5 h-3.5 me-1 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('reports.reservation-shortage', ['orderReservation' => $orderReservation, 'format' => 'pdf']) }}" class="inline-flex items-center px-3 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-800 rounded-xl text-xs font-bold shadow-2xs transition" title="Export Shortage to PDF">
                        <svg class="w-3.5 h-3.5 me-1 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                        PDF
                    </a>
                    <a href="{{ route('order-reservations.print-shortage', $orderReservation) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-bold shadow-2xs transition">
                        <svg class="w-3.5 h-3.5 me-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print View
                    </a>
                @endif

                <!-- One-click Warehouse Confirmation -->
                <form action="{{ route('order-reservations.confirm-all', $orderReservation) }}" method="POST" onsubmit="return confirm('Confirm that ALL items are physically verified and 100% available in warehouse? This will mark all items available with zero shortage.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Confirm All Available (Warehouse)
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="warehouseCockpit()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alert -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center text-emerald-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Prominent Warehouse Status Banner -->
            @if($orderReservation->status === 'all_available')
                <div class="p-5 bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 border border-emerald-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-black text-base text-emerald-900 leading-tight">All Items Confirmed Available in Warehouse</h3>
                            <p class="text-xs text-emerald-700 mt-0.5">
                                Verified by <strong>{{ $orderReservation->confirmedBy?->name ?? 'Warehouse Manager' }}</strong>
                                @if($orderReservation->warehouse_confirmed_at)
                                    on {{ $orderReservation->warehouse_confirmed_at->format('M d, Y h:i A') }}
                                @endif
                                @if($orderReservation->warehouse_location)
                                    &bull; Location: <strong class="font-mono">{{ $orderReservation->warehouse_location }}</strong>
                                @endif
                            </p>
                        </div>
                    </div>
                    @if($orderReservation->document_id)
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('documents.create', ['source_document_id' => $orderReservation->document_id, 'type' => 'packing_list']) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-bold transition shadow-xs">
                                Create Packing List &rarr;
                            </a>
                            <a href="{{ route('documents.create', ['source_document_id' => $orderReservation->document_id, 'type' => 'invoice']) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-gray-50 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-bold transition shadow-2xs">
                                Create Invoice &rarr;
                            </a>
                        </div>
                    @endif
                </div>
            @elseif($orderReservation->status === 'has_shortage')
                <div class="p-5 bg-gradient-to-r from-amber-50 via-rose-50 to-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-black text-base text-amber-900 leading-tight">
                                Stock Shortage Recorded: {{ $orderReservation->short_items_count }} Item(s) Short (Total {{ number_format($orderReservation->total_short_qty, 2) }} Qty)
                            </h3>
                            <p class="text-xs text-amber-700 mt-0.5">
                                Please review short parts below or print the shortage sheet to notify procurement and warehouse managers.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('order-reservations.print-shortage', $orderReservation) }}" target="_blank"
                           class="inline-flex items-center px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Print Shortage Sheet
                        </a>
                    </div>
                </div>
            @else
                <div class="p-5 bg-gradient-to-r from-slate-50 to-gray-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-600 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-black text-base text-gray-800 leading-tight">Awaiting Warehouse Stock Verification</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Physical count pending. Click "Confirm All Available" if fully in stock, or enter available quantities below to record missing parts.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Info Summary Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-4 shadow-xs border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Customer / Company</span>
                    <span class="font-bold text-sm text-gray-900 mt-1 block">{{ $orderReservation->company_name ?: 'Not specified' }}</span>
                    @if($orderReservation->country)
                        <span class="text-xs text-gray-500 block">{{ $orderReservation->country }}</span>
                    @endif
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-xs border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Reservation Date</span>
                    <span class="font-bold text-sm text-gray-900 mt-1 block">{{ $orderReservation->reservation_date?->format('M d, Y') ?: '-' }}</span>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-xs border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Total Requested Items</span>
                    <div class="flex items-baseline space-x-2 mt-1">
                        <span class="font-black text-lg text-gray-900 font-mono">{{ number_format($orderReservation->total_requested_qty, 2) }}</span>
                        <span class="text-xs text-gray-500">across {{ $orderReservation->total_items_count }} items</span>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-xs border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Stock Breakdown</span>
                    <div class="flex items-center space-x-3 mt-1">
                        <div class="text-xs">
                            <span class="font-bold text-emerald-600 font-mono text-sm">{{ number_format($orderReservation->total_available_qty, 2) }}</span>
                            <span class="text-gray-400 block text-[10px]">Available</span>
                        </div>
                        <div class="text-xs border-l border-gray-200 pl-3">
                            <span class="font-black font-mono text-sm {{ $orderReservation->total_short_qty > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                {{ number_format($orderReservation->total_short_qty, 2) }}
                            </span>
                            <span class="text-gray-400 block text-[10px]">Short / Missing</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warehouse Stock Audit Form & Line Items Table -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <form action="{{ route('order-reservations.update-items', $orderReservation) }}" method="POST">
                    @csrf

                    <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-slate-50/50">
                        <div>
                            <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider flex items-center">
                                <svg class="w-4 h-4 me-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                Warehouse Physical Audit Table
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Modify Available Qty to record short parts. Short Qty will update in real time.
                            </p>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button type="button" @click="showAddModal = true" class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-200 text-indigo-700 rounded-xl text-xs font-bold shadow-2xs transition">
                                <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add Extra Missing Item
                            </button>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-sm transition">
                                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                Save Audit & Shortages
                            </button>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">#</th>
                                    <th class="px-4 py-3 text-left w-40">Item Code</th>
                                    <th class="px-4 py-3 text-left">Description</th>
                                    <th class="px-4 py-3 text-right w-24">Req Qty</th>
                                    <th class="px-4 py-3 text-right w-28">Avail Qty</th>
                                    <th class="px-4 py-3 text-right w-24">Short Qty</th>
                                    <th class="px-4 py-3 text-left w-36">Bin / Location</th>
                                    <th class="px-4 py-3 text-left">Shortage Reason / Notes</th>
                                    <th class="px-4 py-3 text-center w-28">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($orderReservation->items as $idx => $item)
                                    <tr class="hover:bg-slate-50/70 transition" x-data="{
                                        req: {{ (float) $item->requested_qty }},
                                        avail: {{ (float) $item->available_qty }},
                                        get short() {
                                            return Math.max(0, this.req - (parseFloat(this.avail) || 0)).toFixed(2);
                                        }
                                    }">
                                        <td class="px-4 py-3 text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                        <td class="px-4 py-3 font-mono font-bold text-gray-900">
                                            {{ $item->item_code }}
                                            @if(!$item->document_item_id)
                                                <span class="text-[9px] px-1 py-0.2 bg-purple-50 text-purple-700 rounded border border-purple-200 ml-1">Manual</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ $item->description ?: '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-gray-800">
                                            {{ number_format($item->requested_qty, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <input type="number" step="any" min="0" name="items[{{ $item->id }}][available_qty]"
                                                   x-model.number="avail"
                                                   class="w-24 text-right text-xs font-mono font-bold text-emerald-700 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1">
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-black">
                                            <span :class="short > 0 ? 'text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200' : 'text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200'"
                                                  x-text="short > 0 ? '-' + short : '0.00'">
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text" name="items[{{ $item->id }}][bin_location]" value="{{ $item->bin_location }}" placeholder="e.g. Bin 14"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text" name="items[{{ $item->id }}][shortage_reason]" value="{{ $item->shortage_reason }}" placeholder="Reason for shortage"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1">
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <template x-if="short > 0 && avail > 0">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                    Shortage
                                                </span>
                                            </template>
                                            <template x-if="short > 0 && avail == 0">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                                    Missing / Nil
                                                </span>
                                            </template>
                                            <template x-if="short == 0 && avail > 0">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                    Available
                                                </span>
                                            </template>
                                            <template x-if="short == 0 && avail == 0">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-50 text-slate-700 border border-slate-200">
                                                    Pending
                                                </span>
                                            </template>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Warehouse Meta Details & Save Button Footer -->
                    <div class="p-5 border-t border-gray-100 bg-slate-50/50 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full md:w-2/3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                    Warehouse Storage Location
                                </label>
                                <input type="text" name="warehouse_location" value="{{ old('warehouse_location', $orderReservation->warehouse_location) }}" placeholder="e.g. Main Warehouse, Shelf B-12"
                                       class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                    Warehouse Audit Notes
                                </label>
                                <input type="text" name="warehouse_notes" value="{{ old('warehouse_notes', $orderReservation->warehouse_notes) }}" placeholder="Notes on shortages, incoming deliveries..."
                                       class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 self-end md:self-auto">
                            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-sm hover:shadow transition">
                                Save Warehouse Audit / Shortage Updates
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal: Add Custom Missing Item -->
            <div x-show="showAddModal" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 max-w-lg w-full p-6 space-y-4" @click.outside="showAddModal = false">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider flex items-center">
                            <svg class="w-4 h-4 me-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Record Additional Missing Item / Short Part
                        </h3>
                        <button type="button" @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
                    </div>

                    <form action="{{ route('order-reservations.add-short-item', $orderReservation) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Item Code *</label>
                            <input type="text" name="item_code" required placeholder="e.g. 11041-002" class="w-full text-xs font-mono font-bold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Description</label>
                            <input type="text" name="description" placeholder="Item description" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Requested Qty *</label>
                                <input type="number" step="any" min="0.001" name="requested_qty" value="1" required class="w-full text-xs font-mono font-bold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Available Qty *</label>
                                <input type="number" step="any" min="0" name="available_qty" value="0" required class="w-full text-xs font-mono font-bold text-emerald-700 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Bin / Storage Location</label>
                            <input type="text" name="bin_location" placeholder="e.g. Bin 07" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Shortage Reason</label>
                            <input type="text" name="shortage_reason" placeholder="e.g. Supplier delivery delayed, damaged in transit" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="flex items-center justify-end space-x-2 pt-2 border-t border-gray-100">
                            <button type="button" @click="showAddModal = false" class="px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-gray-800">Cancel</button>
                            <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                Add Missing Part
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        function warehouseCockpit() {
            return {
                showAddModal: false
            };
        }
    </script>
</x-app-layout>
