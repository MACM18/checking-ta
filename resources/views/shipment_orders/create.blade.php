<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Initialize Shipment Order Tracker') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    @if($sourceDoc)
                        Originating from Proforma Invoice (PI): <span class="font-mono font-bold text-indigo-600">{{ $sourceDoc->document_number }}</span>
                    @else
                        Create a standalone shipment tracking workflow
                    @endif
                </p>
            </div>
            <a href="{{ route('shipment-orders.index') }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                &larr; Back to Trackers
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('shipment-orders.store') }}" method="POST" class="space-y-6">
                @csrf
                @if($sourceDoc)
                    <input type="hidden" name="document_id" value="{{ $sourceDoc->id }}">
                @endif

                <!-- Order Identification -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="font-bold text-base text-gray-900 border-b border-gray-100 pb-2 flex items-center">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">1</span>
                        Order & Customer Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Tracker / Order Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="order_number" value="{{ old('order_number', $autoOrderNumber) }}" required class="w-full text-sm font-mono font-bold rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Company / Customer Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="company_name" value="{{ old('company_name', $sourceDoc?->company_name) }}" required class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Destination Country <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="country" value="{{ old('country', $sourceDoc?->country) }}" required class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <select name="currency" class="w-full text-sm rounded-lg border-gray-300 font-bold">
                                <option value="USD" {{ old('currency', $sourceDoc?->currency) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                <option value="AED" {{ old('currency', $sourceDoc?->currency) === 'AED' ? 'selected' : '' }}>AED (د.إ)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Customer PO (Purchase Order) Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="font-bold text-base text-gray-900 border-b border-gray-100 pb-2 flex items-center">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">2</span>
                        Customer Purchase Order (PO) Details
                    </h3>
                    <p class="text-xs text-gray-500">
                        Record customer's purchase order reference once PI is approved. If PO is already received, enter it now to automatically complete Stage 2.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Customer PO Number
                            </label>
                            <input type="text" name="customer_po_number" value="{{ old('customer_po_number') }}" placeholder="e.g. PO-2026-8910" class="w-full text-sm font-mono rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Customer PO Date
                            </label>
                            <input type="date" name="customer_po_date" value="{{ old('customer_po_date') }}" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Customer PO Terms / Notes
                            </label>
                            <textarea name="customer_po_notes" rows="2" placeholder="Special requirements, delivery address notes from customer PO..." class="w-full text-sm rounded-lg border-gray-300">{{ old('customer_po_notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment & Documents Linking -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="font-bold text-base text-gray-900 border-b border-gray-100 pb-2 flex items-center">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">3</span>
                        Payment & Related Documents
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Payment Status
                            </label>
                            <select name="payment_status" class="w-full text-sm rounded-lg border-gray-300">
                                <option value="pending" {{ old('payment_status') === 'pending' ? 'selected' : '' }}>Pending Payment</option>
                                <option value="advance_received" {{ old('payment_status') === 'advance_received' ? 'selected' : '' }}>Advance Received</option>
                                <option value="fully_paid" {{ old('payment_status') === 'fully_paid' ? 'selected' : '' }}>Fully Paid</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Payment Reference
                            </label>
                            <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" placeholder="Bank Swift / TT Ref" class="w-full text-sm font-mono rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Payment Amount Received
                            </label>
                            <input type="number" step="0.01" min="0" name="payment_amount" value="{{ old('payment_amount') }}" placeholder="0.00" class="w-full text-sm font-mono text-right rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Commercial Invoice (N)
                            </label>
                            <input type="text" name="linked_invoice_no" value="{{ old('linked_invoice_no') }}" placeholder="e.g. N10045" class="w-full text-sm font-mono rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Packing List (W)
                            </label>
                            <input type="text" name="linked_packing_list_no" value="{{ old('linked_packing_list_no') }}" placeholder="e.g. W30012" class="w-full text-sm font-mono rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Carrier Method
                            </label>
                            <input type="text" name="carrier_method" value="{{ old('carrier_method', 'DHL Express') }}" placeholder="DHL / Air Freight / Sea" class="w-full text-sm rounded-lg border-gray-300">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end space-x-3">
                    <a href="{{ route('shipment-orders.index') }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-lg text-sm font-bold shadow-sm transition flex items-center">
                        <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Initialize Order Tracker
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
