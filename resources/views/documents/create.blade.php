<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('documents.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 flex items-center">
                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Back to Documents
                    </a>
                </div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight mt-1">
                    New Document Wizard & Verification
                </h2>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                Live Verification Active
            </span>
        </div>
    </x-slot>

    @php
        $initialData = [
            'documentNumber' => '',
            'documentType' => $targetType ?: '',
            'sourceDocumentId' => $sourceDoc?->id,
            'sourceDocumentNumber' => $sourceDoc?->document_number,
            'companyName' => $sourceDoc?->company_name ?? '',
            'country' => $sourceDoc?->country ?? '',
            'address' => $sourceDoc?->address ?? '',
            'contactDetails' => $sourceDoc?->contact_details ?? '',
            'currency' => $sourceDoc?->currency ?? 'USD',
            'netWeight' => $sourceDoc?->total_net_weight,
            'grossWeight' => $sourceDoc?->total_gross_weight,
            'items' => $sourceDoc && $sourceDoc->items->isNotEmpty() ? $sourceDoc->items->map(fn($it) => [
                'item_code' => $it->item_code,
                'description' => $it->description,
                'unit_amount' => (float) $it->unit_amount,
                'unit_price' => (float) $it->unit_price,
                'unit_weight' => (float) $it->unit_weight,
                'total_weight' => (float) $it->total_weight,
                'total_amount' => (float) $it->total_amount,
                'price_from_tracker' => false,
            ]) : null,
            'packages' => $sourceDoc && $sourceDoc->packages->isNotEmpty() ? $sourceDoc->packages->map(fn($pkg) => [
                'package_type' => $pkg->package_type,
                'dimension_type' => $pkg->dimension_type,
                'length_cm' => (float) $pkg->length_cm,
                'width_cm' => (float) $pkg->width_cm,
                'height_cm' => (float) $pkg->height_cm,
                'diameter_cm' => (float) $pkg->diameter_cm,
                'quantity' => (int) $pkg->quantity,
                'gross_weight_per_pkg_kg' => (float) $pkg->gross_weight_per_pkg_kg,
                'volumetric_weight_kg' => (float) $pkg->volumetric_weight_kg,
                'cbm' => (float) $pkg->cbm,
            ]) : null,
        ];
    @endphp

    <div class="py-8" x-data="documentCreator(@js($initialData))">
        <form method="POST" action="{{ route('documents.store') }}" @submit="prepareSubmit($event)">
            @csrf

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- Main Document Details Form (8 Cols) -->
                    <div class="lg:col-span-8 space-y-6">

                        <!-- Step 0: Import from Source Document (PI / Previous Document) -->
                        <div class="bg-gradient-to-r from-indigo-50/70 via-purple-50/50 to-white rounded-xl shadow-sm border border-indigo-100 p-5 space-y-3" x-data="{
                            sourceInput: '{{ $sourceDoc?->document_number ?? '' }}',
                            isImporting: false,
                            importMessage: '{{ $sourceDoc ? "Loaded initial items & details from {$sourceDoc->document_number}" : "" }}',
                            importError: '',

                            async triggerImport() {
                                const docCode = (this.sourceInput || '').trim();
                                if (!docCode) {
                                    alert('Please select or enter a source document code to import.');
                                    return;
                                }

                                if (!confirm(`Are you sure you want to import details from ${docCode}? This will populate customer details, line items, and packaging.`)) {
                                    return;
                                }

                                this.isImporting = true;
                                this.importError = '';
                                this.importMessage = '';

                                try {
                                    const res = await fetch(`/api/documents/source-data/${encodeURIComponent(docCode)}`);
                                    if (!res.ok) {
                                        const errData = await res.json();
                                        throw new Error(errData.error || 'Failed to find source document');
                                    }
                                    const data = await res.json();

                                    // Import customer / recipient data
                                    if (data.company_name) $data.companyName = data.company_name;
                                    if (data.country) $data.country = data.country;
                                    if (data.address) $data.address = data.address;
                                    if (data.contact_details) $data.contactDetails = data.contact_details;
                                    if (data.currency) $data.currency = data.currency;

                                    $data.sourceDocumentId = data.id;
                                    $data.sourceDocumentNumber = data.document_number;

                                    // Import line items
                                    if (data.items && data.items.length > 0) {
                                        $data.items = data.items.map(it => ({
                                            item_code: it.item_code || '',
                                            description: it.description || '',
                                            unit_amount: it.unit_amount || 1,
                                            unit_price: it.unit_price || 0,
                                            unit_weight: it.unit_weight || 0,
                                            total_weight: it.total_weight || (it.unit_weight * it.unit_amount) || 0,
                                            total_amount: it.total_amount || (it.unit_amount * it.unit_price) || 0,
                                            price_from_tracker: false
                                        }));
                                    }

                                    // Import packages if present
                                    if (data.packages && data.packages.length > 0) {
                                        $data.packages = data.packages.map(p => ({
                                            package_type: p.package_type || 'Carton',
                                            dimension_type: p.dimension_type || 'standard',
                                            length_cm: p.length_cm,
                                            width_cm: p.width_cm,
                                            height_cm: p.height_cm,
                                            diameter_cm: p.diameter_cm,
                                            quantity: p.quantity || 1,
                                            gross_weight_per_pkg_kg: p.gross_weight_per_pkg_kg,
                                            volumetric_weight_kg: p.volumetric_weight_kg || 0,
                                            cbm: p.cbm || 0
                                        }));
                                    }

                                    if (data.total_net_weight) $data.netWeight = data.total_net_weight;
                                    if (data.total_gross_weight) $data.grossWeight = data.total_gross_weight;

                                    $data.recalcTotals();
                                    this.importMessage = `Successfully imported ${data.items ? data.items.length : 0} items from ${data.document_number} (${data.company_name})!`;
                                } catch (err) {
                                    this.importError = err.message;
                                } finally {
                                    this.isImporting = false;
                                }
                            }
                        }">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-sm text-gray-900">
                                            Import from Source Document (Proforma Invoice / Previous Document)
                                        </h3>
                                        <p class="text-xs text-gray-500">
                                            Select or type a source document code (e.g. <span class="font-mono font-bold text-indigo-700">E26211</span>) to import company details, items, quantities, and packaging.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-1">
                                <div class="flex-1 relative">
                                    <input type="text"
                                           x-model="sourceInput"
                                           list="recentSourceDocsList"
                                           placeholder="Type or select source document code (e.g. E26211, N26001)..."
                                           class="w-full text-xs font-mono font-bold rounded-lg border-gray-300 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                                    <datalist id="recentSourceDocsList">
                                        @foreach($availableSourceDocs as $avail)
                                            <option value="{{ $avail->document_number }}">
                                                {{ $avail->document_number }} &mdash; {{ $avail->company_name }} ({{ $avail->document_type }})
                                            </option>
                                        @endforeach
                                    </datalist>
                                </div>

                                <button type="button"
                                        @click="triggerImport()"
                                        :disabled="isImporting"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-sm transition disabled:opacity-50">
                                    <svg x-show="!isImporting" class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                                    <svg x-show="isImporting" class="animate-spin w-3.5 h-3.5 me-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Import Records & Items</span>
                                </button>
                            </div>

                            <div x-show="importMessage" x-cloak class="p-2.5 bg-emerald-50 text-emerald-800 rounded-lg text-xs font-semibold flex items-center border border-emerald-200">
                                <svg class="w-4 h-4 me-1.5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                <span x-text="importMessage"></span>
                            </div>

                            <div x-show="importError" x-cloak class="p-2.5 bg-rose-50 text-rose-800 rounded-lg text-xs font-semibold flex items-center border border-rose-200">
                                <svg class="w-4 h-4 me-1.5 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                <span x-text="importError"></span>
                            </div>

                            <!-- Hidden inputs for source document reference -->
                            <input type="hidden" name="source_document_id" x-model="sourceDocumentId">
                            <input type="hidden" name="source_document_number" x-model="sourceDocumentNumber">
                        </div>

                        <!-- Step 1: Document Identification Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                            <div class="border-b border-gray-100 pb-3">
                                <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">1</span>
                                    Document Identification & Smart Detection
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Type the document number (e.g. <span class="font-mono font-semibold">E26211</span>, <span class="font-mono font-semibold">N10045</span>, <span class="font-mono font-semibold">W30012</span>, <span class="font-mono font-semibold">E26211R</span>, <span class="font-mono font-semibold">CR100</span>, <span class="font-mono font-semibold">E26211C</span>). The system will automatically classify the document type and load the checklist.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Document Number Input -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Document Number <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text"
                                               name="document_number"
                                               x-model="documentNumber"
                                               @input.debounce.300ms="detectDocumentType()"
                                               placeholder="e.g. E26211 or N10045"
                                               required
                                               class="w-full text-base font-mono uppercase font-semibold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none" x-show="isDetecting">
                                            <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    @error('document_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <!-- Document Type Selector with Suggestion Badge -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Document Type <span class="text-red-500">*</span>
                                        </label>
                                        <span x-show="ruleMatched" class="text-[11px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100" x-text="ruleMatched"></span>
                                    </div>
                                    <select name="document_type"
                                            x-model="documentType"
                                            @change="loadChecklistsForType(documentType)"
                                            required
                                            class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                        <option value="">-- Select Type --</option>
                                        @foreach($types as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('document_type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2">
                                <!-- Date -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Document Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="document_date" value="{{ old('document_date', $defaultDate) }}" required class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <!-- Currency -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Currency <span class="text-red-500">*</span>
                                    </label>
                                    <select name="currency" x-model="currency" required class="w-full text-sm font-semibold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="USD">USD ($)</option>
                                        <option value="AED">AED (AED)</option>
                                    </select>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Status
                                    </label>
                                    <select name="status" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="draft">Draft</option>
                                        <option value="active">Active / Issued</option>
                                        <option value="final">Finalized</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Customer / Company Details -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="border-b border-gray-100 pb-3">
                                <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">2</span>
                                    Company & Recipient Information
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Company Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="company_name" x-model="companyName" required placeholder="e.g. Apex Industrial Solutions LLC" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Country <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="country" x-model="country" required placeholder="e.g. United Arab Emirates, Oman, Saudi Arabia" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Address Needed
                                    </label>
                                    <textarea name="address" x-model="address" rows="3" placeholder="Billing & Shipping street address, warehouse, P.O. Box..." class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Other Contact Details
                                    </label>
                                    <textarea name="contact_details" x-model="contactDetails" rows="3" placeholder="Attn / Contact person, Phone, Email, TRN / Tax No..." class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Line Items (Item code, unit amount, unit price/weight, total amount/weight) -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">3</span>
                                        <span x-text="isWeightOnly ? 'Packing List & Weight Breakdown' : 'Document Line Items & Pricing'"></span>
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="isWeightOnly ? 'Item code, description, quantity, unit net weight (kg), and calculated total net weight. Prices are omitted for packing lists & reserve documents.' : 'Item code, description, quantity/unit amount, unit price, and auto-computed line total.'"></p>
                                </div>
                                <button type="button" @click="addItem()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                    <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add Line Item
                                </button>
                            </div>

                            <!-- Price Tracker Tier Selection Bar (Shown only when financial pricing applies) -->
                            <div x-show="!isWeightOnly" class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-gray-700 flex items-center text-xs">
                                            <svg class="w-4 h-4 text-indigo-600 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                            Price List:
                                        </span>
                                        <select x-model="selectedPriceList" @change="onPriceTierChanged()" class="text-xs rounded-lg border-gray-300 py-1 px-2 font-semibold focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                            <option value="">(All Price Lists)</option>
                                            <template x-for="list in availablePriceLists" :key="list">
                                                <option :value="list" x-text="list"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-gray-700 flex items-center text-xs">
                                            Price Label / Tier:
                                        </span>
                                        <select x-model="selectedPriceLabel" @change="onPriceTierChanged()" class="text-xs rounded-lg border-gray-300 py-1 px-2.5 font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white" :class="selectedPriceLabel ? 'text-indigo-700 font-extrabold ring-1 ring-indigo-500' : 'text-gray-600'">
                                            <option value="">-- No Auto-Pricing --</option>
                                            <template x-for="lbl in availablePriceLabels" :key="lbl">
                                                <option :value="lbl" x-text="lbl"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <template x-if="selectedPriceLabel">
                                        <button type="button" @click="repriceAllLineItems()" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-100 hover:bg-indigo-200 text-indigo-800 font-bold transition text-[11px]" title="Update all line item unit prices to match currently selected label">
                                            <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            Apply <span x-text="selectedPriceLabel" class="ms-0.5"></span> to All Rows
                                        </button>
                                    </template>
                                    <span class="text-[11px] text-gray-400 font-medium" x-show="selectedPriceLabel">
                                        Auto-fills price when item code is entered
                                    </span>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-3 py-2 text-left w-36">Item Code</th>
                                            <th class="px-3 py-2 text-left">Description</th>
                                            <th class="px-3 py-2 text-right w-24">Quantity / Units</th>
                                            <!-- Financial headers -->
                                            <th x-show="!isWeightOnly" class="px-3 py-2 text-right w-28">Unit Price (<span x-text="currency"></span>)</th>
                                            <th x-show="!isWeightOnly" class="px-3 py-2 text-right w-32">Total Amount</th>
                                            <!-- Weight-only headers -->
                                            <th x-show="isWeightOnly" class="px-3 py-2 text-right w-28">Unit Net Wt (kg)</th>
                                            <th x-show="isWeightOnly" class="px-3 py-2 text-right w-32">Total Net Wt (kg)</th>
                                            <th class="sticky right-0 z-20 bg-gray-50 px-2 py-2 text-center w-10 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr class="hover:bg-slate-50 group">
                                                <td class="px-3 py-2">
                                                    <input type="text"
                                                           :name="`items[${index}][item_code]`"
                                                           x-model="item.item_code"
                                                           :list="`item-datalist-${index}`"
                                                           @input.debounce.250ms="onItemCodeInput(item, index)"
                                                           @change="lookupItemPrice(item)"
                                                           placeholder="SKU-101"
                                                           autocomplete="off"
                                                           required
                                                           class="w-full text-xs font-mono font-semibold rounded border-gray-300 py-1.5 px-2">
                                                    <datalist :id="`item-datalist-${index}`">
                                                        <template x-for="sug in (itemSuggestions[index] || [])" :key="sug.item_code">
                                                            <option :value="sug.item_code" :label="`${sug.item_code} - ${sug.description} (${sug.currency || ''} ${sug.unit_price || ''})`"></option>
                                                        </template>
                                                    </datalist>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="text" :name="`items[${index}][description]`" x-model="item.description" placeholder="Item description / specs" class="w-full text-xs rounded border-gray-300 py-1.5 px-2">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.001" min="0.001" :name="`items[${index}][unit_amount]`" x-model.number="item.unit_amount" @input="recalcItem(item)" required class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>
                                                <!-- Financial mode inputs -->
                                                <td x-show="!isWeightOnly" class="px-3 py-2">
                                                    <div class="relative">
                                                        <input type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price" @input="recalcItem(item)" :required="!isWeightOnly" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                        <span x-show="item.price_from_tracker" x-cloak class="absolute -top-1 -right-1 flex h-2 w-2" title="Price loaded from Item Price Tracker">
                                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td x-show="!isWeightOnly" class="px-3 py-2 text-right font-mono font-bold text-gray-800">
                                                    <span x-text="currency"></span> <span x-text="formatNumber(item.total_amount)"></span>
                                                </td>
                                                <!-- Weight-only mode inputs -->
                                                <template x-if="isWeightOnly">
                                                    <input type="hidden" :name="`items[${index}][unit_price]`" value="0">
                                                </template>
                                                <td x-show="isWeightOnly" class="px-3 py-2">
                                                    <input type="number" step="0.001" min="0" :name="`items[${index}][unit_weight]`" x-model.number="item.unit_weight" @input="recalcItem(item)" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>
                                                <td x-show="isWeightOnly" class="px-3 py-2 text-right font-mono font-bold text-gray-800">
                                                    <input type="hidden" :name="`items[${index}][total_weight]`" :value="item.total_weight">
                                                    <span x-text="formatWeight(item.total_weight)"></span> kg
                                                </td>
                                                <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-2 py-2 text-center shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-red-400 hover:text-red-600 transition p-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Weights & Subtotal Bar with Live Weight Check -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-100 bg-slate-50 p-4 rounded-lg">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Total Net Weight (kg)
                                        </label>
                                        <button type="button" @click="syncWeightFromItems()" x-show="calculatedItemsNetWeight > 0" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-semibold underline">
                                            Sync from Items (<span x-text="formatWeight(calculatedItemsNetWeight)"></span> kg)
                                        </button>
                                    </div>
                                    <input type="number" step="0.001" min="0" name="total_net_weight" x-model.number="netWeight" placeholder="0.000" class="w-full text-sm font-mono rounded-lg border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Total Gross Weight (kg)
                                    </label>
                                    <input type="number" step="0.001" min="0" name="total_gross_weight" x-model.number="grossWeight" placeholder="0.000" class="w-full text-sm font-mono rounded-lg border-gray-300">
                                </div>
                                <div class="text-right flex flex-col justify-center">
                                    <div x-show="!isWeightOnly">
                                        <span class="text-xs uppercase tracking-wider font-semibold text-gray-500">Calculated Subtotal</span>
                                        <span class="text-xl font-mono font-extrabold text-indigo-700 block">
                                            <span x-text="currency"></span> <span x-text="formatNumber(subtotal)"></span>
                                        </span>
                                    </div>
                                    <div x-show="isWeightOnly" class="space-y-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Weight-Only Document
                                        </span>
                                        <p class="text-[11px] text-gray-500">Prices omitted (Packing List / Reserve)</p>
                                    </div>
                                </div>

                                <!-- Live Weight Validation Warning -->
                                <div x-show="netWeight > 0 && grossWeight > 0 && netWeight > grossWeight" x-transition class="md:col-span-3 text-xs text-amber-800 bg-amber-50 p-2.5 rounded-lg border border-amber-200 flex items-center">
                                    <svg class="w-4 h-4 me-2 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    <span><strong>Weight Alert:</strong> Net Weight (<span x-text="netWeight"></span> kg) is greater than Gross Weight (<span x-text="grossWeight"></span> kg). Ensure packaging is factored into gross weight.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Package Dimensions & Diameters Breakdown -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">4</span>
                                        Package Dimensions & Diameter Breakdown
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Specify dimensions for multiple packages. Supports rectangular (L × W × H) or cylindrical (Diameter × Height) packaging.
                                    </p>
                                </div>
                                <button type="button" @click="addPackage()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                    <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add Package Type
                                </button>
                            </div>

                            <!-- Package Rows Table -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-3 py-2 text-left w-32">Package Type</th>
                                            <th class="px-3 py-2 text-left w-28">Type</th>
                                            <th class="px-3 py-2 text-left">Dimensions (cm)</th>
                                            <th class="px-3 py-2 text-right w-20">Qty (Pkgs)</th>
                                            <th class="px-3 py-2 text-right w-28">Weight/Pkg (kg)</th>
                                            <th class="px-3 py-2 text-right w-28">Vol. Wt (kg)</th>
                                            <th class="px-3 py-2 text-right w-24">CBM (m³)</th>
                                            <th class="sticky right-0 z-20 bg-gray-50 px-2 py-2 text-center w-10 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(pkg, pIndex) in packages" :key="pIndex">
                                            <tr class="hover:bg-slate-50 group">
                                                <!-- Package Type -->
                                                <td class="px-3 py-2">
                                                    <select :name="`packages[${pIndex}][package_type]`" x-model="pkg.package_type" class="w-full text-xs rounded border-gray-300 py-1.5 px-2">
                                                        <option value="Carton">Carton / Box</option>
                                                        <option value="Wooden Crate">Wooden Crate</option>
                                                        <option value="Pallet">Pallet</option>
                                                        <option value="Drum">Drum / Cylinder</option>
                                                        <option value="Roll">Roll</option>
                                                        <option value="Bundle">Bundle</option>
                                                    </select>
                                                </td>

                                                <!-- Dimension Type (Standard vs Diameter) -->
                                                <td class="px-3 py-2">
                                                    <select :name="`packages[${pIndex}][dimension_type]`" x-model="pkg.dimension_type" @change="recalcPackage(pkg)" class="w-full text-xs font-semibold rounded border-gray-300 py-1.5 px-2 text-indigo-700 bg-indigo-50/50">
                                                        <option value="standard">Box (L×W×H)</option>
                                                        <option value="diameter">Cylinder (Ø×H)</option>
                                                    </select>
                                                </td>

                                                <!-- Dimensions Inputs -->
                                                <td class="px-3 py-2">
                                                    <!-- Standard Box Inputs -->
                                                    <template x-if="pkg.dimension_type === 'standard'">
                                                        <div class="flex items-center space-x-1 font-mono">
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][length_cm]`" x-model.number="pkg.length_cm" @input="recalcPackage(pkg)" placeholder="L" class="w-16 text-xs text-right rounded border-gray-300 py-1 px-1.5">
                                                            <span class="text-gray-400">×</span>
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][width_cm]`" x-model.number="pkg.width_cm" @input="recalcPackage(pkg)" placeholder="W" class="w-16 text-xs text-right rounded border-gray-300 py-1 px-1.5">
                                                            <span class="text-gray-400">×</span>
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][height_cm]`" x-model.number="pkg.height_cm" @input="recalcPackage(pkg)" placeholder="H" class="w-16 text-xs text-right rounded border-gray-300 py-1 px-1.5">
                                                            <span class="text-[11px] text-gray-400">cm</span>
                                                        </div>
                                                    </template>

                                                    <!-- Cylinder / Diameter Inputs -->
                                                    <template x-if="pkg.dimension_type === 'diameter'">
                                                        <div class="flex items-center space-x-1 font-mono">
                                                            <span class="text-xs text-indigo-600 font-bold">Ø</span>
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][diameter_cm]`" x-model.number="pkg.diameter_cm" @input="recalcPackage(pkg)" placeholder="Dia" class="w-20 text-xs text-right rounded border-gray-300 py-1 px-1.5" title="Diameter in cm">
                                                            <span class="text-gray-400">×</span>
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][height_cm]`" x-model.number="pkg.height_cm" @input="recalcPackage(pkg)" placeholder="H" class="w-20 text-xs text-right rounded border-gray-300 py-1 px-1.5" title="Height in cm">
                                                            <span class="text-[11px] text-gray-400">cm</span>
                                                        </div>
                                                    </template>
                                                </td>

                                                <!-- Quantity (number of packages with these dimensions) -->
                                                <td class="px-3 py-2">
                                                    <input type="number" min="1" :name="`packages[${pIndex}][quantity]`" x-model.number="pkg.quantity" @input="recalcPackage(pkg)" required class="w-full text-xs font-mono font-bold text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>

                                                <!-- Gross Weight per Package -->
                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.001" min="0" :name="`packages[${pIndex}][gross_weight_per_pkg_kg]`" x-model.number="pkg.gross_weight_per_pkg_kg" @input="recalcPackage(pkg)" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>

                                                <!-- Volumetric Weight (computed) -->
                                                <td class="px-3 py-2 text-right font-mono font-semibold text-gray-700">
                                                    <span x-text="pkg.volumetric_weight_kg ? pkg.volumetric_weight_kg.toFixed(2) : '0.00'"></span> kg
                                                </td>

                                                <!-- CBM (computed) -->
                                                <td class="px-3 py-2 text-right font-mono text-gray-600">
                                                    <span x-text="pkg.cbm ? pkg.cbm.toFixed(3) : '0.000'"></span> m³
                                                </td>

                                                <!-- Remove Row -->
                                                <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-2 py-2 text-center shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                                    <button type="button" @click="removePackage(pIndex)" x-show="packages.length > 1" class="text-red-400 hover:text-red-600 transition p-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Package Aggregation Bar -->
                            <div class="pt-3 border-t border-gray-100 bg-slate-50 p-4 rounded-lg flex flex-wrap items-center justify-between gap-4 text-xs">
                                <div class="flex items-center space-x-6">
                                    <div>
                                        <span class="text-gray-500 uppercase tracking-wider font-semibold block text-[10px]">Total Packages</span>
                                        <span class="text-base font-bold font-mono text-gray-900" x-text="totalPackagesCount"></span> pkgs
                                    </div>
                                    <div>
                                        <span class="text-gray-500 uppercase tracking-wider font-semibold block text-[10px]">Total Package Gross Wt</span>
                                        <span class="text-base font-bold font-mono text-gray-900" x-text="totalPackageGrossWeight.toFixed(2)"></span> kg
                                    </div>
                                    <div>
                                        <span class="text-indigo-600 uppercase tracking-wider font-bold block text-[10px]">Total Volumetric Wt</span>
                                        <span class="text-base font-extrabold font-mono text-indigo-700" x-text="totalVolumetricWeight.toFixed(2)"></span> kg
                                    </div>
                                    <div>
                                        <span class="text-emerald-600 uppercase tracking-wider font-bold block text-[10px]">Total Volume (CBM)</span>
                                        <span class="text-base font-extrabold font-mono text-emerald-700" x-text="totalCbm.toFixed(3)"></span> m³
                                    </div>
                                </div>

                                <button type="button" @click="syncWeightFromPackages()" x-show="totalPackageGrossWeight > 0" class="inline-flex items-center px-2.5 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 rounded font-semibold text-xs transition">
                                    <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    Set as Total Gross Weight
                                </button>
                            </div>
                        </div>

                        <!-- Step 5: Shipment Method Costs (DHL, Air freight, Sea freight) with Rate / kg -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">5</span>
                                        Shipment Method Costs Comparison & Rate / KG
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Track air freight or DHL rate per kg ($/kg or AED/kg). Chargeable weight evaluates higher of actual vs volumetric weight.
                                    </p>
                                </div>

                                <div class="text-right">
                                    <span class="text-[11px] text-gray-500 block">Chargeable Wt for Air/DHL:</span>
                                    <span class="font-mono font-bold text-sm text-indigo-700" x-text="`${chargeableWeight.toFixed(2)} kg`"></span>
                                    <span class="text-[10px] text-gray-400 block" x-text="grossWeight >= totalVolumetricWeight ? '(Actual Gross Weight)' : '(Volumetric Weight)'"></span>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left w-36">Carrier Method</th>
                                            <th class="px-3 py-2.5 text-right w-28">Checked Wt (kg)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Rate / kg (<span x-text="currency"></span>)</th>
                                            <th class="px-3 py-2.5 text-right w-32">System Amount (<span x-text="currency"></span>)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Added Amount (<span x-text="currency"></span>)</th>
                                            <th class="px-3 py-2.5 text-right w-36">Given Amount (<span x-text="currency"></span>)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <!-- DHL -->
                                        <tr class="hover:bg-amber-50/40">
                                            <td class="px-4 py-2.5 font-bold text-amber-700 flex items-center">
                                                <span class="w-2 h-2 rounded-full bg-amber-500 me-2"></span>
                                                DHL Express
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.001" min="0" name="shipment_costs[dhl][checked_weight]" x-model.number="carriers.dhl.checked_weight" @input="recalcCarrier('dhl')" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][rate_per_kg]" x-model.number="carriers.dhl.rate_per_kg" @input="recalcCarrier('dhl')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][system_amount]" x-model.number="carriers.dhl.system_amount" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][added_amount]" x-model.number="carriers.dhl.added_amount" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center space-x-1.5">
                                                    <input type="number" step="0.01" min="0" name="shipment_costs[dhl][given_amount]" x-model.number="carriers.dhl.given_amount" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    <button type="button" @click="applyFreightToTotal(carriers.dhl.given_amount, 'DHL')" x-show="carriers.dhl.given_amount > 0" class="text-[10px] whitespace-nowrap bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold px-1.5 py-1 rounded transition" title="Add this freight to Final Total">
                                                        + Apply
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Air Freight -->
                                        <tr class="hover:bg-blue-50/40">
                                            <td class="px-4 py-2.5 font-bold text-blue-700 flex items-center">
                                                <span class="w-2 h-2 rounded-full bg-blue-500 me-2"></span>
                                                Air Freight
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.001" min="0" name="shipment_costs[air_freight][checked_weight]" x-model.number="carriers.air_freight.checked_weight" @input="recalcCarrier('air_freight')" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][rate_per_kg]" x-model.number="carriers.air_freight.rate_per_kg" @input="recalcCarrier('air_freight')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][system_amount]" x-model.number="carriers.air_freight.system_amount" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][added_amount]" x-model.number="carriers.air_freight.added_amount" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center space-x-1.5">
                                                    <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][given_amount]" x-model.number="carriers.air_freight.given_amount" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    <button type="button" @click="applyFreightToTotal(carriers.air_freight.given_amount, 'Air')" x-show="carriers.air_freight.given_amount > 0" class="text-[10px] whitespace-nowrap bg-blue-100 hover:bg-blue-200 text-blue-900 font-bold px-1.5 py-1 rounded transition" title="Add this freight to Final Total">
                                                        + Apply
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Sea Freight -->
                                        <tr class="hover:bg-emerald-50/40">
                                            <td class="px-4 py-2.5 font-bold text-emerald-700 flex items-center">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500 me-2"></span>
                                                Sea Freight
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.001" min="0" name="shipment_costs[sea_freight][checked_weight]" x-model.number="carriers.sea_freight.checked_weight" @input="recalcCarrier('sea_freight')" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][rate_per_kg]" x-model.number="carriers.sea_freight.rate_per_kg" @input="recalcCarrier('sea_freight')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][system_amount]" x-model.number="carriers.sea_freight.system_amount" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][added_amount]" x-model.number="carriers.sea_freight.added_amount" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center space-x-1.5">
                                                    <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][given_amount]" x-model.number="carriers.sea_freight.given_amount" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    <button type="button" @click="applyFreightToTotal(carriers.sea_freight.given_amount, 'Sea')" x-show="carriers.sea_freight.given_amount > 0" class="text-[10px] whitespace-nowrap bg-emerald-100 hover:bg-emerald-200 text-emerald-900 font-bold px-1.5 py-1 rounded transition" title="Add this freight to Final Total">
                                                        + Apply
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Step 5: Final Total & Notes -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Notes / Terms / Special Instructions
                                    </label>
                                    <textarea name="notes" rows="3" placeholder="Payment terms, delivery schedule, bank details..." class="w-full text-sm rounded-lg border-gray-300"></textarea>
                                </div>
                                <div x-show="!isWeightOnly" class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 text-right space-y-2">
                                    <label class="block text-xs font-bold text-indigo-900 uppercase tracking-wider">
                                        Final Total Amount (<span x-text="currency"></span>)
                                    </label>
                                    <div class="flex items-center justify-end space-x-2">
                                        <span class="text-sm font-mono font-bold text-gray-500" x-text="currency"></span>
                                        <input type="number" step="0.01" min="0" name="final_total" x-model.number="finalTotal" class="w-48 text-right font-mono text-2xl font-black text-indigo-900 rounded-lg border-indigo-200">
                                    </div>
                                    <p class="text-[11px] text-indigo-600">Defaults to item sum. Can be adjusted for freight/discounts.</p>
                                </div>
                                <div x-show="isWeightOnly" class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-right space-y-2">
                                    <input type="hidden" name="final_total" value="0">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700">
                                        Non-Commercial / No Financial Total
                                    </span>
                                    <p class="text-xs text-gray-600 font-medium">
                                        This document (<span class="font-bold uppercase text-indigo-700" x-text="documentType"></span>) only tracks weights, dimensions, and line items without financial pricing.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Verification Checklist Panel (4 Cols, Sticky) -->
                    <div class="lg:col-span-4 sticky top-6 space-y-6">

                        <!-- Interactive Session Checklist Drawer -->
                        <div class="bg-white rounded-xl shadow-md border-2 border-indigo-100 p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-900">Pre-Creation Checklist</h4>
                                        <p class="text-[11px] text-gray-500" x-text="checklistHeading"></p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold font-mono"
                                      :class="allChecked ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                      x-text="`${checkedCount} / ${checklists.length}`">
                                </span>
                            </div>

                            <!-- Session info banner -->
                            <div class="text-[11px] bg-slate-50 text-slate-600 p-2.5 rounded-lg border border-slate-200 flex items-start space-x-2">
                                <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Checklist clicks are kept in your browser session to guide your workflow and prevent errors.</span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300"
                                     :style="`width: ${checklists.length > 0 ? (checkedCount / checklists.length) * 100 : 0}%`">
                                </div>
                            </div>

                            <!-- Real-time All Checked Celebration Banner -->
                            <div x-show="allChecked" x-transition class="p-2.5 bg-emerald-50 text-emerald-800 rounded-lg text-xs font-bold flex items-center justify-center space-x-1.5 border border-emerald-300">
                                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                <span>All Verification Steps Checked!</span>
                            </div>

                            <!-- Checklist Items List -->
                            <div class="space-y-2.5 max-h-[420px] overflow-y-auto pr-1">
                                <template x-if="checklists.length === 0">
                                    <div class="text-center py-6 text-gray-400 text-xs">
                                        Type a document number or select document type to load verification checklist.
                                    </div>
                                </template>

                                <template x-for="(item, idx) in checklists" :key="item.id || idx">
                                    <label class="flex items-start space-x-3 p-2.5 rounded-lg border cursor-pointer transition select-none"
                                           :class="isItemChecked(item.id) ? 'bg-emerald-50/60 border-emerald-200' : 'bg-white border-gray-200 hover:bg-slate-50'">
                                        <input type="checkbox"
                                               class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                               :checked="isItemChecked(item.id)"
                                               @change="toggleCheckItem(item.id)">
                                        <div class="flex-1 text-xs">
                                            <div class="font-medium text-gray-800"
                                                 :class="{'line-through text-gray-400': isItemChecked(item.id)}"
                                                 x-text="item.item_text"></div>
                                            <div class="text-[10px] text-gray-500 mt-0.5" x-text="item.hint" x-show="item.hint"></div>
                                            <span x-show="item.is_required" class="inline-block mt-1 text-[9px] uppercase font-bold text-rose-600 bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200">Required</span>
                                        </div>
                                    </label>
                                </template>
                            </div>

                            <div class="pt-2">
                                <button type="button" @click="resetChecklistSession()" class="text-[11px] text-gray-400 hover:text-gray-600 underline">
                                    Reset Checklist for this document
                                </button>
                            </div>
                        </div>

                        <!-- Action Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                            <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-sm rounded-lg shadow-sm flex items-center justify-center space-x-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Save & Create Document</span>
                            </button>
                            <a href="{{ route('documents.index') }}" class="block text-center py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition">
                                Cancel
                            </a>
                        </div>

                    </div>

                </div>
            </div>
        </form>
    </div>

    <!-- Alpine.js Document Creation & Checklist State Management -->
    <script>
        function documentCreator(initial = {}) {
            const initialItems = (initial && initial.items && initial.items.length > 0)
                ? initial.items.map(it => ({
                    item_code: it.item_code || '',
                    description: it.description || '',
                    unit_amount: parseFloat(it.unit_amount) || 1,
                    unit_price: parseFloat(it.unit_price) || 0,
                    total_amount: parseFloat(it.total_amount) || 0,
                    unit_weight: parseFloat(it.unit_weight) || 0,
                    total_weight: parseFloat(it.total_weight) || 0,
                    price_from_tracker: false
                }))
                : [
                    { item_code: '', description: '', unit_amount: 1, unit_price: 0, total_amount: 0, unit_weight: 0, total_weight: 0, price_from_tracker: false }
                ];

            const initialPackages = (initial && initial.packages && initial.packages.length > 0)
                ? initial.packages.map(p => ({
                    package_type: p.package_type || 'Carton',
                    dimension_type: p.dimension_type || 'standard',
                    length_cm: p.length_cm ?? null,
                    width_cm: p.width_cm ?? null,
                    height_cm: p.height_cm ?? null,
                    diameter_cm: p.diameter_cm ?? null,
                    quantity: parseInt(p.quantity) || 1,
                    gross_weight_per_pkg_kg: p.gross_weight_per_pkg_kg ?? null,
                    volumetric_weight_kg: parseFloat(p.volumetric_weight_kg) || 0,
                    cbm: parseFloat(p.cbm) || 0
                }))
                : [
                    {
                        package_type: 'Carton',
                        dimension_type: 'standard',
                        length_cm: null,
                        width_cm: null,
                        height_cm: null,
                        diameter_cm: null,
                        quantity: 1,
                        gross_weight_per_pkg_kg: null,
                        volumetric_weight_kg: 0,
                        cbm: 0
                    }
                ];

            return {
                sourceDocumentId: initial.source_document_id || null,
                sourceDocumentNumber: initial.source_document_number || '',
                companyName: initial.company_name || '',
                country: initial.country || '',
                address: initial.address || '',
                contactDetails: initial.contact_details || '',
                documentNumber: initial.document_number || '',
                documentType: initial.document_type || '{{ old('document_type', $targetType ?? '') }}',
                currency: initial.currency || 'USD',
                ruleMatched: '',
                isDetecting: false,
                subtotal: 0,
                finalTotal: 0,
                netWeight: initial.total_net_weight ?? null,
                grossWeight: initial.total_gross_weight ?? null,
                checklists: [],
                checkedItems: {},

                selectedPriceList: '',
                selectedPriceLabel: 'AED 30%',
                availablePriceLists: ['Price List', 'Union'],
                availablePriceLabels: ['AED 30%', 'AED 40%', 'AED 50%', 'USD 30%', 'USD 40%', 'USD 50%'],
                itemSuggestions: {},

                items: initialItems,
                packages: initialPackages,

                carriers: {
                    dhl: { checked_weight: null, rate_per_kg: null, system_amount: null, added_amount: null, given_amount: null },
                    air_freight: { checked_weight: null, rate_per_kg: null, system_amount: null, added_amount: null, given_amount: null },
                    sea_freight: { checked_weight: null, rate_per_kg: null, system_amount: null, added_amount: null, given_amount: null }
                },

                get isWeightOnly() {
                    return this.documentType === 'packing_list' || this.documentType === 'reserve';
                },

                get calculatedItemsNetWeight() {
                    return this.items.reduce((sum, it) => sum + (parseFloat(it.total_weight) || 0), 0);
                },

                syncWeightFromItems() {
                    if (this.calculatedItemsNetWeight > 0) {
                        this.netWeight = Math.round(this.calculatedItemsNetWeight * 1000) / 1000;
                    }
                },

                get totalPackagesCount() {
                    return this.packages.reduce((sum, p) => sum + (parseInt(p.quantity) || 0), 0);
                },

                get totalPackageGrossWeight() {
                    return this.packages.reduce((sum, p) => {
                        const wt = parseFloat(p.gross_weight_per_pkg_kg) || 0;
                        const qty = parseInt(p.quantity) || 1;
                        return sum + (wt * qty);
                    }, 0);
                },

                get totalVolumetricWeight() {
                    return this.packages.reduce((sum, p) => sum + (parseFloat(p.volumetric_weight_kg) || 0), 0);
                },

                get totalCbm() {
                    return this.packages.reduce((sum, p) => sum + (parseFloat(p.cbm) || 0), 0);
                },

                get chargeableWeight() {
                    const actual = parseFloat(this.grossWeight) || 0;
                    return Math.max(actual, this.totalVolumetricWeight);
                },

                addPackage() {
                    this.packages.push({
                        package_type: 'Carton',
                        dimension_type: 'standard',
                        length_cm: null,
                        width_cm: null,
                        height_cm: null,
                        diameter_cm: null,
                        quantity: 1,
                        gross_weight_per_pkg_kg: null,
                        volumetric_weight_kg: 0,
                        cbm: 0
                    });
                },

                removePackage(index) {
                    if (this.packages.length > 1) {
                        this.packages.splice(index, 1);
                        this.recalcAllCarriers();
                    }
                },

                recalcPackage(pkg) {
                    const qty = Math.max(1, parseInt(pkg.quantity) || 1);
                    const h = parseFloat(pkg.height_cm) || 0;

                    if (pkg.dimension_type === 'diameter') {
                        const dia = parseFloat(pkg.diameter_cm) || 0;
                        if (dia > 0 && h > 0) {
                            pkg.volumetric_weight_kg = Math.round(((dia * dia * h) / 5000) * qty * 1000) / 1000;
                            const r = dia / 2;
                            pkg.cbm = Math.round((Math.PI * r * r * h / 1000000) * qty * 10000) / 10000;
                        } else {
                            pkg.volumetric_weight_kg = 0;
                            pkg.cbm = 0;
                        }
                    } else {
                        const l = parseFloat(pkg.length_cm) || 0;
                        const w = parseFloat(pkg.width_cm) || 0;
                        if (l > 0 && w > 0 && h > 0) {
                            pkg.volumetric_weight_kg = Math.round(((l * w * h) / 5000) * qty * 1000) / 1000;
                            pkg.cbm = Math.round(((l * w * h) / 1000000) * qty * 10000) / 10000;
                        } else {
                            pkg.volumetric_weight_kg = 0;
                            pkg.cbm = 0;
                        }
                    }

                    this.recalcAllCarriers();
                },

                syncWeightFromPackages() {
                    if (this.totalPackageGrossWeight > 0) {
                        this.grossWeight = Math.round(this.totalPackageGrossWeight * 1000) / 1000;
                        this.recalcAllCarriers();
                    }
                },

                recalcCarrier(method) {
                    const c = this.carriers[method];
                    if (!c) return;
                    const rate = parseFloat(c.rate_per_kg);
                    if (rate > 0) {
                        const wt = parseFloat(c.checked_weight) || this.chargeableWeight;
                        c.system_amount = Math.round(wt * rate * 100) / 100;
                    }
                },

                recalcAllCarriers() {
                    ['dhl', 'air_freight', 'sea_freight'].forEach(m => this.recalcCarrier(m));
                },

                applyFreightToTotal(amount, carrier) {
                    const freight = parseFloat(amount) || 0;
                    this.finalTotal = Math.round((this.subtotal + freight) * 100) / 100;
                },

                init() {
                    this.items.forEach(it => this.recalcItem(it));
                    this.packages.forEach(p => this.recalcPackage(p));
                    this.recalcTotals();
                    this.initPriceLabels();
                    if (this.documentType) {
                        this.loadChecklistsForType(this.documentType);
                    }
                },

                async initPriceLabels() {
                    try {
                        const res = await fetch('/api/price-items/labels');
                        const data = await res.json();
                        if (data.price_labels && data.price_labels.length > 0) {
                            this.availablePriceLabels = data.price_labels;
                        }
                        if (data.price_lists && data.price_lists.length > 0) {
                            this.availablePriceLists = data.price_lists;
                        }
                    } catch (e) {
                        console.error('Failed to load price labels', e);
                    }
                },

                async onItemCodeInput(item, index) {
                    const q = item.item_code ? item.item_code.trim() : '';
                    if (q.length < 1) {
                        this.itemSuggestions[index] = [];
                        return;
                    }

                    try {
                        const params = new URLSearchParams({
                            q: q,
                            price_label: this.selectedPriceLabel || '',
                            price_list: this.selectedPriceList || ''
                        });
                        const res = await fetch(`/api/price-items/search?${params.toString()}`);
                        const data = await res.json();
                        this.itemSuggestions[index] = data.items || [];
                    } catch (e) {
                        console.error('Item suggestions fetch error', e);
                    }

                    if (!this.isWeightOnly) {
                        this.lookupItemPrice(item);
                    }
                },

                async lookupItemPrice(item) {
                    const code = item.item_code ? item.item_code.trim() : '';
                    if (!code) return;

                    try {
                        const params = new URLSearchParams({
                            item_code: code,
                            price_label: this.selectedPriceLabel || '',
                            price_list: this.selectedPriceList || ''
                        });
                        const res = await fetch(`/api/price-items/lookup?${params.toString()}`);
                        const data = await res.json();

                        if (data.found) {
                            if (data.description && !item.description) {
                                item.description = data.description;
                            }
                            if (!this.isWeightOnly && data.unit_price !== null && data.unit_price !== undefined) {
                                item.unit_price = parseFloat(data.unit_price);
                                item.price_from_tracker = true;
                                this.recalcItem(item);
                            }
                        }
                    } catch (e) {
                        console.error('Item price lookup error', e);
                    }
                },

                async repriceAllLineItems() {
                    if (this.isWeightOnly) return;
                    for (const it of this.items) {
                        if (it.item_code && it.item_code.trim()) {
                            await this.lookupItemPrice(it);
                        }
                    }
                },

                onPriceTierChanged() {
                    if (this.selectedPriceLabel && !this.isWeightOnly) {
                        this.repriceAllLineItems();
                    }
                },

                addItem() {
                    this.items.push({
                        item_code: '',
                        description: '',
                        unit_amount: 1,
                        unit_price: 0,
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                        this.recalcTotals();
                    }
                },

                recalcItem(item) {
                    const qty = parseFloat(item.unit_amount) || 0;
                    const price = parseFloat(item.unit_price) || 0;
                    item.total_amount = Math.round(qty * price * 100) / 100;
                    const unitWt = parseFloat(item.unit_weight) || 0;
                    item.total_weight = Math.round(qty * unitWt * 1000) / 1000;
                    this.recalcTotals();
                },

                recalcTotals() {
                    let sum = 0;
                    this.items.forEach(it => {
                        sum += parseFloat(it.total_amount) || 0;
                    });
                    this.subtotal = Math.round(sum * 100) / 100;
                    this.finalTotal = this.subtotal;
                    if (this.isWeightOnly && this.calculatedItemsNetWeight > 0 && !this.netWeight) {
                        this.netWeight = Math.round(this.calculatedItemsNetWeight * 1000) / 1000;
                    }
                },

                formatNumber(val) {
                    return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatWeight(val) {
                    return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 });
                },

                get checklistHeading() {
                    if (!this.documentType) return 'Awaiting Document Type';
                    return this.documentType.replace('_', ' ').toUpperCase();
                },

                get checkedCount() {
                    let count = 0;
                    this.checklists.forEach(item => {
                        if (this.checkedItems[this.sessionKey(item.id)]) {
                            count++;
                        }
                    });
                    return count;
                },

                get allChecked() {
                    return this.checklists.length > 0 && this.checkedCount === this.checklists.length;
                },

                sessionKey(itemId) {
                    const docKey = this.documentNumber ? this.documentNumber.trim().toUpperCase() : 'NEW';
                    return `chk_${docKey}_${itemId}`;
                },

                isItemChecked(itemId) {
                    return !!this.checkedItems[this.sessionKey(itemId)];
                },

                toggleCheckItem(itemId) {
                    const key = this.sessionKey(itemId);
                    const newVal = !this.checkedItems[key];
                    this.checkedItems[key] = newVal;
                    sessionStorage.setItem(key, newVal ? '1' : '0');
                },

                loadChecklistSession() {
                    this.checklists.forEach(item => {
                        const key = this.sessionKey(item.id);
                        const val = sessionStorage.getItem(key);
                        if (val === '1') {
                            this.checkedItems[key] = true;
                        }
                    });
                },

                resetChecklistSession() {
                    this.checklists.forEach(item => {
                        const key = this.sessionKey(item.id);
                        sessionStorage.removeItem(key);
                        delete this.checkedItems[key];
                    });
                },

                async detectDocumentType() {
                    const num = this.documentNumber ? this.documentNumber.trim() : '';
                    if (!num) {
                        this.ruleMatched = '';
                        return;
                    }

                    this.isDetecting = true;
                    try {
                        const response = await fetch(`/api/documents/detect?number=${encodeURIComponent(num)}`);
                        const data = await response.json();

                        if (data.detected && data.type) {
                            this.documentType = data.type;
                            this.ruleMatched = data.rule_matched;
                            this.checklists = data.checklists || [];
                            this.loadChecklistSession();
                        } else {
                            this.ruleMatched = data.rule_matched || '';
                        }
                    } catch (e) {
                        console.error('Detection error', e);
                    } finally {
                        this.isDetecting = false;
                    }
                },

                async loadChecklistsForType(type) {
                    if (!type) {
                        this.checklists = [];
                        return;
                    }
                    try {
                        const response = await fetch(`/api/checklists/${type}`);
                        const data = await response.json();
                        this.checklists = data.items || [];
                        this.loadChecklistSession();
                    } catch (e) {
                        console.error('Checklist fetch error', e);
                    }
                },

                prepareSubmit(e) {
                    // All verification checks done
                    return true;
                }
            };
        }
    </script>
</x-app-layout>
