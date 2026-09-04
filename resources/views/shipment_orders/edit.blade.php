<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Edit Shipment Order: <span class="font-mono text-indigo-700">{{ $shipmentOrder->order_number }}</span>
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Update customer details, linked workflow documents (PI, Invoice, Packing List), payment status, and tracking info.
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('shipment-orders.show', $shipmentOrder) }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                    &larr; View Cockpit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('shipment-orders.update', $shipmentOrder) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Section 1: Order Status & Category -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="font-bold text-base text-gray-900 border-b border-gray-100 pb-2 flex items-center">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">1</span>
                        Order Status & General Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Order Status <span class="text-red-500">*</span>
                            </label>
                            <select name="status" required class="w-full text-sm rounded-lg border-gray-300 font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="active" {{ old('status', $shipmentOrder->status) === 'active' ? 'selected' : '' }}>Active / In Progress</option>
                                <option value="completed" {{ old('status', $shipmentOrder->status) === 'completed' ? 'selected' : '' }}>Completed / Delivered</option>
                                <option value="cancelled" {{ old('status', $shipmentOrder->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Shipment Category <span class="text-red-500">*</span>
                            </label>
                            <select name="shipment_category" required class="w-full text-sm rounded-lg border-gray-300 font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('shipment_category', $shipmentOrder->shipment_category) === $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <select name="currency" class="w-full text-sm rounded-lg border-gray-300 font-bold">
                                <option value="USD" {{ old('currency', $shipmentOrder->currency) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                <option value="AED" {{ old('currency', $shipmentOrder->currency) === 'AED' ? 'selected' : '' }}>AED (د.إ)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Company / Customer Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="company_name" value="{{ old('company_name', $shipmentOrder->company_name) }}" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Destination Country <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="country" value="{{ old('country', $shipmentOrder->country) }}" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Custom Status Message (Highlighted on Orders List)
                            </label>
                            <input type="text"
                                   name="custom_status_message"
                                   value="{{ old('custom_status_message', $shipmentOrder->custom_status_message) }}"
                                   placeholder="e.g. Awaiting customs clearance, Urgent inspection, Ready for dispatch"
                                   maxlength="255"
                                   class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">Displayed with a prominent highlighted badge on the shipment orders index page.</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Linked Workflow Documents (PI, Invoice, Packing List) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
                    <h3 class="font-bold text-base text-gray-900 border-b border-gray-100 pb-2 flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">2</span>
                            Linked Workflow Documents (PI, Commercial Invoice, Packing List)
                        </div>
                    </h3>
                    <p class="text-xs text-gray-500 -mt-2">
                        Select an existing document from the system to enable 1-click navigation, or type an external document number.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Proforma Invoice (PI) -->
                        <div class="p-4 bg-slate-50 rounded-xl border border-gray-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-indigo-900 uppercase">1. Proforma Invoice (PI)</span>
                                @if($shipmentOrder->resolved_proforma_document)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">Linked</span>
                                @endif
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">System Document</label>
                                <select name="document_id" class="w-full text-xs rounded-lg border-gray-300">
                                    <option value="">-- Select from system --</option>
                                    @foreach($systemPIs as $pi)
                                        <option value="{{ $pi->id }}" {{ old('document_id', $shipmentOrder->document_id) == $pi->id ? 'selected' : '' }}>
                                            {{ $pi->document_number }} - {{ $pi->company_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Or Document # (e.g. E26215)</label>
                                <input type="text" name="proforma_invoice_no" value="{{ old('proforma_invoice_no', $shipmentOrder->proforma_invoice_no) }}" placeholder="e.g. E26215" class="w-full text-xs font-mono rounded-lg border-gray-300">
                            </div>
                        </div>

                        <!-- Commercial Invoice (N) -->
                        <div class="p-4 bg-slate-50 rounded-xl border border-gray-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-900 uppercase">2. Commercial Invoice (N)</span>
                                @if($shipmentOrder->resolved_invoice_document)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Linked</span>
                                @endif
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">System Document</label>
                                <select name="invoice_document_id" class="w-full text-xs rounded-lg border-gray-300">
                                    <option value="">-- Select from system --</option>
                                    @foreach($systemInvoices as $inv)
                                        <option value="{{ $inv->id }}" {{ old('invoice_document_id', $shipmentOrder->invoice_document_id) == $inv->id ? 'selected' : '' }}>
                                            {{ $inv->document_number }} - {{ $inv->company_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Or Invoice # (e.g. N26001)</label>
                                <input type="text" name="linked_invoice_no" value="{{ old('linked_invoice_no', $shipmentOrder->linked_invoice_no) }}" placeholder="e.g. N26001" class="w-full text-xs font-mono rounded-lg border-gray-300">
                            </div>
                        </div>

                        <!-- Packing List (W) -->
                        <div class="p-4 bg-slate-50 rounded-xl border border-gray-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-blue-900 uppercase">3. Packing List (W)</span>
                                @if($shipmentOrder->resolved_packing_list_document)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">Linked</span>
                                @endif
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">System Document</label>
                                <select name="packing_list_document_id" class="w-full text-xs rounded-lg border-gray-300">
                                    <option value="">-- Select from system --</option>
                                    @foreach($systemPackingLists as $pl)
                                        <option value="{{ $pl->id }}" {{ old('packing_list_document_id', $shipmentOrder->packing_list_document_id) == $pl->id ? 'selected' : '' }}>
                                            {{ $pl->document_number }} - {{ $pl->company_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Or Packing List # (e.g. W26001)</label>
                                <input type="text" name="linked_packing_list_no" value="{{ old('linked_packing_list_no', $shipmentOrder->linked_packing_list_no) }}" placeholder="e.g. W26001" class="w-full text-xs font-mono rounded-lg border-gray-300">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Customer PO & Payment Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="font-bold text-base text-gray-900 border-b border-gray-100 pb-2 flex items-center">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">3</span>
                        Customer Purchase Order & Payment Tracking
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Customer PO Number</label>
                            <input type="text" name="customer_po_number" value="{{ old('customer_po_number', $shipmentOrder->customer_po_number) }}" class="w-full text-sm font-mono rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Customer PO Date</label>
                            <input type="date" name="customer_po_date" value="{{ old('customer_po_date', $shipmentOrder->customer_po_date?->format('Y-m-d')) }}" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Payment Status</label>
                            <select name="payment_status" class="w-full text-sm rounded-lg border-gray-300 font-semibold">
                                <option value="pending" {{ old('payment_status', $shipmentOrder->payment_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="payment_submitted" {{ old('payment_status', $shipmentOrder->payment_status) === 'payment_submitted' ? 'selected' : '' }}>Payment Submitted (Advice/Slip)</option>
                                <option value="advance_received" {{ old('payment_status', $shipmentOrder->payment_status) === 'advance_received' ? 'selected' : '' }}>Advance Confirmed</option>
                                <option value="fully_paid" {{ old('payment_status', $shipmentOrder->payment_status) === 'fully_paid' ? 'selected' : '' }}>Fully Paid & Confirmed</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Payment Slip / Advice Ref</label>
                            <input type="text" name="payment_submission_ref" value="{{ old('payment_submission_ref', $shipmentOrder->payment_submission_ref) }}" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Bank Confirmed Ref</label>
                            <input type="text" name="payment_reference" value="{{ old('payment_reference', $shipmentOrder->payment_reference) }}" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Payment Amount</label>
                            <input type="number" step="0.01" name="payment_amount" value="{{ old('payment_amount', $shipmentOrder->payment_amount) }}" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">PO Notes / Special Instructions</label>
                            <textarea name="customer_po_notes" rows="2" class="w-full text-sm rounded-lg border-gray-300">{{ old('customer_po_notes', $shipmentOrder->customer_po_notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Carrier & Logistics Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="font-bold text-base text-gray-900 border-b border-gray-100 pb-2 flex items-center">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">4</span>
                        Carrier & Dispatch Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Carrier Method</label>
                            <input type="text" name="carrier_method" value="{{ old('carrier_method', $shipmentOrder->carrier_method) }}" placeholder="e.g. DHL, Air Freight" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">AWB / Tracking Number</label>
                            <input type="text" name="tracking_awb_no" value="{{ old('tracking_awb_no', $shipmentOrder->tracking_awb_no) }}" class="w-full text-sm font-mono rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Dispatch Date</label>
                            <input type="date" name="dispatch_date" value="{{ old('dispatch_date', $shipmentOrder->dispatch_date?->format('Y-m-d')) }}" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Delivery Date</label>
                            <input type="date" name="delivery_date" value="{{ old('delivery_date', $shipmentOrder->delivery_date?->format('Y-m-d')) }}" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">General Notes</label>
                            <textarea name="notes" rows="2" class="w-full text-sm rounded-lg border-gray-300">{{ old('notes', $shipmentOrder->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('shipment-orders.show', $shipmentOrder) }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                        Save Order & Linked Documents
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
