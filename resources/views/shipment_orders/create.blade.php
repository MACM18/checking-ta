<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Initialize Shipment Order Tracker') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    @if($sourceDoc)
                        Originating from Proforma Invoice (PI): <span class="font-mono font-bold text-indigo-600">{{ $sourceDoc->document_number }}</span> ({{ $sourceDoc->company_name }})
                    @else
                        Track a shipment order starting from Proforma Invoice (PI), Customer PO, or Commercial Invoice
                    @endif
                </p>
            </div>
            <a href="{{ route('shipment-orders.index') }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                &larr; Back to Trackers
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        docSourceMode: '{{ $sourceDoc ? 'system' : 'manual' }}',
        selectedSystemDocId: '{{ $sourceDoc?->id }}',
        companyName: '{{ old('company_name', $sourceDoc?->company_name) }}',
        country: '{{ old('country', $sourceDoc?->country) }}',
        proformaNo: '{{ old('proforma_invoice_no', $sourceDoc?->isProformaInvoice() ? $sourceDoc->document_number : '') }}',
        invoiceNo: '{{ old('linked_invoice_no', $sourceDoc?->isCommercialInvoice() ? $sourceDoc->document_number : '') }}',

        onSystemDocSelect(e) {
            const selectedOpt = e.target.selectedOptions[0];
            if (selectedOpt && selectedOpt.value) {
                this.companyName = selectedOpt.getAttribute('data-company') || this.companyName;
                this.country = selectedOpt.getAttribute('data-country') || this.country;
                const docNo = selectedOpt.getAttribute('data-docno') || '';
                if (docNo.startsWith('E') || docNo.startsWith('EL')) {
                    this.proformaNo = docNo;
                } else if (docNo.startsWith('N')) {
                    this.invoiceNo = docNo;
                }
            }
        }
    }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('shipment-orders.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Section 1: Source Document Selection (System Document or Older/External Reference) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-base text-gray-900 flex items-center">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">1</span>
                            Document Source & Categorization
                        </h3>
                        <div class="flex items-center space-x-2 bg-gray-100 p-1 rounded-lg text-xs font-semibold">
                            <button type="button" @click="docSourceMode = 'system'" :class="docSourceMode === 'system' ? 'bg-white text-indigo-700 shadow-xs font-bold' : 'text-gray-600'" class="px-3 py-1 rounded-md transition">
                                System Document
                            </button>
                            <button type="button" @click="docSourceMode = 'manual'" :class="docSourceMode === 'manual' ? 'bg-white text-indigo-700 shadow-xs font-bold' : 'text-gray-600'" class="px-3 py-1 rounded-md transition">
                                Older / External Doc
                            </button>
                        </div>
                    </div>

                    <!-- Mode A: System Document -->
                    <div x-show="docSourceMode === 'system'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Select System Document (Proforma Invoice or Commercial Invoice)
                            </label>
                            <select name="document_id" @change="onSystemDocSelect($event)" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Choose an existing document --</option>
                                <optgroup label="Proforma Invoices (E / EL)">
                                    @foreach($systemPIs as $pi)
                                        <option value="{{ $pi->id }}" data-docno="{{ $pi->document_number }}" data-company="{{ $pi->company_name }}" data-country="{{ $pi->country }}" {{ old('document_id', $sourceDoc?->id) == $pi->id ? 'selected' : '' }}>
                                            {{ $pi->document_number }} &mdash; {{ $pi->company_name }} ({{ $pi->country }})
                                        </option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Commercial Invoices (N)">
                                    @foreach($systemInvoices as $inv)
                                        <option value="{{ $inv->id }}" data-docno="{{ $inv->document_number }}" data-company="{{ $inv->company_name }}" data-country="{{ $inv->country }}" {{ old('document_id', $sourceDoc?->id) == $inv->id ? 'selected' : '' }}>
                                            {{ $inv->document_number }} &mdash; {{ $inv->company_name }} ({{ $inv->country }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Selecting a system document automatically fills company details and initializes Stage 1.</p>
                        </div>
                    </div>

                    <!-- Mode B: Manual / Older Document Reference -->
                    <div x-show="docSourceMode === 'manual'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Older Document Number or External Reference
                            </label>
                            <input type="text" name="document_reference" value="{{ old('document_reference') }}" placeholder="e.g. E24109, OLD-PI-2025, or Manual Order No" class="w-full text-sm font-mono rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">For shipments based on past invoices, older spreadsheets, or external document numbers not registered in this database.</p>
                        </div>
                    </div>

                    <!-- Category & Basic Details -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Shipment Category <span class="text-red-500">*</span>
                            </label>
                            <select name="shipment_category" required class="w-full text-sm rounded-lg border-gray-300 font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('shipment_category', 'Standard') === $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Tracker / Order Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="order_number" value="{{ old('order_number', $autoOrderNumber) }}" required class="w-full text-sm font-mono font-bold rounded-lg border-gray-300">
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

                <!-- Section 2: Customer & Proforma / PO Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="font-bold text-base text-gray-900 border-b border-gray-100 pb-2 flex items-center">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">2</span>
                        Customer & Proforma Invoice (PI) &rarr; PO Workflow
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Company / Customer Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="company_name" x-model="companyName" required class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Destination Country <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="country" x-model="country" required class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Proforma Invoice (PI) Number
                            </label>
                            <input type="text" name="proforma_invoice_no" x-model="proformaNo" placeholder="e.g. E26211 or EL26211" class="w-full text-sm font-mono rounded-lg border-gray-300">
                            <span class="text-[10px] text-gray-400">Usually starting with E or EL</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Customer PO (Purchase Order) Number
                            </label>
                            <input type="text" name="customer_po_number" value="{{ old('customer_po_number') }}" placeholder="e.g. PO-89012" class="w-full text-sm font-mono rounded-lg border-gray-300">
                            <span class="text-[10px] text-gray-400">Entering this automatically marks Stage 2 (PO Received) complete</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Customer PO Date
                            </label>
                            <input type="date" name="customer_po_date" value="{{ old('customer_po_date') }}" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Commercial Invoice (N) Number
                            </label>
                            <input type="text" name="linked_invoice_no" x-model="invoiceNo" placeholder="e.g. N26211" class="w-full text-sm font-mono rounded-lg border-gray-300">
                            <span class="text-[10px] text-gray-400">Commercial invoice generated for shipment</span>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Customer PO Notes / Terms
                            </label>
                            <textarea name="customer_po_notes" rows="2" placeholder="Special requirements, delivery instructions from customer..." class="w-full text-sm rounded-lg border-gray-300">{{ old('customer_po_notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Payment & Shipping Logistics -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="font-bold text-base text-gray-900 border-b border-gray-100 pb-2 flex items-center">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">3</span>
                        Payment & Logistics
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
                            <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" placeholder="TT Transfer Ref / Bank Ref" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Payment Amount Received
                            </label>
                            <input type="number" step="0.01" name="payment_amount" value="{{ old('payment_amount') }}" placeholder="0.00" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Carrier / Courier Method
                            </label>
                            <input type="text" name="carrier_method" value="{{ old('carrier_method') }}" placeholder="e.g. DHL Express, Air Freight, Emirates" class="w-full text-sm rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                AWB / Tracking Number
                            </label>
                            <input type="text" name="tracking_awb_no" value="{{ old('tracking_awb_no') }}" placeholder="Airway bill or courier tracking" class="w-full text-sm font-mono rounded-lg border-gray-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Packing List (W) Number
                            </label>
                            <input type="text" name="linked_packing_list_no" value="{{ old('linked_packing_list_no') }}" placeholder="e.g. W26211" class="w-full text-sm font-mono rounded-lg border-gray-300">
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-3 pt-2">
                    <a href="{{ route('shipment-orders.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-7 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-sm font-bold shadow-sm transition">
                        Initialize Shipment Tracker &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
