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

                <a href="{{ route('order-reservations.edit', $orderReservation) }}" class="inline-flex items-center px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 rounded-xl text-xs font-bold shadow-2xs transition">
                    <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit Reservation
                </a>

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
                <form action="{{ route('order-reservations.confirm-all', $orderReservation) }}"
                      method="POST"
                      x-data="confirmAllHandler()"
                      @submit.prevent="handleConfirm()">
                    @csrf
                    <button type="submit" :disabled="isLoading" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-60 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        <svg x-show="!isLoading" class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <svg x-show="isLoading" class="w-4 h-4 me-1.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="isLoading ? 'Confirming...' : 'Confirm All Available (Warehouse)'">Confirm All Available (Warehouse)</span>
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="warehouseCockpit()"
         @reservation-optimistic-confirm-all.window="optimisticConfirmAll()"
         @reservation-confirm-all-success.window="onConfirmSuccess($event.detail)"
         @reservation-confirm-all-failed.window="rollbackConfirmAll()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alert -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center text-emerald-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Prominent Warehouse Status Banner -->
            <div x-show="status === 'all_available'" class="p-5 bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 border border-emerald-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs" {!! $orderReservation->status !== 'all_available' ? 'style="display: none;"' : '' !!}>
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-emerald-900 leading-tight">All Items Confirmed Available in Warehouse</h3>
                        <p class="text-xs text-emerald-700 mt-0.5">
                            Verified by <strong x-text="confirmedBy">{{ $orderReservation->confirmedBy?->name ?? 'Warehouse Manager' }}</strong>
                            <span x-show="confirmedAt">on <span x-text="confirmedAt">{{ $orderReservation->warehouse_confirmed_at?->format('M d, Y h:i A') }}</span></span>
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

            <div x-show="status === 'has_shortage'" class="p-5 bg-gradient-to-r from-amber-50 via-rose-50 to-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs" {!! $orderReservation->status !== 'has_shortage' ? 'style="display: none;"' : '' !!}>
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-amber-900 leading-tight">
                            Stock Shortage Recorded: <span x-text="shortItemsCount">{{ $orderReservation->short_items_count }}</span> Item(s) Short (Total <span x-text="formatQty(totalShortQty)">{{ number_format($orderReservation->total_short_qty, 2) }}</span> Qty)
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

            <div x-show="status !== 'all_available' && status !== 'has_shortage'" class="p-5 bg-gradient-to-r from-slate-50 to-gray-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs" {!! in_array($orderReservation->status, ['all_available', 'has_shortage']) ? 'style="display: none;"' : '' !!}>
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
                            <span class="font-bold text-emerald-600 font-mono text-sm" x-text="formatQty(totalAvailableQty)">{{ number_format($orderReservation->total_available_qty, 2) }}</span>
                            <span class="text-gray-400 block text-[10px]">Available</span>
                        </div>
                        <div class="text-xs border-l border-gray-200 pl-3">
                            <span class="font-black font-mono text-sm"
                                  :class="totalShortQty > 0 ? 'text-rose-600' : 'text-gray-400'"
                                  x-text="formatQty(totalShortQty)">
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
                            <button type="button" @click="addNewRow()" class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-xl text-xs font-bold shadow-2xs transition">
                                <svg class="w-3.5 h-3.5 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
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
                                    <th class="px-3 py-2.5 text-center w-12">#</th>
                                    <th class="px-3 py-2.5 text-left w-44">Item Code</th>
                                    <th class="px-3 py-2.5 text-left min-w-[200px]">Description</th>
                                    <th class="px-3 py-2.5 text-right w-24">Req Qty</th>
                                    <th class="px-3 py-2.5 text-right w-28">Avail Qty</th>
                                    <th class="px-3 py-2.5 text-right w-24">Short Qty</th>
                                    <th class="px-3 py-2.5 text-left w-32">Bin / Location</th>
                                    <th class="px-3 py-2.5 text-left w-32">Supplier / Inv #</th>
                                    <th class="px-3 py-2.5 text-left min-w-[180px]">Shortage Reason / Notes</th>
                                    <th class="sticky right-0 z-20 bg-gray-50 px-3 py-2.5 text-center w-36 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200">Status / Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($orderReservation->items as $idx => $item)
                                    <tr class="hover:bg-slate-50/70 transition group" x-data="{
                                        req: {{ (float) $item->requested_qty }},
                                        avail: '{{ (float) $item->available_qty }}',
                                        prevAvail: '{{ (float) $item->available_qty }}',
                                        get short() {
                                            const r = parseFloat(this.req) || 0;
                                            const a = (this.avail !== '' && this.avail !== null) ? parseFloat(this.avail) : 0;
                                            return Math.max(0, r - a).toFixed(2);
                                        }
                                    }"
                                    @reservation-optimistic-confirm-all.window="prevAvail = avail; avail = req;"
                                    @reservation-confirm-all-failed.window="avail = prevAvail;">
                                        <td class="px-3 py-2 text-gray-400 font-mono text-center">{{ $idx + 1 }}</td>
                                        <td class="px-3 py-2 font-mono font-bold text-gray-900 whitespace-nowrap">
                                            {{ $item->item_code }}
                                            @if(!$item->document_item_id)
                                                <span class="text-[9px] px-1.5 py-0.5 bg-purple-50 text-purple-700 rounded border border-purple-200 ml-1 font-sans font-medium">Manual</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   name="items[{{ $item->id }}][description]"
                                                   value="{{ $item->description }}"
                                                   placeholder="Description"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-2.5">
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-bold text-gray-800">
                                            {{ number_format($item->requested_qty, 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number" step="any" min="0" name="items[{{ $item->id }}][available_qty]"
                                                   x-model="avail"
                                                   @focus="$event.target.select()"
                                                   placeholder="0"
                                                   class="w-full text-right text-xs font-mono font-bold text-emerald-700 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-2.5 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-black">
                                            <span :class="short > 0 ? 'text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200' : 'text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200'"
                                                  x-text="short > 0 ? '-' + short : '0.00'">
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" name="items[{{ $item->id }}][bin_location]" value="{{ $item->bin_location }}" placeholder="e.g. Bin 14"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-2.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" name="items[{{ $item->id }}][supplier_invoice_no]" value="{{ $item->supplier_invoice_no }}" placeholder="e.g. 26FZ12"
                                                   class="w-full text-xs font-mono rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-2.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" name="items[{{ $item->id }}][shortage_reason]" value="{{ $item->shortage_reason }}" placeholder="Reason for shortage"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-2.5">
                                        </td>
                                        <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-3 py-2 text-center whitespace-nowrap shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                            <div class="flex items-center justify-center space-x-2">
                                                <template x-if="short > 0 && parseFloat(avail) > 0">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                        Shortage
                                                    </span>
                                                </template>
                                                <template x-if="short > 0 && (parseFloat(avail) === 0 || avail === '' || avail === null)">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                                        Missing / Nil
                                                    </span>
                                                </template>
                                                <template x-if="short == 0 && parseFloat(avail) > 0">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                        Available
                                                    </span>
                                                </template>
                                                <template x-if="short == 0 && (parseFloat(avail) === 0 || avail === '' || avail === null)">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-50 text-slate-700 border border-slate-200">
                                                        Pending
                                                    </span>
                                                </template>

                                                <button type="button"
                                                        @click="deleteItem({{ $item->id }}, '{{ addslashes($item->item_code) }}')"
                                                        class="text-gray-400 hover:text-rose-600 p-1 transition rounded hover:bg-rose-50"
                                                        title="Delete item {{ $item->item_code }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Inline Added Missing Items -->
                                <template x-for="(newItem, nIdx) in newItems" :key="'new-' + nIdx">
                                    <tr class="bg-indigo-50/30 hover:bg-indigo-50/50 transition group border-l-4 border-l-indigo-500">
                                        <td class="px-3 py-2 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700">New</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   :name="`new_items[${nIdx}][item_code]`"
                                                   x-model="newItem.item_code"
                                                   :list="`new-item-datalist-${nIdx}`"
                                                   @input.debounce.250ms="onNewItemCodeInput(newItem, nIdx)"
                                                   @change="lookupNewItem(newItem, nIdx, true)"
                                                   placeholder="Item code *"
                                                   autocomplete="off"
                                                   required
                                                   class="w-full text-xs font-mono font-bold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase bg-white py-1.5 px-2.5">
                                            <datalist :id="`new-item-datalist-${nIdx}`">
                                                <template x-for="sug in (newItemsSuggestions[nIdx] || [])" :key="sug.item_code">
                                                    <option :value="sug.item_code" :label="`${sug.item_code} - ${sug.description}`"></option>
                                                </template>
                                            </datalist>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   :name="`new_items[${nIdx}][description]`"
                                                   x-model="newItem.description"
                                                   :list="`new-desc-datalist-${nIdx}`"
                                                   @input.debounce.250ms="onNewDescInput(newItem, nIdx)"
                                                   placeholder="Description"
                                                   autocomplete="off"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-1.5 px-2.5">
                                            <datalist :id="`new-desc-datalist-${nIdx}`">
                                                <template x-for="sug in (newDescSuggestions[nIdx] || [])" :key="sug.id || sug.item_code">
                                                    <option :value="sug.description" :label="`${sug.item_code} - ${sug.description}`"></option>
                                                </template>
                                            </datalist>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number" step="any" min="0"
                                                   :name="`new_items[${nIdx}][requested_qty]`"
                                                   x-model="newItem.requested_qty"
                                                   @focus="$event.target.select()"
                                                   placeholder="Qty"
                                                   class="w-full text-right text-xs font-mono font-bold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-1.5 px-2.5 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number" step="any" min="0"
                                                   :name="`new_items[${nIdx}][available_qty]`"
                                                   x-model="newItem.available_qty"
                                                   @focus="$event.target.select()"
                                                   placeholder="0"
                                                   class="w-full text-right text-xs font-mono font-bold text-emerald-700 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-1.5 px-2.5 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-black">
                                            <span :class="parseFloat(newShortQty(newItem)) > 0 ? 'text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200' : 'text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200'"
                                                  x-text="parseFloat(newShortQty(newItem)) > 0 ? '-' + newShortQty(newItem) : '0.00'">
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   :name="`new_items[${nIdx}][bin_location]`"
                                                   x-model="newItem.bin_location"
                                                   placeholder="e.g. Bin 14"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-1.5 px-2.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   :name="`new_items[${nIdx}][supplier_invoice_no]`"
                                                   x-model="newItem.supplier_invoice_no"
                                                   placeholder="e.g. 26FZ12"
                                                   class="w-full text-xs font-mono rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-1.5 px-2.5">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   :name="`new_items[${nIdx}][shortage_reason]`"
                                                   x-model="newItem.shortage_reason"
                                                   placeholder="Shortage reason"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-1.5 px-2.5">
                                        </td>
                                        <td class="sticky right-0 z-10 bg-white group-hover:bg-indigo-50/40 transition px-3 py-2 text-center whitespace-nowrap shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                            <div class="flex items-center justify-center space-x-1.5">
                                                <button type="button"
                                                        @click="quickSaveNewItem(newItem, nIdx)"
                                                        :disabled="newItem.isSaving || !newItem.item_code"
                                                        class="inline-flex items-center px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-50 text-white rounded-lg text-[11px] font-bold shadow-2xs transition"
                                                        title="Quick Save this item now">
                                                    <template x-if="!newItem.isSaving">
                                                        <span class="flex items-center">
                                                            <svg class="w-3 h-3 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Save
                                                        </span>
                                                    </template>
                                                    <template x-if="newItem.isSaving">
                                                        <span>...</span>
                                                    </template>
                                                </button>
                                                <button type="button"
                                                        @click="removeNewRow(nIdx)"
                                                        class="text-gray-400 hover:text-rose-600 p-1 transition rounded hover:bg-rose-50"
                                                        title="Remove this draft row">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Inline Add Row Action Bar -->
                    <div class="px-5 py-3 bg-slate-50/70 border-t border-gray-100 flex items-center justify-between">
                        <button type="button" @click="addNewRow()" class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-xl text-xs font-bold shadow-2xs transition">
                            <svg class="w-3.5 h-3.5 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Extra Missing Item (Inline)
                        </button>
                        <span class="text-[11px] text-gray-500" x-show="newItems.length > 0">
                            <span class="font-bold text-amber-600" x-text="newItems.length"></span> new item(s) pending save. Click "Save" on the row or "Save Warehouse Audit" below.
                        </span>
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

        </div>
    </div>

    <script>
        function confirmAllHandler() {
            return {
                isLoading: false,
                async handleConfirm() {
                    if (this.isLoading) return;

                    const confirmed = window.systemConfirm
                        ? await window.systemConfirm({
                            title: 'Verify All Items Available',
                            message: 'Confirm that ALL items are physically verified and 100% available in warehouse? This will mark all items available with zero shortage.',
                            confirmText: 'Yes, Confirm All',
                            type: 'primary'
                        })
                        : confirm('Confirm that ALL items are physically verified and 100% available in warehouse?');

                    if (!confirmed) return;

                    this.isLoading = true;
                    // Trigger optimistic instant UI update across page
                    window.dispatchEvent(new CustomEvent('reservation-optimistic-confirm-all'));
                    window.showToast?.('Marking all items available in warehouse...', 'info', 2000);

                    try {
                        const response = await fetch('{{ route('order-reservations.confirm-all', $orderReservation) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Failed to confirm items.');
                        }

                        window.dispatchEvent(new CustomEvent('reservation-confirm-all-success', { detail: data }));
                        window.showToast?.(data.message || 'Warehouse confirmed! All items verified.', 'success');
                    } catch (error) {
                        window.dispatchEvent(new CustomEvent('reservation-confirm-all-failed'));
                        window.showToast?.(error.message || 'Failed to update warehouse. Reverted.', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                }
            };
        }

        function warehouseCockpit() {
            return {
                newItems: [],
                newItemsSuggestions: {},
                newDescSuggestions: {},
                status: @js($orderReservation->status),
                prevStatus: @js($orderReservation->status),
                confirmedBy: @js($orderReservation->confirmedBy?->name ?? 'Warehouse Manager'),
                confirmedAt: @js($orderReservation->warehouse_confirmed_at ? $orderReservation->warehouse_confirmed_at->format('M d, Y h:i A') : ''),
                totalRequestedQty: {{ (float) $orderReservation->total_requested_qty }},
                totalAvailableQty: {{ (float) $orderReservation->total_available_qty }},
                prevTotalAvailableQty: {{ (float) $orderReservation->total_available_qty }},
                totalShortQty: {{ (float) $orderReservation->total_short_qty }},
                prevTotalShortQty: {{ (float) $orderReservation->total_short_qty }},
                shortItemsCount: {{ (int) $orderReservation->short_items_count }},
                prevShortItemsCount: {{ (int) $orderReservation->short_items_count }},

                addNewRow() {
                    this.newItems.push({
                        item_code: '',
                        description: '',
                        requested_qty: '',
                        available_qty: '',
                        bin_location: '',
                        supplier_invoice_no: '',
                        shortage_reason: '',
                        remarks: '',
                        isSaving: false
                    });
                },

                async removeNewRow(idx) {
                    const item = this.newItems[idx];
                    if (item && item.item_code && item.item_code.trim()) {
                        const confirmed = window.systemConfirm
                            ? await window.systemConfirm({
                                title: 'Discard Item Row',
                                message: `Are you sure you want to remove the new row for "${item.item_code}"?`,
                                confirmText: 'Yes, Remove',
                                type: 'danger'
                            })
                            : confirm(`Remove row for "${item.item_code}"?`);

                        if (!confirmed) return;
                    }

                    this.newItems.splice(idx, 1);
                    delete this.newItemsSuggestions[idx];
                    delete this.newDescSuggestions[idx];
                },

                async deleteItem(itemId, itemCode) {
                    const confirmed = window.systemConfirm
                        ? await window.systemConfirm({
                            title: 'Remove Item from Reservation',
                            message: `Are you sure you want to remove item "${itemCode}" from this reservation? This cannot be undone.`,
                            confirmText: 'Yes, Remove Item',
                            type: 'danger'
                        })
                        : confirm(`Are you sure you want to remove item "${itemCode}" from this reservation?`);

                    if (!confirmed) return;

                    window.showToast?.(`Removing item ${itemCode}...`, 'info', 1500);

                    try {
                        const response = await fetch(`/order-reservations/{{ $orderReservation->id }}/items/${itemId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Failed to remove item.');
                        }

                        window.showToast?.(data.message || `Item ${itemCode} removed.`, 'success');
                        setTimeout(() => window.location.reload(), 300);
                    } catch (e) {
                        window.showToast?.(e.message || 'Error removing item.', 'error');
                    }
                },

                newShortQty(item) {
                    const req = (item.requested_qty !== '' && item.requested_qty !== null && !isNaN(item.requested_qty)) ? parseFloat(item.requested_qty) : 0;
                    const avail = (item.available_qty !== '' && item.available_qty !== null && !isNaN(item.available_qty)) ? parseFloat(item.available_qty) : 0;
                    return Math.max(0, req - avail).toFixed(2);
                },

                async onNewItemCodeInput(item, idx) {
                    const q = item.item_code ? item.item_code.trim() : '';
                    if (q.length < 1) {
                        this.newItemsSuggestions[idx] = [];
                        return;
                    }
                    try {
                        const res = await fetch(`/api/price-items/search?q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        this.newItemsSuggestions[idx] = data.items || [];
                    } catch (e) {
                        console.error('New item suggestions error', e);
                    }
                    this.lookupNewItem(item, idx, false);
                },

                async onNewDescInput(item, idx) {
                    const q = item.description ? item.description.trim() : '';
                    if (q.length < 2) {
                        this.newDescSuggestions[idx] = [];
                        return;
                    }
                    try {
                        const res = await fetch(`/api/price-items/search?q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        this.newDescSuggestions[idx] = data.items || [];

                        const exact = (data.items || []).find(s => (s.description || '').toLowerCase() === q.toLowerCase());
                        if (exact && !item.item_code) {
                            item.item_code = exact.item_code;
                        }
                    } catch (e) {
                        console.error('New desc suggestions error', e);
                    }
                },

                async lookupNewItem(item, idx, force = false) {
                    const code = item.item_code ? item.item_code.trim() : '';
                    if (!code) return;
                    try {
                        const res = await fetch(`/api/price-items/lookup?item_code=${encodeURIComponent(code)}`);
                        const data = await res.json();
                        if (data.found && data.description) {
                            if (force || !item.description || item.description.trim() === '') {
                                item.description = data.description;
                            }
                        }
                    } catch (e) {
                        console.error('New item lookup error', e);
                    }
                },

                async quickSaveNewItem(item, idx) {
                    if (!item.item_code || !item.item_code.trim()) {
                        window.showToast?.('Please enter an item code before saving.', 'error');
                        return;
                    }
                    item.isSaving = true;
                    try {
                        const response = await fetch('{{ route('order-reservations.add-short-item', $orderReservation) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                item_code: item.item_code.trim(),
                                description: item.description || '',
                                requested_qty: (item.requested_qty !== '' && item.requested_qty !== null && !isNaN(item.requested_qty)) ? parseFloat(item.requested_qty) : 1,
                                available_qty: (item.available_qty !== '' && item.available_qty !== null && !isNaN(item.available_qty)) ? parseFloat(item.available_qty) : 0,
                                bin_location: item.bin_location || '',
                                supplier_invoice_no: item.supplier_invoice_no || '',
                                shortage_reason: item.shortage_reason || 'Manual missing item recorded',
                                remarks: item.remarks || ''
                            })
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Failed to save item.');
                        }

                        window.showToast?.(data.message || 'Missing item recorded successfully.', 'success');
                        setTimeout(() => window.location.reload(), 300);
                    } catch (e) {
                        window.showToast?.(e.message || 'Error saving missing item.', 'error');
                        item.isSaving = false;
                    }
                },

                formatQty(val) {
                    const num = parseFloat(val) || 0;
                    return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                optimisticConfirmAll() {
                    this.prevStatus = this.status;
                    this.prevTotalAvailableQty = this.totalAvailableQty;
                    this.prevTotalShortQty = this.totalShortQty;
                    this.prevShortItemsCount = this.shortItemsCount;

                    this.status = 'all_available';
                    this.totalAvailableQty = this.totalRequestedQty;
                    this.totalShortQty = 0;
                    this.shortItemsCount = 0;
                    this.confirmedAt = 'Just now';
                },

                onConfirmSuccess(detail) {
                    if (detail && detail.warehouse_confirmed_at) {
                        this.confirmedAt = detail.warehouse_confirmed_at;
                    }
                    if (detail && detail.warehouse_confirmed_by) {
                        this.confirmedBy = detail.warehouse_confirmed_by;
                    }
                },

                rollbackConfirmAll() {
                    this.status = this.prevStatus;
                    this.totalAvailableQty = this.prevTotalAvailableQty;
                    this.totalShortQty = this.prevTotalShortQty;
                    this.shortItemsCount = this.prevShortItemsCount;
                }
            };
        }
    </script>
</x-app-layout>
