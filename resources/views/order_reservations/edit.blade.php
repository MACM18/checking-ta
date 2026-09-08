<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('order-reservations.show', $orderReservation) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">&larr; Back to {{ $orderReservation->reserve_document_number }}</a>
                    <span class="text-gray-300">/</span>
                    <span class="text-xs font-mono font-bold text-gray-500">Edit</span>
                </div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight mt-1 flex items-center space-x-3">
                    <span>Edit Reservation: <span class="font-mono text-indigo-600">{{ $orderReservation->reserve_document_number }}</span></span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $orderReservation->status_badge_classes }}">
                        {{ $orderReservation->status_label }}
                    </span>
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Update document information, warehouse locations, and line items with real-time catalog autocompletion.
                </p>
            </div>
            @if($orderReservation->document_id)
                <div class="flex items-center">
                    <a href="{{ route('documents.show', $orderReservation->document_id) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                        <svg class="w-3.5 h-3.5 me-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Linked Document: {{ $orderReservation->document?->document_number }}
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8" x-data="editReserveForm()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('order-reservations.update', $orderReservation) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Basic Document Details Card -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-6">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-800 border-b border-gray-100 pb-3 flex items-center">
                        <svg class="w-4 h-4 me-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Reserve Document Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Reserve Document Number -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Reserve Document Number <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   name="reserve_document_number"
                                   required
                                   value="{{ old('reserve_document_number', $orderReservation->reserve_document_number) }}"
                                   class="w-full text-sm font-mono font-bold rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                            @error('reserve_document_number')
                                <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Company / Customer Name -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Client / Company Name
                            </label>
                            <input type="text"
                                   name="company_name"
                                   placeholder="Customer or Buyer name"
                                   value="{{ old('company_name', $orderReservation->company_name) }}"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Country -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Destination Country
                            </label>
                            <input type="text"
                                   name="country"
                                   placeholder="e.g. United Arab Emirates"
                                   value="{{ old('country', $orderReservation->country) }}"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Reservation Date -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Reservation Date
                            </label>
                            <input type="date"
                                   name="reservation_date"
                                   value="{{ old('reservation_date', $orderReservation->reservation_date ? $orderReservation->reservation_date->format('Y-m-d') : '') }}"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Warehouse Location -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Warehouse / Storage Location
                            </label>
                            <input type="text"
                                   name="warehouse_location"
                                   placeholder="e.g. Section C, Bin 12"
                                   value="{{ old('warehouse_location', $orderReservation->warehouse_location) }}"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- General Notes -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Internal Notes / References
                            </label>
                            <input type="text"
                                   name="notes"
                                   placeholder="e.g. Archived paper invoice #382"
                                   value="{{ old('notes', $orderReservation->notes) }}"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Items & Short Parts Entry Table -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-800 flex items-center">
                                <svg class="w-4 h-4 me-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                Line Items & Shortage Details
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Type item code or description for live catalog search. Shortages recalculate automatically.
                            </p>
                        </div>
                        <div class="text-xs text-gray-500 font-medium">
                            <span class="font-bold text-gray-800" x-text="items.length"></span> line item(s)
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-3 text-center w-12 text-gray-400">#</th>
                                    <th class="px-3 py-3 text-left w-44">Item Code *</th>
                                    <th class="px-3 py-3 text-left min-w-[340px]">Description & Specifications</th>
                                    <th class="px-3 py-3 text-right w-28">Req Qty</th>
                                    <th class="px-3 py-3 text-right w-28">Avail Qty</th>
                                    <th class="px-3 py-3 text-right w-28">Short Qty</th>
                                    <th class="px-3 py-3 text-left min-w-[200px]">Shortage Reason</th>
                                    <th class="sticky right-0 z-20 bg-gray-50 px-3 py-3 text-center w-12 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-slate-50/70 group">
                                        <input type="hidden" :name="`items[${index}][id]`" :value="item.id">
                                        <td class="px-3 py-2.5 text-center text-gray-400 font-mono text-[11px]" x-text="index + 1"></td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   :name="`items[${index}][item_code]`"
                                                   x-model="item.item_code"
                                                   :list="`edit-item-datalist-${index}`"
                                                   @input.debounce.250ms="onItemCodeInput(item, index)"
                                                   @change="lookupItem(item, true)"
                                                   required
                                                   placeholder="Item code"
                                                   autocomplete="off"
                                                   class="w-full text-xs font-mono font-bold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase py-2 px-2.5">
                                            <datalist :id="`edit-item-datalist-${index}`">
                                                <template x-for="sug in (itemSuggestions[index] || [])" :key="sug.item_code">
                                                    <option :value="sug.item_code" :label="`${sug.item_code} - ${sug.description}`"></option>
                                                </template>
                                            </datalist>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   :name="`items[${index}][description]`"
                                                   x-model="item.description"
                                                   :list="`edit-desc-datalist-${index}`"
                                                   @input.debounce.250ms="onDescriptionInput(item, index)"
                                                   placeholder="Enter full item description, brand, or specifications..."
                                                   autocomplete="off"
                                                   class="w-full text-xs sm:text-sm font-medium text-gray-900 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 bg-white shadow-2xs">
                                            <datalist :id="`edit-desc-datalist-${index}`">
                                                <template x-for="sug in (descSuggestions[index] || [])" :key="sug.id || sug.item_code">
                                                    <option :value="sug.description" :label="`${sug.item_code} - ${sug.description}`"></option>
                                                </template>
                                            </datalist>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number" step="any" min="0" :name="`items[${index}][requested_qty]`" x-model="item.requested_qty"
                                                   @focus="$event.target.select()"
                                                   autocomplete="off"
                                                   autocorrect="off"
                                                   autocapitalize="off"
                                                   spellcheck="false"
                                                   data-lpignore="true"
                                                   data-1p-ignore="true"
                                                   placeholder="0.00"
                                                   class="w-full text-xs text-right font-mono font-bold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-2.5 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number" step="any" min="0" :name="`items[${index}][available_qty]`" x-model="item.available_qty"
                                                   @focus="$event.target.select()"
                                                   autocomplete="off"
                                                   autocorrect="off"
                                                   autocapitalize="off"
                                                   spellcheck="false"
                                                   data-lpignore="true"
                                                   data-1p-ignore="true"
                                                   placeholder="0.00"
                                                   class="w-full text-xs text-right font-mono font-bold text-emerald-700 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-2.5 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-black">
                                            <span :class="parseFloat(shortQty(item)) > 0 ? 'text-rose-600 bg-rose-50 px-2.5 py-1 rounded-md border border-rose-200' : 'text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200'"
                                                  x-text="parseFloat(shortQty(item)) > 0 ? '-' + shortQty(item) : '0.00'"></span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`items[${index}][shortage_reason]`" x-model="item.shortage_reason" placeholder="Reason if short/missing (optional)"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-2.5">
                                        </td>
                                        <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-3 py-2 text-center shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                            <button type="button" @click="removeRow(index)" class="text-gray-400 hover:text-rose-600 p-1.5 rounded-lg hover:bg-rose-50 transition" title="Remove Row">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Items Action Bar for Easy Access -->
                    <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                        <button type="button" @click="addRow()" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Item
                        </button>

                        <div class="text-xs text-gray-500 font-medium flex items-center gap-2">
                            <span>Press <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded font-mono text-[10px]">Tab</kbd> to move between fields</span>
                        </div>
                    </div>
                </div>

                <!-- Form Submit Footer -->
                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('order-reservations.show', $orderReservation) }}" class="px-4 py-2.5 text-xs font-bold text-gray-600 hover:text-gray-800 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-sm hover:shadow transition">
                        Update Reservation & Shortage Details
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editReserveForm() {
            const rawItems = @js($initialItems ?? []);

            return {
                items: rawItems.length > 0 ? rawItems : [
                    { id: null, item_code: '', description: '', requested_qty: '', available_qty: '', shortage_reason: '' }
                ],
                itemSuggestions: {},
                descSuggestions: {},
                addRow() {
                    this.items.push({
                        id: null,
                        item_code: '',
                        description: '',
                        requested_qty: '',
                        available_qty: '',
                        shortage_reason: ''
                    });
                },
                async removeRow(index) {
                    const item = this.items[index];
                    const label = item?.item_code ? `item "${item.item_code}"` : 'this item';
                    const confirmed = window.systemConfirm
                        ? await window.systemConfirm({
                            title: 'Remove Item',
                            message: `Are you sure you want to remove ${label} from the reservation?`,
                            confirmText: 'Yes, Remove',
                            type: 'danger'
                        })
                        : confirm(`Are you sure you want to remove ${label}?`);

                    if (!confirmed) return;

                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    } else {
                        this.items[0] = { id: null, item_code: '', description: '', requested_qty: '', available_qty: '', shortage_reason: '' };
                    }
                    delete this.itemSuggestions[index];
                    delete this.descSuggestions[index];
                },
                shortQty(item) {
                    const req = (item.requested_qty !== '' && item.requested_qty !== null && !isNaN(item.requested_qty)) ? parseFloat(item.requested_qty) : 0;
                    const avail = (item.available_qty !== '' && item.available_qty !== null && !isNaN(item.available_qty)) ? parseFloat(item.available_qty) : 0;
                    return Math.max(0, req - avail).toFixed(2);
                },
                async onItemCodeInput(item, index) {
                    const q = item.item_code ? item.item_code.trim() : '';
                    if (q.length < 1) {
                        this.itemSuggestions[index] = [];
                        return;
                    }
                    try {
                        const res = await fetch(`/api/price-items/search?q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        this.itemSuggestions[index] = data.items || [];
                    } catch (e) {
                        console.error('Item suggestions fetch error', e);
                    }
                    this.lookupItem(item, false);
                },
                async onDescriptionInput(item, index) {
                    const q = item.description ? item.description.trim() : '';
                    if (q.length < 2) {
                        this.descSuggestions[index] = [];
                        return;
                    }
                    try {
                        const res = await fetch(`/api/price-items/search?q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        this.descSuggestions[index] = data.items || [];

                        const exact = (data.items || []).find(s => (s.description || '').toLowerCase() === q.toLowerCase());
                        if (exact && !item.item_code) {
                            item.item_code = exact.item_code;
                        }
                    } catch (e) {
                        console.error('Description suggestions error', e);
                    }
                },
                async lookupItem(item, force = false) {
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
                        console.error('Item lookup error', e);
                    }
                }
            };
        }
    </script>
</x-app-layout>
