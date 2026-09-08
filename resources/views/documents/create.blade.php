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
        $shipmentCosts = $sourceDoc?->shipmentCosts?->keyBy('method');
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
            'sourceInput' => $sourceDoc?->document_number ?? '',
            'importMessage' => $sourceDoc ? "Loaded initial items, company & charges from {$sourceDoc->document_number}" : '',
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
            'carriers' => [
                'dhl' => [
                    'checked_weight' => $shipmentCosts?->get('dhl')?->checked_weight !== null ? (float) $shipmentCosts->get('dhl')->checked_weight : null,
                    'rate_per_kg' => $shipmentCosts?->get('dhl')?->rate_per_kg !== null ? (float) $shipmentCosts->get('dhl')->rate_per_kg : null,
                    'system_amount' => $shipmentCosts?->get('dhl')?->system_amount !== null ? (float) $shipmentCosts->get('dhl')->system_amount : null,
                    'added_amount' => $shipmentCosts?->get('dhl')?->added_amount !== null ? (float) $shipmentCosts->get('dhl')->added_amount : null,
                    'given_amount' => $shipmentCosts?->get('dhl')?->given_amount !== null ? (float) $shipmentCosts->get('dhl')->given_amount : null,
                ],
                'air_freight' => [
                    'checked_weight' => $shipmentCosts?->get('air_freight')?->checked_weight !== null ? (float) $shipmentCosts->get('air_freight')->checked_weight : null,
                    'rate_per_kg' => $shipmentCosts?->get('air_freight')?->rate_per_kg !== null ? (float) $shipmentCosts->get('air_freight')->rate_per_kg : null,
                    'system_amount' => $shipmentCosts?->get('air_freight')?->system_amount !== null ? (float) $shipmentCosts->get('air_freight')->system_amount : null,
                    'added_amount' => $shipmentCosts?->get('air_freight')?->added_amount !== null ? (float) $shipmentCosts->get('air_freight')->added_amount : null,
                    'given_amount' => $shipmentCosts?->get('air_freight')?->given_amount !== null ? (float) $shipmentCosts->get('air_freight')->given_amount : null,
                ],
                'sea_freight' => [
                    'checked_weight' => $shipmentCosts?->get('sea_freight')?->checked_weight !== null ? (float) $shipmentCosts->get('sea_freight')->checked_weight : null,
                    'rate_per_kg' => $shipmentCosts?->get('sea_freight')?->rate_per_kg !== null ? (float) $shipmentCosts->get('sea_freight')->rate_per_kg : null,
                    'system_amount' => $shipmentCosts?->get('sea_freight')?->system_amount !== null ? (float) $shipmentCosts->get('sea_freight')->system_amount : null,
                    'added_amount' => $shipmentCosts?->get('sea_freight')?->added_amount !== null ? (float) $shipmentCosts->get('sea_freight')->added_amount : null,
                    'given_amount' => $shipmentCosts?->get('sea_freight')?->given_amount !== null ? (float) $shipmentCosts->get('sea_freight')->given_amount : null,
                ],
            ],
        ];
    @endphp

    <div class="py-8" x-data="documentCreator(@js($initialData))">
        <form method="POST" action="{{ route('documents.store') }}" @submit="prepareSubmit($event)">
            @csrf

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Unsaved Draft Recovery Banner -->
                <div x-show="hasDraft" x-cloak x-transition class="mb-6 bg-gradient-to-r from-amber-50 via-indigo-50/40 to-amber-50 border border-amber-300/80 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 leading-tight">Unsaved Local Draft Available</h4>
                            <p class="text-xs text-gray-600 mt-0.5">
                                We found an auto-saved draft from <strong class="text-amber-800" x-text="draftSavedAt"></strong>. Would you like to restore your work?
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" @click="restoreDraft()" class="inline-flex items-center px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Restore Draft
                        </button>
                        <button type="button" @click="discardDraft()" class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-xl text-xs font-semibold shadow-2xs transition">
                            Discard
                        </button>
                    </div>
                </div>

                <!-- Auto-save Status Badge (when auto-saved) -->
                <div x-show="lastAutoSavedAt" x-cloak class="flex items-center justify-end mb-3">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium bg-white text-gray-500 border border-gray-200 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 me-1.5 animate-pulse"></span>
                        Draft auto-saved locally at <strong class="ml-1 font-mono text-gray-700" x-text="lastAutoSavedAt"></strong>
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- Main Document Details Form (8 Cols) -->
                    <div class="lg:col-span-8 min-w-0 space-y-6">

                        <!-- Step 0: Import from Source Document (PI / Previous Document) -->
                        <div class="bg-gradient-to-r from-indigo-50/70 via-purple-50/50 to-white rounded-xl shadow-sm border border-indigo-100 p-5 space-y-3">
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
                                            Select or type a source document code (e.g. <span class="font-mono font-bold text-indigo-700">E26211</span>) to import company details, shipment charges, items, and packaging.
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
                                    <span>Import Details & Charges</span>
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

                        <!-- Non-Admin Checklist-First Collapsible Control -->
                        <div class="p-4 rounded-xl border {{ Auth::user()->isAdmin() ? 'bg-slate-50 border-slate-200' : 'bg-gradient-to-r from-indigo-50/90 to-purple-50/80 border-indigo-200' }} flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg {{ Auth::user()->isAdmin() ? 'bg-slate-200 text-slate-700' : 'bg-indigo-600 text-white' }} flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-gray-900">
                                        {{ Auth::user()->isAdmin() ? 'Extended Document Details (Steps 2 to 6)' : 'Checklist Verification Focus Mode' }}
                                    </div>
                                    <p class="text-[11px] text-gray-600">
                                        {{ Auth::user()->isAdmin() ? 'All input sections are visible. Toggle to collapse extended form fields.' : 'Checklist is shown prominently by default. Toggle below to display complete document items and charges.' }}
                                    </p>
                                </div>
                            </div>
                            <button type="button"
                                    @click="showFullForm = !showFullForm"
                                    class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-bold transition shadow-2xs {{ Auth::user()->isAdmin() ? 'bg-white hover:bg-slate-100 text-gray-700 border border-gray-300' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }}">
                                <span x-text="showFullForm ? 'Hide Extended Form Fields ▲' : 'Show Complete Document Form ▼'"></span>
                            </button>
                        </div>

                        <!-- Collapsible Container for Steps 2 to 6 -->
                        <div x-show="showFullForm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">

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
                                        <span x-text="isWeightOnly ? (documentType === 'delivery_note' ? 'Delivery Note & Weight Breakdown' : (documentType === 'reserve' ? 'Warehouse Reserve & Weight Breakdown' : 'Packing List & Weight Breakdown')) : 'Document Line Items & Pricing'"></span>
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="isWeightOnly ? 'Item code, description, quantity, unit net weight (kg), and calculated total net weight. Prices are omitted for weight-focused documents (packing lists, reserves, and delivery notes).' : 'Item code, description, quantity, unit price, discounts (-) and additions (+).'"></p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" @click="addItem()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Add Line Item
                                    </button>
                                    <button type="button" x-show="!isWeightOnly" @click="addDiscount()" class="inline-flex items-center px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-xs font-bold transition" title="Add a discount line (% or fixed minus from total)">
                                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                        Add Discount (-)
                                    </button>
                                    <button type="button" x-show="!isWeightOnly" @click="addTax()" class="inline-flex items-center px-3 py-1.5 bg-amber-50 text-amber-800 hover:bg-amber-100 rounded-lg text-xs font-bold transition" title="Add VAT or tax line (% or fixed plus to total)">
                                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Add Tax / VAT (+)
                                    </button>
                                    <button type="button" x-show="!isWeightOnly" @click="addAddition()" class="inline-flex items-center px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold transition" title="Add extra charge, freight, or surcharge line (plus to total)">
                                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Add Addition (+)
                                    </button>
                                </div>
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
                                <table x-ref="itemsTable" class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-2 py-2.5 text-center w-12 text-gray-400">#</th>
                                            <th class="px-3 py-2.5 text-left w-44">Item / Record Code</th>
                                            <th class="px-3 py-2.5 text-left min-w-[180px]">Description</th>
                                            <th class="px-3 py-2.5 text-right w-24">Quantity</th>
                                            <!-- Financial headers -->
                                            <th x-show="!isWeightOnly" class="px-3 py-2.5 text-right w-48">Unit Price (<span x-text="currency"></span>)</th>
                                            <th x-show="!isWeightOnly" class="px-3 py-2.5 text-right w-32">Total Amount</th>
                                            <!-- Weight-only headers -->
                                            <th x-show="isWeightOnly" class="px-3 py-2.5 text-right w-28">Unit Net Wt (kg)</th>
                                            <th x-show="isWeightOnly" class="px-3 py-2.5 text-right w-32">Total Net Wt (kg)</th>
                                            <th class="sticky right-0 z-20 bg-gray-50 px-2 py-2.5 text-center w-24 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr class="hover:bg-slate-50 group transition duration-150"
                                                :class="{
                                                    'bg-rose-50/40': item.type === 'discount' || item.total_amount < 0,
                                                    'bg-amber-50/30': item.type === 'tax' || ['TAX', 'VAT'].includes((item.item_code || '').toUpperCase()),
                                                    'bg-emerald-50/30': item.type === 'addition',
                                                    'opacity-40 bg-indigo-50 border-2 border-dashed border-indigo-400': draggedRowIndex === index,
                                                    'border-t-2 border-indigo-500 bg-indigo-50/40': dragOverRowIndex === index && draggedRowIndex !== index
                                                }"
                                                @dragover.prevent="onRowDragOver($event, index)"
                                                @dragleave="onRowDragLeave($event, index)"
                                                @drop.prevent="onRowDrop($event, index)">
                                                <td class="px-1 py-2 text-center align-middle text-gray-400 select-none">
                                                    <div class="flex items-center justify-center space-x-1">
                                                        <span class="cursor-grab active:cursor-grabbing text-gray-400 hover:text-indigo-600 p-0.5 rounded transition"
                                                              draggable="true"
                                                              @dragstart="onRowDragStart($event, index)"
                                                              @dragend="onRowDragEnd()"
                                                              title="Drag to reorder row">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                            </svg>
                                                        </span>
                                                        <span class="text-[10px] font-mono text-gray-500 font-bold" x-text="index + 1"></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 align-middle">
                                                    <div class="relative flex items-center">
                                                        <input type="text"
                                                               :name="`items[${index}][item_code]`"
                                                               x-model="item.item_code"
                                                               :list="`item-datalist-${index}`"
                                                               @input.debounce.250ms="onItemCodeInput(item, index)"
                                                               @change="lookupItemPrice(item)"
                                                               @keydown="handleTableKeyNav($event, index, 0)"
                                                               @paste="handleItemCodePaste($event, index)"
                                                               data-grid-item="true"
                                                               :data-grid-row="index"
                                                               data-grid-col="0"
                                                               :placeholder="item.type === 'discount' ? 'DISCOUNT' : (item.type === 'tax' ? 'TAX' : (item.type === 'addition' ? 'ADDITION' : 'SKU-101'))"
                                                               autocomplete="off"
                                                               autocorrect="off"
                                                               autocapitalize="off"
                                                               spellcheck="false"
                                                               data-lpignore="true"
                                                               required
                                                               class="w-full text-xs font-mono font-semibold rounded border-gray-300 py-1.5 px-2.5 transition"
                                                               :class="{
                                                                   'font-bold text-rose-700 bg-rose-50 border-rose-300': item.type === 'discount' || item.total_amount < 0,
                                                                   'font-bold text-amber-800 bg-amber-50 border-amber-300': item.type === 'tax' || ['TAX', 'VAT'].includes((item.item_code || '').toUpperCase()),
                                                                   'font-bold text-emerald-700 bg-emerald-50 border-emerald-300': item.type === 'addition' || (item.item_code || '').toUpperCase() === 'ADDITION'
                                                               }">
                                                        <datalist :id="`item-datalist-${index}`">
                                                            <template x-for="sug in (itemSuggestions[index] || [])" :key="sug.item_code">
                                                                <option :value="sug.item_code" :label="`${sug.item_code} - ${sug.description} (${sug.currency || ''} ${sug.unit_price || ''})`"></option>
                                                            </template>
                                                        </datalist>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 align-middle">
                                                    <input type="text"
                                                           :name="`items[${index}][description]`"
                                                           x-model="item.description"
                                                           @keydown="handleTableKeyNav($event, index, 1)"
                                                           data-grid-item="true"
                                                           :data-grid-row="index"
                                                           data-grid-col="1"
                                                           autocomplete="off"
                                                           autocorrect="off"
                                                           autocapitalize="off"
                                                           spellcheck="false"
                                                           data-lpignore="true"
                                                           :placeholder="item.type === 'discount' ? 'e.g. Special client discount (10%)' : (item.type === 'tax' ? 'e.g. VAT / Tax (5%)' : (item.type === 'addition' ? 'e.g. Freight charge, packing fee' : 'Item description / specs'))"
                                                           class="w-full text-xs rounded border-gray-300 py-1.5 px-2">
                                                </td>
                                                <td class="px-3 py-2 align-middle">
                                                    <template x-if="!isAdjustment(item)">
                                                        <input type="text"
                                                               inputmode="decimal"
                                                               :name="`items[${index}][unit_amount]`"
                                                               x-model="item.unit_amount"
                                                               @input="onQuantityInput(item)"
                                                               @focus="$event.target.select()"
                                                               @keydown="handleTableKeyNav($event, index, 2)"
                                                               @paste="handleQuantityPaste($event, index)"
                                                               data-grid-item="true"
                                                               :data-grid-row="index"
                                                               data-grid-col="2"
                                                               autocomplete="off"
                                                               autocorrect="off"
                                                               autocapitalize="off"
                                                               spellcheck="false"
                                                               data-lpignore="true"
                                                               data-1p-ignore="true"
                                                               placeholder="Qty"
                                                               class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    </template>
                                                    <template x-if="isAdjustment(item)">
                                                        <div class="flex items-center justify-center py-1.5" title="Quantity not applicable for adjustments">
                                                            <input type="hidden" :name="`items[${index}][unit_amount]`" value="1">
                                                            <span class="text-gray-400 font-mono font-bold text-xs select-none">—</span>
                                                        </div>
                                                    </template>
                                                </td>
                                                <!-- Financial mode inputs -->
                                                <td x-show="!isWeightOnly" class="px-3 py-2 align-middle">
                                                    <!-- Regular Line Item Unit Price with Safe Lock & Edit Icon -->
                                                    <template x-if="!isAdjustment(item)">
                                                        <div class="relative flex items-center">
                                                            <input type="number"
                                                                   step="0.01"
                                                                   :name="`items[${index}][unit_price]`"
                                                                   x-model="item.unit_price"
                                                                   @input="recalcItem(item)"
                                                                   @focus="if (item.price_editable) $event.target.select()"
                                                                   @keydown="handleTableKeyNav($event, index, 3)"
                                                                   data-grid-item="true"
                                                                   :data-grid-row="index"
                                                                   data-grid-col="3"
                                                                   autocomplete="off"
                                                                   autocorrect="off"
                                                                   autocapitalize="off"
                                                                   spellcheck="false"
                                                                   data-lpignore="true"
                                                                   :readonly="!item.price_editable"
                                                                   placeholder="0.00"
                                                                   :required="!isWeightOnly"
                                                                   class="w-full text-xs font-mono text-right rounded py-1.5 pl-2 pr-14 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none transition"
                                                                   :class="!item.price_editable ? 'bg-slate-100/80 text-slate-700 cursor-not-allowed border-gray-200 select-all' : 'bg-white text-gray-900 font-bold border-indigo-500 ring-2 ring-indigo-500/20 shadow-xs'"
                                                                   :ref="`priceInput_${index}`">

                                                            <!-- Price Tracker Indicator Dot -->
                                                            <span x-show="item.price_from_tracker" x-cloak class="absolute -top-1 -right-1 flex h-2 w-2 pointer-events-none" title="Price loaded from Item Price Tracker">
                                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                                            </span>

                                                            <!-- Inline Action Buttons (Never go under the input) -->
                                                            <div class="absolute right-1 flex items-center space-x-0.5">
                                                                <!-- % Discount Button -->
                                                                <button type="button"
                                                                        x-show="parseFloat(item.unit_price) > 0"
                                                                        @click="applyLineDiscount(item)"
                                                                        class="px-1.5 py-0.5 text-[10px] font-bold rounded text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 transition"
                                                                        title="Apply % discount to this unit price">
                                                                    -%
                                                                </button>

                                                                <!-- Edit / Lock Button to customize price safely -->
                                                                <button type="button"
                                                                        @click="togglePriceEdit(item, index)"
                                                                        class="p-1 rounded text-gray-400 hover:text-indigo-600 hover:bg-gray-200/60 transition"
                                                                        :class="item.price_editable ? 'text-indigo-600 bg-indigo-50 ring-1 ring-indigo-300' : 'text-gray-400'"
                                                                        :title="item.price_editable ? 'Price unlocked (Click to lock)' : 'Price locked to prevent mistakes (Click to edit unit price)'">
                                                                    <template x-if="!item.price_editable">
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                                        </svg>
                                                                    </template>
                                                                    <template x-if="item.price_editable">
                                                                        <svg class="w-3.5 h-3.5 text-emerald-600 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                                        </svg>
                                                                    </template>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <!-- Adjustment / Discount / Tax Unit Price & Percentage Mode -->
                                                    <template x-if="isAdjustment(item)">
                                                        <div>
                                                            <!-- Fixed Input Mode -->
                                                            <div x-show="item.calc_mode !== 'percentage'" class="flex items-center space-x-1">
                                                                <button type="button"
                                                                        @click="setCalcMode(item, 'percentage')"
                                                                        class="px-1.5 py-1 rounded text-[10px] font-bold bg-gray-100 hover:bg-gray-200 text-gray-600 transition shrink-0"
                                                                        title="Switch to % percentage mode">
                                                                    %
                                                                </button>
                                                                <input type="number"
                                                                       step="0.01"
                                                                       :name="`items[${index}][unit_price]`"
                                                                       x-model="item.unit_price"
                                                                       @input="recalcItem(item)"
                                                                       @keydown="handleTableKeyNav($event, index, 3)"
                                                                       data-grid-item="true"
                                                                       :data-grid-row="index"
                                                                       data-grid-col="3"
                                                                       autocomplete="off"
                                                                       autocorrect="off"
                                                                       autocapitalize="off"
                                                                       spellcheck="false"
                                                                       data-lpignore="true"
                                                                       placeholder="0.00"
                                                                       class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                                       :class="item.type === 'discount' || item.total_amount < 0 ? 'text-rose-600 font-bold' : (item.type === 'tax' ? 'text-amber-700 font-bold' : 'text-emerald-700 font-bold')">
                                                            </div>

                                                            <!-- Percentage Input Mode -->
                                                            <div x-show="item.calc_mode === 'percentage'" class="flex items-center space-x-1">
                                                                <button type="button"
                                                                        @click="setCalcMode(item, 'fixed')"
                                                                        class="px-1.5 py-1 rounded text-[10px] font-bold bg-gray-100 hover:bg-gray-200 text-gray-600 transition shrink-0"
                                                                        title="Switch to fixed amount mode">
                                                                    <span x-text="currency === 'AED' ? 'AED' : '$'"></span>
                                                                </button>
                                                                <div class="relative flex items-center flex-1">
                                                                    <input type="number"
                                                                           step="any"
                                                                           min="0"
                                                                           max="100"
                                                                           x-model.number="item.percentage"
                                                                           @input="recalcItem(item)"
                                                                           @keydown="handleTableKeyNav($event, index, 3)"
                                                                           data-grid-item="true"
                                                                           :data-grid-row="index"
                                                                           data-grid-col="3"
                                                                           autocomplete="off"
                                                                           autocorrect="off"
                                                                           autocapitalize="off"
                                                                           spellcheck="false"
                                                                           data-lpignore="true"
                                                                           placeholder="0.0"
                                                                           class="w-full text-xs font-mono font-bold text-right rounded border-gray-300 py-1.5 pl-2 pr-6 focus:ring-indigo-500 focus:border-indigo-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                                    <span class="absolute right-2 text-xs font-black text-gray-500 pointer-events-none">%</span>
                                                                </div>
                                                                <input type="hidden" :name="`items[${index}][unit_price]`" :value="item.unit_price">
                                                            </div>
                                                        </div>
                                                    </template>
                                                </td>
                                                <td x-show="!isWeightOnly" class="px-3 py-2 align-middle text-right font-mono font-bold" :class="item.total_amount < 0 ? 'text-rose-600' : (item.type === 'tax' ? 'text-amber-700' : 'text-gray-800')">
                                                    <span x-text="currency"></span> <span x-text="item.total_amount < 0 ? `-${formatNumber(Math.abs(item.total_amount))}` : formatNumber(item.total_amount)"></span>
                                                </td>
                                                <!-- Weight-only mode inputs -->
                                                <template x-if="isWeightOnly">
                                                    <input type="hidden" :name="`items[${index}][unit_price]`" value="0">
                                                </template>
                                                <td x-show="isWeightOnly" class="px-3 py-2 align-middle">
                                                    <input type="number"
                                                           step="0.001"
                                                           :name="`items[${index}][unit_weight]`"
                                                           x-model.number="item.unit_weight"
                                                           @input="recalcItem(item)"
                                                           @keydown="handleTableKeyNav($event, index, 3)"
                                                           data-grid-item="true"
                                                           :data-grid-row="index"
                                                           data-grid-col="3"
                                                           autocomplete="off"
                                                           autocorrect="off"
                                                           autocapitalize="off"
                                                           spellcheck="false"
                                                           data-lpignore="true"
                                                           placeholder="0.000"
                                                           class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                </td>
                                                <td x-show="isWeightOnly" class="px-3 py-2 align-middle text-right font-mono font-bold text-gray-800">
                                                    <input type="hidden" :name="`items[${index}][total_weight]`" :value="item.total_weight">
                                                    <span x-text="formatWeight(item.total_weight)"></span> kg
                                                </td>
                                                <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-2 py-2 align-middle text-center shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100 whitespace-nowrap">
                                                    <div class="flex items-center justify-center space-x-0.5">
                                                        <button type="button"
                                                                @click="insertItemAfter(index)"
                                                                class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded p-1 transition"
                                                                title="Insert new row below this item">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                                            </svg>
                                                        </button>
                                                        <button type="button"
                                                                @click="moveItemUp(index)"
                                                                :disabled="index === 0"
                                                                class="text-gray-400 hover:text-gray-700 disabled:opacity-20 disabled:pointer-events-none p-1 transition rounded hover:bg-gray-100"
                                                                title="Move row up">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                            </svg>
                                                        </button>
                                                        <button type="button"
                                                                @click="moveItemDown(index)"
                                                                :disabled="index === items.length - 1"
                                                                class="text-gray-400 hover:text-gray-700 disabled:opacity-20 disabled:pointer-events-none p-1 transition rounded hover:bg-gray-100"
                                                                title="Move row down">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                            </svg>
                                                        </button>
                                                        <button type="button"
                                                                @click="removeItem(index)"
                                                                x-show="items.length > 1"
                                                                class="text-red-400 hover:text-red-600 hover:bg-red-50 rounded p-1 transition"
                                                                title="Remove row">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="bg-slate-50 font-bold border-t-2 border-gray-200 text-xs">
                                        <tr>
                                            <td colspan="3" class="px-3 py-2.5 text-right uppercase text-gray-500 font-semibold tracking-wider">
                                                Total Quantity:
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-mono font-black text-indigo-700 text-sm">
                                                <span x-text="formattedTotalQuantity"></span>
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-mono text-gray-400 text-xs">—</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-black text-sm text-gray-900">
                                                <span x-show="!isWeightOnly"><span x-text="currency"></span> <span x-text="formatNumber(subtotal)"></span></span>
                                                <span x-show="isWeightOnly"><span x-text="formatWeight(calculatedItemsNetWeight)"></span> kg</span>
                                            </td>
                                            <td class="sticky right-0 z-20 bg-slate-50 px-2 py-2.5 border-l border-gray-200"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Bottom Items Action Bar -->
                            <div class="px-5 py-3 bg-slate-50 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" @click="addItem()" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition">
                                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Add Line Item
                                    </button>
                                    <button type="button" @click="openBulkPasteModal('add_items')" class="inline-flex items-center px-3.5 py-2 bg-slate-800 hover:bg-slate-900 active:bg-black text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition" title="Paste multiple items or quantities at once">
                                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                        Bulk Paste Items / Qty
                                    </button>
                                    <button type="button" x-show="!isWeightOnly" @click="addDiscount()" class="inline-flex items-center px-3 py-2 bg-white hover:bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs font-bold transition shadow-2xs" title="Add a discount line (% or fixed minus from total)">
                                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                        Add Discount (-)
                                    </button>
                                    <button type="button" x-show="!isWeightOnly" @click="addTax()" class="inline-flex items-center px-3 py-2 bg-white hover:bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-bold transition shadow-2xs" title="Add VAT or tax line (% or fixed plus to total)">
                                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Add Tax / VAT (+)
                                    </button>
                                    <button type="button" x-show="!isWeightOnly" @click="addAddition()" class="inline-flex items-center px-3 py-2 bg-white hover:bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs font-bold transition shadow-2xs" title="Add extra charge, freight, or surcharge line (plus to total)">
                                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Add Addition (+)
                                    </button>
                                </div>
                                <div class="text-xs text-gray-500 font-medium flex items-center space-x-2">
                                    <span><strong class="text-gray-700" x-text="items.length"></strong> line item(s) in document</span>
                                    <span>&bull;</span>
                                    <span>Total Quantity: <strong class="text-indigo-700 font-mono font-bold" x-text="`${formattedTotalQuantity} units`"></strong></span>
                                </div>
                            </div>

                            <!-- Weights & Subtotal Bar with Live Weight Check -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-gray-100 bg-slate-50 p-4 rounded-lg">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Total Quantity
                                    </label>
                                    <div class="h-10 px-3 bg-white border border-gray-300 rounded-lg flex items-center justify-between font-mono font-black text-indigo-700 text-sm">
                                        <span x-text="formattedTotalQuantity"></span>
                                        <span class="text-xs text-gray-400 font-sans font-normal">units</span>
                                    </div>
                                </div>
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
                                    <div x-show="!isWeightOnly" class="space-y-1">
                                        <div x-show="discountsTotal > 0 || taxesTotal > 0 || additionsTotal > 0" class="text-[11px] text-gray-500 space-y-0.5 border-b border-gray-200 pb-1.5 mb-1">
                                            <div class="flex items-center justify-end space-x-2">
                                                <span>Items Subtotal:</span>
                                                <span class="font-mono font-bold text-gray-800"><span x-text="currency"></span> <span x-text="formatNumber(itemsBaseTotal)"></span></span>
                                            </div>
                                            <div x-show="discountsTotal > 0" class="flex items-center justify-end space-x-2 text-rose-600">
                                                <span>Discounts:</span>
                                                <span class="font-mono font-bold">-<span x-text="currency"></span> <span x-text="formatNumber(discountsTotal)"></span></span>
                                            </div>
                                            <div x-show="taxesTotal > 0" class="flex items-center justify-end space-x-2 text-amber-700">
                                                <span>Tax / VAT:</span>
                                                <span class="font-mono font-bold">+<span x-text="currency"></span> <span x-text="formatNumber(taxesTotal)"></span></span>
                                            </div>
                                            <div x-show="additionsTotal > 0" class="flex items-center justify-end space-x-2 text-emerald-700">
                                                <span>Additions:</span>
                                                <span class="font-mono font-bold">+<span x-text="currency"></span> <span x-text="formatNumber(additionsTotal)"></span></span>
                                            </div>
                                        </div>
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

                        <!-- Bulk Paste Items & Quantities Modal -->
                        <div x-show="bulkPasteModalOpen"
                             x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto"
                             aria-labelledby="modal-title"
                             role="dialog"
                             aria-modal="true">
                            <!-- Backdrop with blur -->
                            <div x-show="bulkPasteModalOpen"
                                 x-transition:enter="ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                                 @click="bulkPasteModalOpen = false"></div>

                            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                                <div x-show="bulkPasteModalOpen"
                                     x-transition:enter="ease-out duration-200"
                                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="ease-in duration-150"
                                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-gray-200">

                                    <!-- Modal Header -->
                                    <div class="px-6 py-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <div class="p-2 bg-white/10 rounded-xl">
                                                <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-black tracking-tight" id="modal-title">Bulk Paste Items & Quantities</h3>
                                                <p class="text-xs text-slate-300">Paste comma-separated or newline-separated lists from Excel, ERP, or chat</p>
                                            </div>
                                        </div>
                                        <button type="button" @click="bulkPasteModalOpen = false" class="text-slate-400 hover:text-white transition p-1 rounded-lg">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>

                                    <!-- Tab Switcher -->
                                    <div class="flex border-b border-gray-200 bg-gray-50/80 px-6 pt-3 gap-2">
                                        <button type="button"
                                                @click="bulkPasteTab = 'add_items'"
                                                class="pb-2.5 px-3 text-xs font-bold border-b-2 transition flex items-center gap-1.5"
                                                :class="bulkPasteTab === 'add_items' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                            <span>➕ Add New Items (+ Quantities)</span>
                                        </button>
                                        <button type="button"
                                                @click="bulkPasteTab = 'update_quantities'"
                                                class="pb-2.5 px-3 text-xs font-bold border-b-2 transition flex items-center gap-1.5"
                                                :class="bulkPasteTab === 'update_quantities' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                            <span>🔢 Update Quantities for Existing Items</span>
                                            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-700 font-mono font-bold" x-text="items.filter(it => !isAdjustment(it)).length"></span>
                                        </button>
                                    </div>

                                    <!-- Modal Body -->
                                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                                        <!-- TAB 1: ADD NEW ITEMS -->
                                        <div x-show="bulkPasteTab === 'add_items'" class="space-y-4">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <!-- Item Codes Textarea -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                            Item Codes <span class="text-rose-500">*</span>
                                                        </label>
                                                        <span class="text-[11px] text-gray-400 font-mono" x-text="`${bulkPastePreviewItems.length} code(s)`"></span>
                                                    </div>
                                                    <textarea x-model="bulkPasteItemsText"
                                                              rows="6"
                                                              placeholder="Paste item codes separated by comma or new line:&#10;SKU-001, SKU-002, SKU-003&#10;or&#10;SKU-001&#10;SKU-002&#10;or Excel 2-column data"
                                                              class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 shadow-2xs"></textarea>
                                                    <p class="mt-1 text-[11px] text-gray-500">Supports: Comma (<kbd>,</kbd>), Enter/Newline, or Tabs</p>
                                                </div>

                                                <!-- Quantities Textarea -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                            Quantities <span class="text-gray-400 font-normal text-[11px]">(Optional)</span>
                                                        </label>
                                                        <span class="text-[11px] text-gray-400 font-mono" x-text="`${parseDelimitedList(bulkPasteQuantitiesText).length} qty(s)`"></span>
                                                    </div>
                                                    <textarea x-model="bulkPasteQuantitiesText"
                                                              rows="6"
                                                              placeholder="Paste quantities corresponding to items:&#10;10, 25, 50&#10;or&#10;10&#10;25&#10;50"
                                                              class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 shadow-2xs"></textarea>
                                                    <p class="mt-1 text-[11px] text-gray-500">1st qty matches 1st item, 2nd matches 2nd, etc.</p>
                                                </div>
                                            </div>

                                            <!-- Live Match Preview -->
                                            <div x-show="bulkPastePreviewItems.length > 0" class="border border-indigo-100 bg-indigo-50/40 rounded-xl p-3">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        Matched Items Preview
                                                    </span>
                                                    <span class="text-xs text-indigo-700 font-semibold" x-text="`${bulkPastePreviewItems.length} item(s) to add`"></span>
                                                </div>
                                                <div class="max-h-40 overflow-y-auto border border-indigo-100 rounded-lg bg-white divide-y divide-gray-100">
                                                    <template x-for="(pv, idx) in bulkPastePreviewItems" :key="idx">
                                                        <div class="px-3 py-1.5 flex items-center justify-between text-xs font-mono">
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-gray-400 text-[11px]" x-text="`#${idx + 1}`"></span>
                                                                <span class="font-bold text-gray-800" x-text="pv.code"></span>
                                                            </div>
                                                            <div class="flex items-center space-x-1.5">
                                                                <span class="text-gray-500 text-[11px]">Qty:</span>
                                                                <span class="font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-800" x-text="pv.qty !== '' ? pv.qty : '—'"></span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB 2: UPDATE EXISTING ITEMS' QUANTITIES -->
                                        <div x-show="bulkPasteTab === 'update_quantities'" class="space-y-4">
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                        Quantities List <span class="text-rose-500">*</span>
                                                    </label>
                                                    <span class="text-[11px] text-gray-400 font-mono" x-text="`${parseDelimitedList(bulkPasteQuantitiesText).length} qty(s)`"></span>
                                                </div>
                                                <textarea x-model="bulkPasteQuantitiesText"
                                                          rows="5"
                                                          placeholder="Paste quantity list separated by comma or new line:&#10;5, 12, 30, 8, 14&#10;or&#10;5&#10;12&#10;30"
                                                          class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 shadow-2xs"></textarea>
                                                <p class="mt-1 text-[11px] text-gray-500">Quantities will be assigned sequentially to the existing line items in the table below.</p>
                                            </div>

                                            <!-- Target Mapping Preview -->
                                            <div class="border border-gray-200 bg-slate-50/60 rounded-xl p-3">
                                                <span class="text-xs font-bold text-gray-700 uppercase tracking-wider block mb-2">
                                                    Existing Items & New Quantity Mapping
                                                </span>
                                                <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg bg-white divide-y divide-gray-100">
                                                    <template x-for="(it, idx) in items.filter(x => !isAdjustment(x))" :key="idx">
                                                        <div class="px-3 py-1.5 flex items-center justify-between text-xs font-mono">
                                                            <div class="flex items-center space-x-2 truncate pr-2">
                                                                <span class="text-gray-400 text-[11px]" x-text="`#${idx + 1}`"></span>
                                                                <span class="font-bold text-gray-800" x-text="it.item_code || '(No Code)'"></span>
                                                                <span class="text-gray-500 truncate max-w-[180px]" x-text="it.description"></span>
                                                            </div>
                                                            <div class="flex items-center space-x-2 shrink-0">
                                                                <span class="text-gray-400 text-[11px]">Current: <span x-text="it.unit_amount || '—'"></span></span>
                                                                <span>→</span>
                                                                <span class="font-bold px-2 py-0.5 rounded text-xs"
                                                                      :class="bulkPasteQtyForIndex(idx) !== null ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-400'"
                                                                      x-text="bulkPasteQtyForIndex(idx) !== null ? bulkPasteQtyForIndex(idx) : 'No change'">
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Footer -->
                                    <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                                        <button type="button" @click="bulkPasteModalOpen = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 transition">
                                            Cancel
                                        </button>
                                        <div class="flex items-center space-x-2">
                                            <button type="button"
                                                    x-show="bulkPasteTab === 'add_items'"
                                                    @click="applyBulkAddItems()"
                                                    :disabled="bulkPastePreviewItems.length === 0"
                                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition">
                                                Add <span x-text="bulkPastePreviewItems.length"></span> Item(s) to Document
                                            </button>
                                            <button type="button"
                                                    x-show="bulkPasteTab === 'update_quantities'"
                                                    @click="applyBulkUpdateQuantities()"
                                                    :disabled="parseDelimitedList(bulkPasteQuantitiesText).length === 0"
                                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition">
                                                Update Quantities
                                            </button>
                                        </div>
                                    </div>
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
                                                    <input type="number" min="1" :name="`packages[${pIndex}][quantity]`" x-model.number="pkg.quantity" @input="recalcPackage(pkg)" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true" class="w-full text-xs font-mono font-bold text-right rounded border-gray-300 py-1.5 px-2">
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

                        <!-- Step 5: Shipment Method Costs (DHL, Air freight, Sea freight) with Rate / kg (Hidden for Packing List and Reserve) -->
                        <div x-show="!isWeightOnly" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <input type="hidden" name="selected_shipment_method" :value="selectedCarrier">
                            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">5</span>
                                        Shipment Method Costs Comparison & Rate / KG
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Track air freight or DHL rate per kg ($/kg or AED/kg). Click "+ Apply" to include a shipping carrier in the Final Total.
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
                                            <th class="px-3 py-2.5 text-right w-40">Given Amount & Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <!-- DHL -->
                                        <tr class="hover:bg-amber-50/40" :class="selectedCarrier === 'dhl' ? 'bg-amber-50/60' : ''">
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
                                                    <input type="number" step="0.01" min="0" name="shipment_costs[dhl][given_amount]" x-model.number="carriers.dhl.given_amount" @input="recalcCarrier('dhl')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    <button type="button" @click="toggleCarrierFreight('dhl')" :class="selectedCarrier === 'dhl' ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' : 'bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300'" class="text-[10px] whitespace-nowrap font-bold px-2 py-1 rounded transition cursor-pointer" :title="selectedCarrier === 'dhl' ? 'Freight included in total. Click to remove.' : 'Include this freight in Final Total'">
                                                        <span x-text="selectedCarrier === 'dhl' ? '✓ Applied' : '+ Apply'"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Air Freight -->
                                        <tr class="hover:bg-blue-50/40" :class="selectedCarrier === 'air_freight' ? 'bg-blue-50/60' : ''">
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
                                                    <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][given_amount]" x-model.number="carriers.air_freight.given_amount" @input="recalcCarrier('air_freight')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    <button type="button" @click="toggleCarrierFreight('air_freight')" :class="selectedCarrier === 'air_freight' ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' : 'bg-blue-100 hover:bg-blue-200 text-blue-900 border border-blue-300'" class="text-[10px] whitespace-nowrap font-bold px-2 py-1 rounded transition cursor-pointer" :title="selectedCarrier === 'air_freight' ? 'Freight included in total. Click to remove.' : 'Include this freight in Final Total'">
                                                        <span x-text="selectedCarrier === 'air_freight' ? '✓ Applied' : '+ Apply'"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Sea Freight -->
                                        <tr class="hover:bg-emerald-50/40" :class="selectedCarrier === 'sea_freight' ? 'bg-emerald-50/60' : ''">
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
                                                    <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][given_amount]" x-model.number="carriers.sea_freight.given_amount" @input="recalcCarrier('sea_freight')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    <button type="button" @click="toggleCarrierFreight('sea_freight')" :class="selectedCarrier === 'sea_freight' ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' : 'bg-emerald-100 hover:bg-emerald-200 text-emerald-900 border border-emerald-300'" class="text-[10px] whitespace-nowrap font-bold px-2 py-1 rounded transition cursor-pointer" :title="selectedCarrier === 'sea_freight' ? 'Freight included in total. Click to remove.' : 'Include this freight in Final Total'">
                                                        <span x-text="selectedCarrier === 'sea_freight' ? '✓ Applied' : '+ Apply'"></span>
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
                                        <input type="number" step="0.01" name="final_total" x-model.number="finalTotal" :disabled="isWeightOnly" class="w-48 text-right font-mono text-2xl font-black text-indigo-900 rounded-lg border-indigo-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    </div>
                                    <div x-show="selectedCarrier && carriers[selectedCarrier]" class="text-xs text-indigo-700 font-medium">
                                        Subtotal: <span class="font-mono" x-text="`${currency} ${formatNumber(subtotal)}`"></span>
                                        + Freight (<span class="font-bold uppercase" x-text="selectedCarrier === 'dhl' ? 'DHL Express' : (selectedCarrier === 'air_freight' ? 'Air Freight' : 'Sea Freight')"></span>): 
                                        <span class="font-mono font-bold" x-text="`${currency} ${formatNumber(carriers[selectedCarrier].given_amount || carriers[selectedCarrier].system_amount || 0)}`"></span>
                                    </div>
                                    <p class="text-[11px] text-indigo-600">Defaults to item sum plus applied freight. Can be manually adjusted.</p>
                                </div>
                                <div x-show="isWeightOnly" class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-right space-y-2">
                                    <input type="hidden" name="final_total" value="0" :disabled="!isWeightOnly">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700">
                                        Non-Commercial / No Financial Total
                                    </span>
                                    <p class="text-xs text-gray-600 font-medium">
                                        This document (<span class="font-bold uppercase text-indigo-700" x-text="documentType"></span>) only tracks weights, dimensions, and line items without financial pricing.
                                    </p>
                                </div>
                            </div>
                        </div>

                        </div><!-- End of Collapsible Steps 2 to 6 Container -->

                    </div>

                    <!-- Right Column: Verification Checklist Panel (4 Cols, Sticky) -->
                    <div class="lg:col-span-4 min-w-0 sticky top-6 space-y-6">

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
                ? initial.items.map(it => {
                    const price = parseFloat(it.unit_price) || 0;
                    const code = (it.item_code || '').toUpperCase();
                    const isDisc = price < 0 || code === 'DISCOUNT';
                    const isTax = code === 'TAX' || code === 'VAT';
                    const isAdd = code === 'ADDITION';
                    const desc = it.description || '';
                    const pctMatch = desc.match(/(\d+(?:\.\d+)?)\s*%/);
                    const pct = pctMatch ? parseFloat(pctMatch[1]) : (isTax ? 5 : null);
                    return {
                        type: isDisc ? 'discount' : (isTax ? 'tax' : (isAdd ? 'addition' : 'item')),
                        item_code: it.item_code || '',
                        description: it.description || '',
                        calc_mode: pct ? 'percentage' : 'fixed',
                        percentage: pct,
                        unit_amount: (it.unit_amount !== undefined && it.unit_amount !== null && it.unit_amount !== '') ? it.unit_amount : '',
                        unit_price: (it.unit_price !== undefined && it.unit_price !== null && it.unit_price !== '') ? it.unit_price : '',
                        total_amount: parseFloat(it.total_amount) || 0,
                        unit_weight: parseFloat(it.unit_weight) || 0,
                        total_weight: parseFloat(it.total_weight) || 0,
                        price_from_tracker: false,
                        price_editable: false
                    };
                })
                : [
                    { type: 'item', item_code: '', description: '', calc_mode: 'fixed', percentage: null, unit_amount: '', unit_price: '', total_amount: 0, unit_weight: 0, total_weight: 0, price_from_tracker: false, price_editable: false }
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
                sourceDocumentId: initial.sourceDocumentId || initial.source_document_id || null,
                sourceDocumentNumber: initial.sourceDocumentNumber || initial.source_document_number || '',
                companyName: initial.companyName || initial.company_name || '',
                country: initial.country || '',
                address: initial.address || '',
                contactDetails: initial.contactDetails || initial.contact_details || '',
                documentNumber: initial.documentNumber || initial.document_number || '',
                documentType: initial.documentType || initial.document_type || '{{ old('document_type', $targetType ?? '') }}',
                currency: initial.currency || 'USD',
                ruleMatched: '',
                isDetecting: false,
                subtotal: 0,
                finalTotal: 0,
                selectedCarrier: initial.selectedCarrier || null,
                netWeight: initial.netWeight ?? initial.total_net_weight ?? null,
                grossWeight: initial.grossWeight ?? initial.total_gross_weight ?? null,
                checklists: [],
                checkedItems: {},
                showFullForm: {{ Auth::user()->isAdmin() ? 'true' : 'false' }},

                draftKey: 'doc_draft_create' + (initial.sourceDocumentId ? '_' + initial.sourceDocumentId : ''),
                hasDraft: false,
                draftSavedAt: null,
                lastAutoSavedAt: null,
                autoSaveTimer: null,
                savedDraft: null,

                sourceInput: initial.sourceInput || initial.sourceDocumentNumber || initial.source_document_number || '',
                isImporting: false,
                importMessage: initial.importMessage || '',
                importError: '',

                selectedPriceList: '',
                selectedPriceLabel: 'AED 30%',
                availablePriceLists: ['Price List', 'Union'],
                availablePriceLabels: ['AED 30%', 'AED 40%', 'AED 50%', 'USD 30%', 'USD 40%', 'USD 50%'],
                itemSuggestions: {},

                bulkPasteModalOpen: false,
                bulkPasteTab: 'add_items',
                bulkPasteItemsText: '',
                bulkPasteQuantitiesText: '',
                draggedRowIndex: null,
                dragOverRowIndex: null,

                items: initialItems,
                packages: initialPackages,

                carriers: {
                    dhl: {
                        checked_weight: initial?.carriers?.dhl?.checked_weight ?? null,
                        rate_per_kg: initial?.carriers?.dhl?.rate_per_kg ?? null,
                        system_amount: initial?.carriers?.dhl?.system_amount ?? null,
                        added_amount: initial?.carriers?.dhl?.added_amount ?? null,
                        given_amount: initial?.carriers?.dhl?.given_amount ?? null
                    },
                    air_freight: {
                        checked_weight: initial?.carriers?.air_freight?.checked_weight ?? null,
                        rate_per_kg: initial?.carriers?.air_freight?.rate_per_kg ?? null,
                        system_amount: initial?.carriers?.air_freight?.system_amount ?? null,
                        added_amount: initial?.carriers?.air_freight?.added_amount ?? null,
                        given_amount: initial?.carriers?.air_freight?.given_amount ?? null
                    },
                    sea_freight: {
                        checked_weight: initial?.carriers?.sea_freight?.checked_weight ?? null,
                        rate_per_kg: initial?.carriers?.sea_freight?.rate_per_kg ?? null,
                        system_amount: initial?.carriers?.sea_freight?.system_amount ?? null,
                        added_amount: initial?.carriers?.sea_freight?.added_amount ?? null,
                        given_amount: initial?.carriers?.sea_freight?.given_amount ?? null
                    }
                },

                get isWeightOnly() {
                    return this.documentType === 'packing_list' || this.documentType === 'reserve' || this.documentType === 'delivery_note';
                },

                get calculatedItemsNetWeight() {
                    return this.items.reduce((sum, it) => sum + (parseFloat(it.total_weight) || 0), 0);
                },

                get totalQuantity() {
                    return this.items.reduce((sum, it) => {
                        if (this.isAdjustment(it)) return sum;
                        const qty = parseFloat(it.unit_amount) || 0;
                        return sum + qty;
                    }, 0);
                },

                get formattedTotalQuantity() {
                    const qty = Math.round(this.totalQuantity * 1000) / 1000;
                    return (Math.floor(qty) === qty) ? qty.toLocaleString('en-US') : qty.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                        const wt = (c.checked_weight !== null && c.checked_weight !== '')
                            ? parseFloat(c.checked_weight) || 0
                            : this.chargeableWeight;
                        c.system_amount = Math.round(wt * rate * 100) / 100;
                    }
                    if (this.selectedCarrier === method) {
                        this.recalculateTotals();
                    }
                },

                recalcAllCarriers() {
                    ['dhl', 'air_freight', 'sea_freight'].forEach(m => this.recalcCarrier(m));
                },

                toggleCarrierFreight(carrier) {
                    if (this.selectedCarrier === carrier) {
                        this.selectedCarrier = null;
                    } else {
                        this.selectedCarrier = carrier;
                    }
                    this.recalculateTotals();
                },

                applyFreightToTotal(amount, carrier) {
                    this.selectedCarrier = carrier;
                    this.recalculateTotals();
                },

                async triggerImport() {
                    const docCode = (this.sourceInput || '').trim();
                    if (!docCode) {
                        await window.systemAlert('Please select or enter a source document code to import.', { title: 'Missing Source Document', type: 'warning' });
                        return;
                    }

                    const confirmed = await window.systemConfirm({
                        title: 'Import Document Details',
                        message: `Are you sure you want to import details from ${docCode}? This will populate customer details, shipment charges, line items, and packaging.`,
                        confirmText: 'Import Details',
                        type: 'primary'
                    });

                    if (!confirmed) {
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
                        if (data.company_name !== undefined && data.company_name !== null) this.companyName = data.company_name;
                        if (data.country !== undefined && data.country !== null) this.country = data.country;
                        if (data.address !== undefined && data.address !== null) this.address = data.address;
                        if (data.contact_details !== undefined && data.contact_details !== null) this.contactDetails = data.contact_details;
                        if (data.currency) this.currency = data.currency;

                        this.sourceDocumentId = data.id;
                        this.sourceDocumentNumber = data.document_number;

                        // Import shipment charges
                        if (data.shipment_costs) {
                            ['dhl', 'air_freight', 'sea_freight'].forEach(m => {
                                const sc = data.shipment_costs[m];
                                if (sc) {
                                    this.carriers[m].checked_weight = sc.checked_weight !== null ? sc.checked_weight : null;
                                    this.carriers[m].rate_per_kg = sc.rate_per_kg !== null ? sc.rate_per_kg : null;
                                    this.carriers[m].system_amount = sc.system_amount !== null ? sc.system_amount : null;
                                    this.carriers[m].added_amount = sc.added_amount !== null ? sc.added_amount : null;
                                    this.carriers[m].given_amount = sc.given_amount !== null ? sc.given_amount : null;
                                } else {
                                    this.carriers[m].checked_weight = null;
                                    this.carriers[m].rate_per_kg = null;
                                    this.carriers[m].system_amount = null;
                                    this.carriers[m].added_amount = null;
                                    this.carriers[m].given_amount = null;
                                }
                            });
                        }

                        // Import line items
                        if (data.items && data.items.length > 0) {
                            this.items = data.items.map(it => {
                                const price = parseFloat(it.unit_price) || 0;
                                const code = (it.item_code || '').toUpperCase();
                                const isDisc = price < 0 || code === 'DISCOUNT';
                                const isTax = code === 'TAX' || code === 'VAT';
                                const isAdd = code === 'ADDITION';
                                const desc = it.description || '';
                                const pctMatch = desc.match(/(\d+(?:\.\d+)?)\s*%/);
                                const pct = pctMatch ? parseFloat(pctMatch[1]) : (isTax ? 5 : null);
                                return {
                                    type: isDisc ? 'discount' : (isTax ? 'tax' : (isAdd ? 'addition' : 'item')),
                                    item_code: it.item_code || '',
                                    description: it.description || '',
                                    calc_mode: pct ? 'percentage' : 'fixed',
                                    percentage: pct,
                                    unit_amount: (it.unit_amount !== undefined && it.unit_amount !== null && it.unit_amount !== '') ? it.unit_amount : '',
                                    unit_price: (it.unit_price !== undefined && it.unit_price !== null && it.unit_price !== '') ? it.unit_price : '',
                                    unit_weight: it.unit_weight || 0,
                                    total_weight: it.total_weight || (it.unit_weight * it.unit_amount) || 0,
                                    total_amount: it.total_amount || (it.unit_amount * it.unit_price) || 0,
                                    price_from_tracker: false,
                                    price_editable: false
                                };
                            });
                            this.items.forEach(it => this.recalcItem(it));
                        }

                        // Import packages if present
                        if (data.packages && data.packages.length > 0) {
                            this.packages = data.packages.map(p => ({
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
                            this.packages.forEach(p => this.recalcPackage(p));
                        }

                        if (data.total_net_weight) this.netWeight = data.total_net_weight;
                        if (data.total_gross_weight) this.grossWeight = data.total_gross_weight;

                        this.recalcTotals();
                        this.recalcAllCarriers();
                        this.importMessage = `Successfully imported ${data.items ? data.items.length : 0} items, packaging & shipment charges from ${data.document_number} (${data.company_name})!`;
                    } catch (err) {
                        this.importError = err.message;
                    } finally {
                        this.isImporting = false;
                    }
                },

                init() {
                    this.items.forEach(it => this.recalcItem(it));
                    this.packages.forEach(p => this.recalcPackage(p));
                    this.recalcTotals();
                    this.initPriceLabels();
                    if (this.documentType) {
                        this.loadChecklistsForType(this.documentType);
                    }
                    this.checkSavedDraft();
                    this.autoSaveTimer = setInterval(() => {
                        this.saveDraft();
                    }, 8000);
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

                isAdjustment(it) {
                    if (!it) return false;
                    const code = (it.item_code || '').trim().toUpperCase();
                    return it.type === 'discount' || it.type === 'tax' || it.type === 'addition' || ['DISCOUNT', 'DISC', 'TAX', 'VAT', 'TAX / VAT', 'TAX/VAT', 'ADDITION', 'ADD', 'SURCHARGE'].includes(code);
                },

                get itemsBaseTotal() {
                    const sum = this.items
                        .filter(it => !this.isAdjustment(it) && (parseFloat(it.total_amount) || 0) > 0)
                        .reduce((acc, it) => acc + (parseFloat(it.total_amount) || 0), 0);
                    return Math.round(sum * 100) / 100;
                },

                get discountsTotal() {
                    return this.items
                        .filter(it => it.type === 'discount' || (parseFloat(it.total_amount) || 0) < 0)
                        .reduce((sum, it) => sum + Math.abs(parseFloat(it.total_amount) || 0), 0);
                },

                get taxesTotal() {
                    return this.items
                        .filter(it => it.type === 'tax' || (['TAX', 'VAT'].includes((it.item_code || '').toUpperCase()) && (parseFloat(it.total_amount) || 0) > 0))
                        .reduce((sum, it) => sum + (parseFloat(it.total_amount) || 0), 0);
                },

                get additionsTotal() {
                    return this.items
                        .filter(it => it.type === 'addition' || ((it.item_code || '').toUpperCase() === 'ADDITION' && (parseFloat(it.total_amount) || 0) > 0))
                        .reduce((sum, it) => sum + (parseFloat(it.total_amount) || 0), 0);
                },

                addItem() {
                    this.items.push({
                        type: 'item',
                        item_code: '',
                        description: '',
                        calc_mode: 'fixed',
                        percentage: null,
                        unit_amount: '',
                        unit_price: '',
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false,
                        price_editable: false
                    });
                },

                togglePriceEdit(item, index) {
                    item.price_editable = !item.price_editable;
                    if (item.price_editable) {
                        this.$nextTick(() => {
                            const el = this.$refs['priceInput_' + index];
                            if (el) {
                                el.focus();
                                el.select();
                            }
                        });
                    }
                },

                addDiscount() {
                    const disc = {
                        type: 'discount',
                        item_code: 'DISCOUNT',
                        description: 'Discount (5%)',
                        calc_mode: 'percentage',
                        percentage: 5,
                        unit_amount: 1,
                        unit_price: 0,
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false
                    };
                    this.items.push(disc);
                    this.recalcItem(disc);
                },

                addTax() {
                    const tax = {
                        type: 'tax',
                        item_code: 'TAX',
                        description: 'VAT / Tax (5%)',
                        calc_mode: 'percentage',
                        percentage: 5,
                        unit_amount: 1,
                        unit_price: 0,
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false
                    };
                    this.items.push(tax);
                    this.recalcItem(tax);
                },

                addAddition() {
                    this.items.push({
                        type: 'addition',
                        item_code: 'ADDITION',
                        description: '',
                        calc_mode: 'fixed',
                        percentage: null,
                        unit_amount: 1,
                        unit_price: '',
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false
                    });
                },

                setCalcMode(item, mode) {
                    item.calc_mode = mode;
                    if (mode === 'percentage') {
                        if (!item.percentage || item.percentage <= 0) {
                            item.percentage = 5;
                        }
                    }
                    this.recalcItem(item);
                },

                async applyLineDiscount(item) {
                    const currentPrice = parseFloat(item.unit_price) || 0;
                    if (currentPrice <= 0) return;
                    const input = await window.systemPrompt(`Enter % discount to apply to unit price of ${item.item_code || 'this item'} (e.g. 10 for 10% off):`, {
                        title: 'Apply Unit Price Discount',
                        defaultValue: '10',
                        placeholder: '10'
                    });
                    if (input !== null) {
                        const pct = parseFloat(input);
                        if (!isNaN(pct) && pct > 0 && pct <= 100) {
                            const discounted = Math.round(currentPrice * (1 - (pct / 100)) * 100) / 100;
                            item.unit_price = discounted;
                            if (!item.description.includes(`(-${pct}%)`)) {
                                item.description = (item.description ? item.description + ` ` : '') + `(-${pct}%)`;
                            }
                            this.recalcItem(item);
                        }
                    }
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                        this.recalcTotals();
                    }
                },

                insertItemAfter(index) {
                    this.items.splice(index + 1, 0, {
                        type: 'item',
                        item_code: '',
                        description: '',
                        calc_mode: 'fixed',
                        percentage: null,
                        unit_amount: '',
                        unit_price: '',
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false,
                        price_editable: false
                    });
                    this.$nextTick(() => {
                        this.focusGridCell(index + 1, 0);
                    });
                    this.recalcTotals();
                },

                moveItemUp(index) {
                    if (index > 0) {
                        const item = this.items.splice(index, 1)[0];
                        this.items.splice(index - 1, 0, item);
                        this.recalcTotals();
                    }
                },

                moveItemDown(index) {
                    if (index < this.items.length - 1) {
                        const item = this.items.splice(index, 1)[0];
                        this.items.splice(index + 1, 0, item);
                        this.recalcTotals();
                    }
                },

                onRowDragStart(e, index) {
                    this.draggedRowIndex = index;
                    if (e.dataTransfer) {
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', String(index));
                    }
                },

                onRowDragOver(e, index) {
                    e.preventDefault();
                    if (this.draggedRowIndex === null) return;
                    if (e.dataTransfer) {
                        e.dataTransfer.dropEffect = 'move';
                    }
                    this.dragOverRowIndex = index;
                },

                onRowDragLeave(e, index) {
                    if (this.dragOverRowIndex === index) {
                        this.dragOverRowIndex = null;
                    }
                },

                onRowDragEnd() {
                    this.draggedRowIndex = null;
                    this.dragOverRowIndex = null;
                },

                onRowDrop(e, targetIndex) {
                    e.preventDefault();
                    if (this.draggedRowIndex !== null && this.draggedRowIndex !== targetIndex) {
                        const fromIdx = this.draggedRowIndex;
                        const item = this.items.splice(fromIdx, 1)[0];
                        this.items.splice(targetIndex, 0, item);
                        this.recalcTotals();
                        window.showToast?.(`Moved item from #${fromIdx + 1} to #${targetIndex + 1}`, 'info');
                    }
                    this.draggedRowIndex = null;
                    this.dragOverRowIndex = null;
                },

                onQuantityInput(item) {
                    if (item.unit_amount !== null && item.unit_amount !== undefined) {
                        let val = String(item.unit_amount).replace(/,/g, '.');
                        val = val.replace(/[^0-9.]/g, '');
                        const parts = val.split('.');
                        if (parts.length > 2) {
                            val = parts[0] + '.' + parts.slice(1).join('');
                        }
                        item.unit_amount = val;
                    }
                    this.recalcItem(item);
                },

                recalcItem(item) {
                    if (this.isAdjustment(item)) {
                        item.unit_amount = 1;
                    }
                    const rawQty = (item.unit_amount !== '' && item.unit_amount !== null) ? parseFloat(item.unit_amount) : (this.isAdjustment(item) ? 1 : 0);

                    if (item.calc_mode === 'percentage') {
                        const pct = parseFloat(item.percentage) || 0;
                        const base = this.itemsBaseTotal;
                        const val = Math.round((base * (pct / 100)) * 100) / 100;

                        if (item.type === 'discount') {
                            item.unit_price = -val;
                            if (!item.description || item.description.startsWith('Discount')) {
                                item.description = pct > 0 ? `Discount (${pct}%)` : 'Discount';
                            }
                        } else if (item.type === 'tax') {
                            item.unit_price = val;
                            if (!item.description || item.description.startsWith('VAT') || item.description.startsWith('Tax')) {
                                item.description = pct > 0 ? `VAT / Tax (${pct}%)` : 'VAT / Tax';
                            }
                        } else if (item.type === 'addition') {
                            item.unit_price = val;
                            if (!item.description || item.description.startsWith('Surcharge')) {
                                item.description = pct > 0 ? `Surcharge (${pct}%)` : 'Surcharge';
                            }
                        }
                    } else {
                        let price = (item.unit_price !== '' && item.unit_price !== null) ? parseFloat(item.unit_price) : 0;
                        if (item.type === 'discount' && price > 0) {
                            price = -price;
                        }
                        item.unit_price = price;
                    }

                    const price = parseFloat(item.unit_price) || 0;
                    item.total_amount = Math.round(rawQty * price * 100) / 100;
                    const unitWt = parseFloat(item.unit_weight) || 0;
                    item.total_weight = Math.round(rawQty * unitWt * 1000) / 1000;
                    this.recalcTotals();
                },

                recalcTotals() {
                    const base = this.itemsBaseTotal;

                    // Sync any percentage rows to the current itemsBaseTotal
                    this.items.forEach(it => {
                        if (it.calc_mode === 'percentage') {
                            const pct = parseFloat(it.percentage) || 0;
                            const val = Math.round((base * (pct / 100)) * 100) / 100;
                            const rawQty = (it.unit_amount !== '' && it.unit_amount !== null) ? parseFloat(it.unit_amount) : 1;
                            if (it.type === 'discount') {
                                it.unit_price = -val;
                                it.total_amount = -val * rawQty;
                            } else {
                                it.unit_price = val;
                                it.total_amount = val * rawQty;
                            }
                        }
                    });

                    let sum = 0;
                    this.items.forEach(it => {
                        sum += parseFloat(it.total_amount) || 0;
                    });
                    this.subtotal = Math.round(sum * 100) / 100;
                    let freight = 0;
                    if (this.selectedCarrier && this.carriers[this.selectedCarrier]) {
                        const sel = this.carriers[this.selectedCarrier];
                        freight = parseFloat(sel.given_amount) || parseFloat(sel.system_amount) || 0;
                    }
                    this.finalTotal = Math.round((this.subtotal + freight) * 100) / 100;
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

                checkSavedDraft() {
                    try {
                        const raw = localStorage.getItem(this.draftKey);
                        if (!raw) return;
                        const draft = JSON.parse(raw);
                        // Only consider drafts saved within the last 7 days
                        if (!draft.timestamp || (Date.now() - draft.timestamp) > 7 * 86400000) {
                            localStorage.removeItem(this.draftKey);
                            return;
                        }
                        const hasContent = (draft.items && draft.items.some(i => i.item_code)) || draft.companyName || draft.documentNumber;
                        if (!hasContent) return;

                        this.hasDraft = true;
                        this.draftSavedAt = draft.savedAtFormatted || new Date(draft.timestamp).toLocaleTimeString();
                        this.savedDraft = draft;
                    } catch (e) {
                        console.warn('Failed to parse draft', e);
                    }
                },

                saveDraft() {
                    try {
                        const hasContent = (this.items && this.items.some(i => i.item_code)) || this.companyName || this.documentNumber;
                        if (!hasContent) return;

                        const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        const payload = {
                            timestamp: Date.now(),
                            savedAtFormatted: timeStr,
                            documentNumber: this.documentNumber,
                            documentType: this.documentType,
                            companyName: this.companyName,
                            country: this.country,
                            address: this.address,
                            contactDetails: this.contactDetails,
                            currency: this.currency,
                            netWeight: this.netWeight,
                            grossWeight: this.grossWeight,
                            selectedCarrier: this.selectedCarrier,
                            items: this.items,
                            packages: this.packages,
                            carriers: this.carriers,
                        };
                        localStorage.setItem(this.draftKey, JSON.stringify(payload));
                        this.lastAutoSavedAt = timeStr;
                    } catch (e) {
                        console.warn('Failed to auto-save draft', e);
                    }
                },

                restoreDraft() {
                    if (!this.savedDraft) return;
                    const d = this.savedDraft;
                    if (d.documentNumber) this.documentNumber = d.documentNumber;
                    if (d.documentType) this.documentType = d.documentType;
                    if (d.companyName) this.companyName = d.companyName;
                    if (d.country) this.country = d.country;
                    if (d.address) this.address = d.address;
                    if (d.contactDetails) this.contactDetails = d.contactDetails;
                    if (d.currency) this.currency = d.currency;
                    if (d.netWeight !== undefined) this.netWeight = d.netWeight;
                    if (d.grossWeight !== undefined) this.grossWeight = d.grossWeight;
                    if (d.selectedCarrier !== undefined) this.selectedCarrier = d.selectedCarrier;
                    if (d.carriers) this.carriers = d.carriers;
                    if (Array.isArray(d.items) && d.items.length > 0) this.items = d.items;
                    if (Array.isArray(d.packages) && d.packages.length > 0) this.packages = d.packages;

                    this.items.forEach(it => this.recalcItem(it));
                    this.packages.forEach(p => this.recalcPackage(p));
                    this.recalcTotals();
                    if (this.documentType) {
                        this.loadChecklistsForType(this.documentType);
                    }
                    this.hasDraft = false;
                    window.showToast?.('Local draft restored successfully!', 'success');
                },

                discardDraft() {
                    try {
                        localStorage.removeItem(this.draftKey);
                    } catch (e) {}
                    this.hasDraft = false;
                    this.savedDraft = null;
                    window.showToast?.('Saved draft discarded.', 'info');
                },

                clearDraft() {
                    try {
                        localStorage.removeItem(this.draftKey);
                    } catch (e) {}
                    if (this.autoSaveTimer) {
                        clearInterval(this.autoSaveTimer);
                    }
                },

                handleTableKeyNav(e, rowIdx, colIdx) {
                    if (e.key === 'ArrowUp') {
                        if (rowIdx > 0) {
                            e.preventDefault();
                            this.focusGridCell(rowIdx - 1, colIdx, 'vertical');
                        } else if (e.target.type === 'number') {
                            e.preventDefault();
                        }
                    } else if (e.key === 'ArrowDown') {
                        if (rowIdx < this.items.length - 1) {
                            e.preventDefault();
                            this.focusGridCell(rowIdx + 1, colIdx, 'vertical');
                        } else if (e.target.type === 'number') {
                            e.preventDefault();
                        }
                    } else if (e.altKey && e.key === 'ArrowLeft') {
                        if (colIdx > 0) {
                            e.preventDefault();
                            this.focusGridCell(rowIdx, colIdx - 1, 'left');
                        }
                    } else if (e.altKey && e.key === 'ArrowRight') {
                        if (colIdx < 3) {
                            e.preventDefault();
                            this.focusGridCell(rowIdx, colIdx + 1, 'right');
                        }
                    }
                },

                focusGridCell(rowIdx, colIdx, direction = null) {
                    const table = this.$refs.itemsTable || document;
                    const findCell = (r, c) => {
                        const els = table.querySelectorAll(`[data-grid-item="true"][data-grid-row="${r}"][data-grid-col="${c}"]`);
                        for (let i = 0; i < els.length; i++) {
                            const el = els[i];
                            if (el && el.offsetParent !== null && el.type !== 'hidden' && !el.disabled) {
                                return el;
                            }
                        }
                        return null;
                    };

                    let target = findCell(rowIdx, colIdx);

                    if (!target) {
                        if (direction === 'right') {
                            for (let c = colIdx + 1; c <= 3; c++) {
                                target = findCell(rowIdx, c);
                                if (target) break;
                            }
                        } else if (direction === 'left') {
                            for (let c = colIdx - 1; c >= 0; c--) {
                                target = findCell(rowIdx, c);
                                if (target) break;
                            }
                        } else {
                            target = findCell(rowIdx, colIdx - 1) || findCell(rowIdx, colIdx + 1) || findCell(rowIdx, 0);
                        }
                    }

                    if (target) {
                        target.focus();
                        if (typeof target.select === 'function' && !target.readOnly) {
                            target.select();
                        }
                    }
                },

                openBulkPasteModal(tab = 'add_items') {
                    this.bulkPasteTab = tab;
                    this.bulkPasteModalOpen = true;
                },

                parseDelimitedList(text) {
                    if (!text || typeof text !== 'string') return [];
                    return text
                        .split(/[\r\n,;\t]+/)
                        .map(s => s.trim())
                        .filter(s => s.length > 0);
                },

                get bulkPastePreviewItems() {
                    const rawItems = (this.bulkPasteItemsText || '').split(/[\r\n]+/);
                    const separateQtys = this.parseDelimitedList(this.bulkPasteQuantitiesText);

                    const result = [];
                    rawItems.forEach(line => {
                        const trimmed = line.trim();
                        if (!trimmed) return;

                        // Check if line is tab-separated (e.g. Excel copied row: CODE \t QTY)
                        if (trimmed.includes('\t')) {
                            const parts = trimmed.split('\t').map(s => s.trim()).filter(s => s.length > 0);
                            if (parts.length >= 2) {
                                result.push({
                                    code: parts[0],
                                    qty: parts[1]
                                });
                                return;
                            }
                        }

                        // Check if comma-separated
                        const subItems = trimmed.split(',').map(s => s.trim()).filter(s => s.length > 0);
                        subItems.forEach(sub => {
                            result.push({
                                code: sub,
                                qty: ''
                            });
                        });
                    });

                    // If separate quantities were provided in the quantities box, map them 1-to-1
                    if (separateQtys.length > 0) {
                        result.forEach((item, idx) => {
                            if (separateQtys[idx] !== undefined) {
                                item.qty = separateQtys[idx];
                            }
                        });
                    }

                    return result;
                },

                bulkPasteQtyForIndex(idx) {
                    const qtys = this.parseDelimitedList(this.bulkPasteQuantitiesText);
                    return qtys[idx] !== undefined ? qtys[idx] : null;
                },

                async applyBulkAddItems() {
                    const preview = this.bulkPastePreviewItems;
                    if (preview.length === 0) return;

                    const isFirstEmpty = this.items.length === 1 && !this.items[0].item_code && !this.items[0].description && !this.items[0].unit_amount;
                    const itemsToReprice = [];

                    preview.forEach((pv, pIdx) => {
                        const rawQty = pv.qty !== '' ? parseFloat(pv.qty) : '';
                        const qtyVal = !isNaN(rawQty) && rawQty !== '' ? rawQty : (pv.qty !== '' ? pv.qty : '');

                        if (isFirstEmpty && pIdx === 0) {
                            this.items[0].item_code = pv.code;
                            if (qtyVal !== '') this.items[0].unit_amount = qtyVal;
                            this.recalcItem(this.items[0]);
                            itemsToReprice.push(this.items[0]);
                        } else {
                            const newItem = {
                                type: 'item',
                                item_code: pv.code,
                                description: '',
                                calc_mode: 'fixed',
                                percentage: null,
                                unit_amount: qtyVal,
                                unit_price: '',
                                total_amount: 0,
                                unit_weight: 0,
                                total_weight: 0,
                                price_from_tracker: false,
                                price_editable: false
                            };
                            this.items.push(newItem);
                            this.recalcItem(newItem);
                            itemsToReprice.push(newItem);
                        }
                    });

                    this.bulkPasteModalOpen = false;
                    this.bulkPasteItemsText = '';
                    this.bulkPasteQuantitiesText = '';

                    window.showToast?.(`Added ${preview.length} item(s). Fetching details...`, 'success');

                    for (const it of itemsToReprice) {
                        if (it.item_code) {
                            await this.lookupItemPrice(it);
                        }
                    }
                    this.recalcTotals();
                },

                applyBulkUpdateQuantities() {
                    const qtys = this.parseDelimitedList(this.bulkPasteQuantitiesText);
                    if (qtys.length === 0) return;

                    let updatedCount = 0;
                    const regularItems = this.items.filter(it => !this.isAdjustment(it));

                    regularItems.forEach((it, idx) => {
                        if (qtys[idx] !== undefined) {
                            const val = parseFloat(qtys[idx]);
                            it.unit_amount = !isNaN(val) ? val : qtys[idx];
                            this.recalcItem(it);
                            updatedCount++;
                        }
                    });

                    this.recalcTotals();
                    this.bulkPasteModalOpen = false;
                    this.bulkPasteQuantitiesText = '';
                    window.showToast?.(`Updated quantities for ${updatedCount} item(s)!`, 'success');
                },

                handleItemCodePaste(e, startRowIdx) {
                    const text = (e.clipboardData || window.clipboardData)?.getData('text') || '';
                    const isMulti = text.includes('\n') || text.includes(',') || text.includes('\t') || text.includes(';');
                    if (!isMulti) return;

                    e.preventDefault();
                    const codes = this.parseDelimitedList(text);
                    if (codes.length === 0) return;

                    codes.forEach((code, idx) => {
                        const rowIdx = startRowIdx + idx;
                        if (rowIdx < this.items.length) {
                            const existing = this.items[rowIdx];
                            if (!this.isAdjustment(existing)) {
                                existing.item_code = code;
                                this.lookupItemPrice(existing);
                            }
                        } else {
                            const newItem = {
                                type: 'item',
                                item_code: code,
                                description: '',
                                calc_mode: 'fixed',
                                percentage: null,
                                unit_amount: '',
                                unit_price: '',
                                total_amount: 0,
                                unit_weight: 0,
                                total_weight: 0,
                                price_from_tracker: false,
                                price_editable: false
                            };
                            this.items.push(newItem);
                            this.lookupItemPrice(newItem);
                        }
                    });

                    this.recalcTotals();
                    window.showToast?.(`Pasted ${codes.length} item codes across rows!`, 'success');
                },

                handleQuantityPaste(e, startRowIdx) {
                    const text = (e.clipboardData || window.clipboardData)?.getData('text') || '';
                    const isMulti = text.includes('\n') || text.includes(',') || text.includes('\t') || text.includes(';');
                    if (!isMulti) return;

                    e.preventDefault();
                    const qtys = this.parseDelimitedList(text);
                    if (qtys.length === 0) return;

                    let updated = 0;
                    qtys.forEach((qtyStr, idx) => {
                        const rowIdx = startRowIdx + idx;
                        if (rowIdx < this.items.length) {
                            const item = this.items[rowIdx];
                            if (!this.isAdjustment(item)) {
                                const num = parseFloat(qtyStr);
                                item.unit_amount = !isNaN(num) ? num : qtyStr;
                                this.recalcItem(item);
                                updated++;
                            }
                        }
                    });

                    this.recalcTotals();
                    window.showToast?.(`Pasted ${updated} quantities across rows!`, 'success');
                },

                prepareSubmit(e) {
                    this.clearDraft();
                    return true;
                }
            };
        }
    </script>
</x-app-layout>
