<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('price-tracker.index') }}" class="p-2 bg-white hover:bg-gray-100 border border-gray-200 text-gray-600 hover:text-gray-900 rounded-xl transition shadow-2xs" title="Back to All Items">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-mono font-black text-2xl text-gray-900 leading-tight">
                            {{ $item->item_code }}
                        </h2>
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ $item->item_code }}'); window.showToast?.('Item code copied!', 'success');"
                                class="text-gray-400 hover:text-indigo-600 p-1 rounded transition"
                                title="Copy Item Code">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $item->description ?: 'No description recorded in catalogue' }}
                    </p>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('price-tracker.index', ['q' => $item->item_code]) }}" class="inline-flex items-center px-3.5 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-bold shadow-2xs transition">
                    <svg class="w-4 h-4 me-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Find in Master List
                </a>

                <form action="{{ route('price-tracker.destroy', $item) }}"
                      method="POST"
                      class="inline-block"
                      data-confirm="Are you sure you want to delete item '{{ $item->item_code }}' and all {{ $item->prices->count() }} price points?"
                      data-confirm-title="Delete Item"
                      data-confirm-button="Delete Item"
                      data-confirm-type="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-3.5 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 rounded-xl text-xs font-bold shadow-2xs transition">
                        <svg class="w-4 h-4 me-1.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Delete Item
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Success Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center text-emerald-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 me-2 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- KPI Overview Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <!-- Net Weight Card with Inline Edit -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5" x-data="{ editing: false, weight: '{{ $item->net_weight !== null ? number_format($item->net_weight, 3, '.', '') : '' }}', saving: false }">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Net Weight</span>
                        <button type="button" x-show="!editing" @click="editing = true; $nextTick(() => $refs.wtInput.focus())" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold transition">
                            Edit
                        </button>
                    </div>
                    
                    <div x-show="!editing" class="mt-1">
                        <div class="text-2xl font-black font-mono text-gray-900" x-text="weight ? `${parseFloat(weight).toFixed(3)} kg` : '—'">{{ $item->net_weight !== null ? number_format($item->net_weight, 3, '.', '').' kg' : '—' }}</div>
                        <div class="text-[11px] text-gray-400 mt-1">Default unit weight for packing lists</div>
                    </div>

                    <form x-show="editing" x-cloak @submit.prevent="
                        saving = true;
                        fetch('{{ route('price-tracker.items.update-weight', $item) }}', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ net_weight: weight })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                weight = data.net_weight !== null ? String(data.net_weight) : '';
                                editing = false;
                                window.showToast?.('Net weight updated successfully', 'success');
                            }
                        })
                        .finally(() => saving = false)
                    " class="mt-2 space-y-2">
                        <div class="flex items-center space-x-1.5">
                            <input type="number" step="0.001" min="0" x-ref="wtInput" x-model="weight" placeholder="0.000" class="w-full text-xs font-mono rounded-lg border-indigo-400 py-1.5 px-2.5 focus:ring-indigo-500">
                            <span class="text-xs font-bold text-gray-500">kg</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <button type="submit" :disabled="saving" class="flex-1 px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition">
                                <span x-show="!saving">Save</span>
                                <span x-show="saving">...</span>
                            </button>
                            <button type="button" @click="editing = false" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Total Recorded Tiers -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Recorded Price Tiers</div>
                    <div class="text-2xl font-black font-mono text-indigo-600 mt-1">{{ $item->prices->count() }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Across {{ count($pricesByList) }} Price List(s)</div>
                </div>

                <!-- Document Usage -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Document Usage</div>
                    <div class="text-2xl font-black font-mono text-emerald-600 mt-1">{{ $recentDocumentItems->count() }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Invoices & Packing Lists</div>
                </div>

                <!-- Warehouse Reservations -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Reservations</div>
                    <div class="text-2xl font-black font-mono text-amber-600 mt-1">{{ $recentReservationItems->count() }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Warehouse reservation audits</div>
                </div>
            </div>

            <!-- Multi-Tier Pricing Breakdown -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-base text-gray-900 flex items-center gap-2">
                            <span>🏷️</span>
                            <span>Recorded Multi-Tier Pricing</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Pre-configured unit prices according to client discount tiers and price lists.
                        </p>
                    </div>
                    <a href="{{ route("price-tracker.items.edit", $item) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition">
                        <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Edit item and prices
                    </a>
                </div>

                @if($item->prices->isEmpty())
                    <div class="p-10 text-center text-gray-400">
                        <p class="text-sm font-medium">No price points currently recorded for this item code.</p>
                        <p class="text-xs mt-1">Import prices from Excel or add them via the Price Tracker Importer.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($pricesByList as $listName => $prices)
                            <div class="p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                        <span>Price List: {{ $listName ?: 'Standard' }}</span>
                                    </h4>
                                    <span class="text-[11px] font-mono text-gray-400">{{ count($prices) }} tier(s)</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                    @foreach($prices as $price)
                                        <div class="p-3.5 rounded-xl border {{ str_starts_with($price->price_label, 'USD') ? 'bg-blue-50/50 border-blue-200' : 'bg-indigo-50/40 border-indigo-200' }} flex flex-col justify-between">
                                            <div class="flex items-center justify-between">
                                                <span class="font-mono font-bold text-xs {{ str_starts_with($price->price_label, 'USD') ? 'text-blue-900' : 'text-indigo-900' }}">
                                                    {{ $price->price_label }}
                                                </span>
                                                <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded {{ str_starts_with($price->price_label, 'USD') ? 'bg-blue-100 text-blue-800' : 'bg-indigo-100 text-indigo-800' }}">
                                                    {{ $price->currency }}
                                                </span>
                                            </div>
                                            <div class="mt-2.5">
                                                <span class="text-lg font-black font-mono text-gray-900">
                                                    {{ $price->currency }} {{ number_format($price->price, 2) }}
                                                </span>
                                            </div>
                                            <div class="text-[10px] text-gray-400 mt-1">
                                                Updated {{ $price->updated_at ? $price->updated_at->format('M d, Y') : 'N/A' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Recent Documents Usage -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="font-bold text-base text-gray-900 flex items-center gap-2">
                        <span>📄</span>
                        <span>Recent Documents Featuring This Item</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Historical tracking of documents, invoices, and packing lists where this item code was billed or shipped.
                    </p>
                </div>

                @if($recentDocumentItems->isEmpty())
                    <div class="p-8 text-center text-gray-400 text-xs">
                        No recent document transactions found for item code "{{ $item->item_code }}".
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-3 text-left">Document #</th>
                                    <th class="px-5 py-3 text-left">Type</th>
                                    <th class="px-5 py-3 text-left">Buyer / Company</th>
                                    <th class="px-5 py-3 text-right">Unit Price</th>
                                    <th class="px-5 py-3 text-right">Quantity</th>
                                    <th class="px-5 py-3 text-right">Total Amount</th>
                                    <th class="px-5 py-3 text-center w-24">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($recentDocumentItems as $docItem)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-5 py-3 font-mono font-bold text-indigo-600">
                                            @if($docItem->document)
                                                <a href="{{ route('documents.show', $docItem->document) }}" class="hover:underline">
                                                    {{ $docItem->document->document_number }}
                                                </a>
                                            @else
                                                #{{ $docItem->document_id }}
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            @if($docItem->document)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">
                                                    {{ $docItem->document->formatted_type ?? $docItem->document->document_type }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-gray-700 font-medium">
                                            {{ $docItem->document?->company_name ?: '—' }}
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono font-bold text-gray-900">
                                            {{ $docItem->document?->currency ?? 'USD' }} {{ number_format($docItem->unit_price, 2) }}
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono font-bold text-gray-700">
                                            {{ number_format($docItem->unit_amount, 2) }}
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono font-black text-gray-900">
                                            {{ $docItem->document?->currency ?? 'USD' }} {{ number_format($docItem->total_amount, 2) }}
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            @if($docItem->document)
                                                <a href="{{ route('documents.show', $docItem->document) }}" class="inline-flex items-center px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition">
                                                    View Doc
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Recent Warehouse Reservations -->
            @if($recentReservationItems->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-bold text-base text-gray-900 flex items-center gap-2">
                            <span>📦</span>
                            <span>Warehouse Reservations Featuring This Item</span>
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-3 text-left">Reservation #</th>
                                    <th class="px-5 py-3 text-left">Company</th>
                                    <th class="px-5 py-3 text-left">Status</th>
                                    <th class="px-5 py-3 text-right">Req Qty</th>
                                    <th class="px-5 py-3 text-right">Avail Qty</th>
                                    <th class="px-5 py-3 text-right">Short Qty</th>
                                    <th class="px-5 py-3 text-center w-28">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($recentReservationItems as $resItem)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-5 py-3 font-mono font-bold text-indigo-600">
                                            @if($resItem->orderReservation)
                                                <a href="{{ route('order-reservations.show', $resItem->orderReservation) }}" class="hover:underline">
                                                    {{ $resItem->orderReservation->reserve_document_number }}
                                                </a>
                                            @else
                                                #{{ $resItem->order_reservation_id }}
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-gray-700 font-medium">
                                            {{ $resItem->orderReservation?->company_name ?: '—' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            @if($resItem->orderReservation)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $resItem->orderReservation->status_badge_classes }}">
                                                    {{ $resItem->orderReservation->status_label }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono font-bold text-gray-700">
                                            {{ number_format($resItem->requested_qty, 2) }}
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono font-bold text-emerald-700">
                                            {{ number_format($resItem->available_qty, 2) }}
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono font-bold {{ (float) $resItem->short_qty > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                            {{ (float) $resItem->short_qty > 0 ? '-'.number_format($resItem->short_qty, 2) : '0.00' }}
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            @if($resItem->orderReservation)
                                                <a href="{{ route('order-reservations.show', $resItem->orderReservation) }}" class="inline-flex items-center px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-xs font-bold transition">
                                                    Audit
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
