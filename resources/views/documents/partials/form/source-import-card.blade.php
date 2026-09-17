<!-- Step 0: Import from Source Document (PI / Previous Document) -->
<div class="bg-gradient-to-r from-indigo-50/70 via-purple-50/50 to-white rounded-xl shadow-sm border border-indigo-100 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            </div>
            <div>
                <h3 class="font-bold text-sm text-gray-900">
                    @if(($targetType ?? '') === 'factory_invoice')
                        Import from Order Sheet(s) / Supplier Order (e.g. B26001)
                    @else
                        Import from Source Document (Proforma Invoice / Previous Document)
                    @endif
                </h3>
                <p class="text-xs text-gray-500">
                    @if(($targetType ?? '') === 'factory_invoice')
                        Select or type an Order Sheet code (e.g. <span class="font-mono font-bold text-indigo-700">B26001</span>) to import remaining unfulfilled items and supplier details. You can import from multiple order sheets into one factory invoice.
                    @else
                        Select or type a source document code (e.g. <span class="font-mono font-bold text-indigo-700">E26211</span>) to import company details, shipment charges, items, and packaging.
                    @endif
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
                @foreach($availableSourceDocs ?? [] as $avail)
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

    <!-- Hidden inputs for source document reference and price list -->
    <input type="hidden" name="source_document_id" x-model="sourceDocumentId">
    <input type="hidden" name="source_document_number" x-model="sourceDocumentNumber">
    <input type="hidden" name="price_list" :value="selectedPriceList">
    <input type="hidden" name="price_label" :value="selectedPriceLabel">
</div>
