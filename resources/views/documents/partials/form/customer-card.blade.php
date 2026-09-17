@php
    $isEdit = isset($document);
@endphp

<!-- Step 2: Customer / Company Details -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
    <div class="border-b border-gray-100 pb-3">
        <h3 class="font-bold text-lg text-gray-800 flex items-center">
            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">2</span>
            {{ $isEdit ? 'Company & Recipient Details' : 'Company & Recipient Information' }}
        </h3>
    </div>

    @if(!$isEdit)
        <!-- Highlighted Area: Latest PI for Customer -->
        <div x-show="latestPiDoc && latestPiDoc.found"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-gradient-to-r from-amber-50 via-yellow-50 to-amber-100/60 border-2 border-amber-400/90 rounded-2xl p-4 sm:p-5 shadow-sm text-gray-900">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start sm:items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-600 to-yellow-600 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-extrabold text-xs uppercase tracking-wider text-amber-950 bg-amber-200/90 px-2 py-0.5 rounded-md border border-amber-400/80">
                                Latest PI for Customer
                            </span>
                            <a :href="latestPiDoc.url" target="_blank" class="font-mono font-extrabold text-sm text-amber-950 hover:text-amber-800 underline decoration-amber-500 decoration-2 underline-offset-2 flex items-center" title="Open latest PI document in new tab">
                                <span x-text="latestPiDoc.document_number"></span>
                                <svg class="w-3.5 h-3.5 ml-1 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                            <span class="text-amber-400 font-bold">&bull;</span>
                            <span class="text-xs text-gray-800 font-medium">Date: <strong class="text-black font-semibold" x-text="latestPiDoc.formatted_date || latestPiDoc.document_date"></strong></span>
                            <span class="text-amber-400 font-bold">&bull;</span>
                            <template x-if="latestPiDoc.price_label">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-amber-200 text-amber-950 border border-amber-400 shadow-2xs">
                                    <svg class="w-3 h-3 me-1 text-amber-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                    Price Label: <span class="ml-1" x-text="latestPiDoc.price_label"></span>
                                </span>
                            </template>
                            <template x-if="!latestPiDoc.price_label">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-white/80 text-gray-700 border border-amber-200">
                                    Price Label: None
                                </span>
                            </template>
                            <span class="text-amber-400 font-bold">&bull;</span>
                            <span class="text-xs text-gray-800 font-medium">Total: <strong class="text-black font-mono font-bold" x-text="latestPiDoc.formatted_final_total"></strong></span>
                        </div>
                        <div class="text-[11px] text-amber-900 mt-1 flex items-center space-x-2">
                            <span x-show="selectedPriceLabel && latestPiDoc.price_label && (selectedPriceLabel === latestPiDoc.price_label)" class="font-medium text-amber-900">
                                ✓ Matched currently selected price label <strong class="font-bold text-black" x-text="selectedPriceLabel"></strong>.
                            </span>
                            <span x-show="selectedPriceLabel && latestPiDoc.price_label && (selectedPriceLabel !== latestPiDoc.price_label)" class="font-medium text-amber-900">
                                ℹ Showing customer's previous PI price label (<strong class="font-bold text-black" x-text="latestPiDoc.price_label"></strong>).
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <button type="button"
                            @click="importFromLatestPi()"
                            class="inline-flex items-center px-3.5 py-2 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white rounded-xl text-xs font-bold shadow-xs transition"
                            title="Apply customer price label, country, and currency from this latest PI">
                        <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import this PI
                    </button>
                    <a :href="latestPiDoc.url"
                       target="_blank"
                       class="inline-flex items-center px-3 py-2 bg-white hover:bg-amber-100/50 text-amber-950 border border-amber-300 rounded-xl text-xs font-bold shadow-2xs transition">
                        View
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                    Company Name <span class="text-red-500">*</span>
                </label>
                @if(!$isEdit)
                    <span x-show="isCheckingPi" class="text-[10px] text-amber-700 flex items-center font-medium animate-pulse">
                        <svg class="animate-spin -ml-1 mr-1 h-2.5 w-2.5 text-amber-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Checking previous PI...
                    </span>
                @endif
            </div>
            @if($isEdit)
                <input type="text"
                       name="company_name"
                       value="{{ old('company_name', $document->company_name) }}"
                       required
                       class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            @else
                <input type="text"
                       name="company_name"
                       list="recentCustomersList"
                       x-model="companyName"
                       @input.debounce.300ms="fetchLatestPiForCustomer()"
                       @change="fetchLatestPiForCustomer()"
                       required
                       placeholder="e.g. Apex Industrial Solutions LLC"
                       class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                <datalist id="recentCustomersList">
                    @foreach($recentCustomers ?? [] as $cust)
                        <option value="{{ $cust }}">{{ $cust }}</option>
                    @endforeach
                </datalist>
            @endif
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                Country <span class="text-red-500">*</span>
            </label>
            @if($isEdit)
                <input type="text"
                       name="country"
                       value="{{ old('country', $document->country) }}"
                       required
                       placeholder="e.g. United Arab Emirates, Oman, Saudi Arabia"
                       class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            @else
                <input type="text"
                       name="country"
                       x-model="country"
                       required
                       placeholder="e.g. United Arab Emirates, Oman, Saudi Arabia"
                       class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                Address Needed
            </label>
            @if($isEdit)
                <textarea name="address"
                          rows="3"
                          placeholder="Billing & Shipping street address, warehouse, P.O. Box..."
                          class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $document->address) }}</textarea>
            @else
                <textarea name="address"
                          x-model="address"
                          rows="3"
                          placeholder="Billing & Shipping street address, warehouse, P.O. Box..."
                          class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            @endif
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                Other Contact Details
            </label>
            @if($isEdit)
                <textarea name="contact_details"
                          rows="3"
                          placeholder="Attn / Contact person, Phone, Email, TRN / Tax No..."
                          class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('contact_details', $document->contact_details) }}</textarea>
            @else
                <textarea name="contact_details"
                          x-model="contactDetails"
                          rows="3"
                          placeholder="Attn / Contact person, Phone, Email, TRN / Tax No..."
                          class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            @endif
        </div>
    </div>
</div>
