<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('order-reservations.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">&larr; Back to Order Reservations</a>
                </div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight mt-1">
                    Record Old / External Reserve (R) Document
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Record missing items and stock shortages for historical, paper, or external Reserve orders not created directly in this system.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="legacyReserveForm()">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('order-reservations.store') }}" method="POST" class="space-y-6">
                @csrf

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
                            <input type="text" name="reserve_document_number" required placeholder="e.g. E24810R or R-9821" value="{{ old('reserve_document_number') }}"
                                   class="w-full text-sm font-mono font-bold rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                            <span class="text-[11px] text-gray-400 mt-1 block">Usually ends with "R"</span>
                            @error('reserve_document_number')
                                <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Company / Customer Name -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Client / Company Name
                            </label>
                            <input type="text" name="company_name" placeholder="Customer or Buyer name" value="{{ old('company_name') }}"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Country -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Destination Country
                            </label>
                            <input type="text" name="country" placeholder="e.g. United Arab Emirates" value="{{ old('country') }}"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Reservation Date -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Reservation Date
                            </label>
                            <input type="date" name="reservation_date" value="{{ old('reservation_date', date('Y-m-d')) }}"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Warehouse Location -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Warehouse / Storage Location
                            </label>
                            <input type="text" name="warehouse_location" placeholder="e.g. Section C, Bin 12" value="{{ old('warehouse_location') }}"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- General Notes -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Internal Notes / References
                            </label>
                            <input type="text" name="notes" placeholder="e.g. Archived paper invoice #382" value="{{ old('notes') }}"
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
                                Enter requested and available quantities. Shortage (missing qty) will calculate automatically.
                            </p>
                        </div>
                        <button type="button" @click="addRow()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition">
                            <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Item
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-2.5 text-left w-36">Item Code *</th>
                                    <th class="px-3 py-2.5 text-left">Description</th>
                                    <th class="px-3 py-2.5 text-right w-24">Req Qty</th>
                                    <th class="px-3 py-2.5 text-right w-24">Avail Qty</th>
                                    <th class="px-3 py-2.5 text-right w-24">Short Qty</th>
                                    <th class="px-3 py-2.5 text-left w-32">Bin Location</th>
                                    <th class="px-3 py-2.5 text-left w-32">Supplier / Inv #</th>
                                    <th class="px-3 py-2.5 text-left">Shortage Reason</th>
                                    <th class="sticky right-0 z-20 bg-gray-50 px-3 py-2.5 text-center w-12 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-slate-50/70 group">
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`items[${index}][item_code]`" x-model="item.item_code" required placeholder="Item code"
                                                   class="w-full text-xs font-mono font-bold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`items[${index}][description]`" x-model="item.description" placeholder="Description"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number" step="any" min="0" :name="`items[${index}][requested_qty]`" x-model.number="item.requested_qty"
                                                   class="w-full text-xs text-right font-mono font-bold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number" step="any" min="0" :name="`items[${index}][available_qty]`" x-model.number="item.available_qty"
                                                   class="w-full text-xs text-right font-mono font-bold text-emerald-700 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-black">
                                            <span :class="shortQty(item) > 0 ? 'text-rose-600 bg-rose-50 px-2 py-0.5 rounded' : 'text-gray-400'" x-text="shortQty(item)"></span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`items[${index}][bin_location]`" x-model="item.bin_location" placeholder="e.g. Bin 04"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`items[${index}][supplier_invoice_no]`" x-model="item.supplier_invoice_no" placeholder="e.g. 26FZ12"
                                                   class="w-full text-xs font-mono rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`items[${index}][shortage_reason]`" x-model="item.shortage_reason" placeholder="Reason if short/missing"
                                                   class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                        <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-3 py-2 text-center shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                            <button type="button" @click="removeRow(index)" class="text-gray-400 hover:text-rose-600 p-1" title="Remove Row">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form Submit Footer -->
                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('order-reservations.index') }}" class="px-4 py-2.5 text-xs font-bold text-gray-600 hover:text-gray-800 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-sm hover:shadow transition">
                        Save Reservation & Shortage Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function legacyReserveForm() {
            return {
                items: [
                    { item_code: '', description: '', requested_qty: 1, available_qty: 0, bin_location: '', shortage_reason: '' }
                ],
                addRow() {
                    this.items.push({
                        item_code: '',
                        description: '',
                        requested_qty: 1,
                        available_qty: 0,
                        bin_location: '',
                        supplier_invoice_no: '',
                        shortage_reason: ''
                    });
                },
                removeRow(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    } else {
                        this.items[0] = { item_code: '', description: '', requested_qty: 1, available_qty: 0, bin_location: '', supplier_invoice_no: '', shortage_reason: '' };
                    }
                },
                shortQty(item) {
                    const req = parseFloat(item.requested_qty) || 0;
                    const avail = parseFloat(item.available_qty) || 0;
                    return Math.max(0, req - avail).toFixed(2);
                }
            };
        }
    </script>
</x-app-layout>
