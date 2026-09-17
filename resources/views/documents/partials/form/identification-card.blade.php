@php
    $isEdit = isset($document);
@endphp

<!-- Step 1: Document Identification Card -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
    <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-lg text-gray-800 flex items-center">
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">1</span>
                {{ $isEdit ? 'Document Classification' : 'Document Identification & Smart Detection' }}
            </h3>
            <p class="text-xs text-gray-500 mt-1">
                @if($isEdit)
                    Document number, classification type, currency, and date settings.
                @else
                    Type the document number (e.g. <span class="font-mono font-semibold">E26211</span>, <span class="font-mono font-semibold">N10045</span>, <span class="font-mono font-semibold">W30012</span>, <span class="font-mono font-semibold">E26211R</span>, <span class="font-mono font-semibold">CR100</span>, <span class="font-mono font-semibold">E26211C</span>). The system will automatically classify the document type and load the checklist.
                @endif
            </p>
        </div>
        @if($isEdit)
            <span class="text-xs text-gray-400 font-mono">Current Version: {{ $document->current_version }}</span>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Document Number Input -->
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                Document Number <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                @if($isEdit)
                    <input type="text"
                           name="document_number"
                           value="{{ old('document_number', $document->document_number) }}"
                           required
                           class="w-full text-base font-mono uppercase font-semibold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                @else
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
                @endif
            </div>
            @error('document_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Document Type Selector with Suggestion Badge -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                    Document Type <span class="text-red-500">*</span>
                </label>
                @if(!$isEdit)
                    <span x-show="ruleMatched" class="text-[11px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100" x-text="ruleMatched"></span>
                @endif
            </div>
            <select name="document_type"
                    x-model="documentType"
                    @change="loadChecklistsForType(documentType)"
                    required
                    class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                @if(!$isEdit)
                    <option value="">-- Select Type --</option>
                @endif
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" {{ $isEdit && $document->document_type === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
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
            <input type="date"
                   name="document_date"
                   value="{{ old('document_date', $isEdit ? ($document->document_date ? $document->document_date->format('Y-m-d') : '') : ($defaultDate ?? date('Y-m-d'))) }}"
                   required
                   class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <!-- Currency -->
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                Currency <span class="text-red-500">*</span>
            </label>
            <select name="currency" x-model="currency" @change="onCurrencyChanged()" required class="w-full text-sm font-semibold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($currencies as $c)
                    <option value="{{ $c->code }}" {{ $isEdit && $document->currency === $c->code ? 'selected' : '' }}>
                        {{ $c->code }} ({{ $c->symbol ?: $c->code }})
                    </option>
                @endforeach
            </select>
            <div x-show="isAdditionalCurrency" class="mt-2 p-2 bg-indigo-50/80 border border-indigo-200 rounded-lg space-y-1.5" x-cloak>
                <div class="flex items-center justify-between text-[11px] text-indigo-900 font-semibold">
                    <span>Base: <strong class="font-mono">USD</strong></span>
                    <span x-text="currentCurrencyRateText" class="font-mono"></span>
                </div>
                <button type="button"
                        @click="convertPricesToCurrency()"
                        :disabled="isConvertingCurrency"
                        class="w-full py-1.5 px-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-xs font-bold shadow-xs transition flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" :class="isConvertingCurrency ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <span x-text="`Convert to ${currency}`"></span>
                </button>
                <p x-show="conversionMessage" x-text="conversionMessage" class="text-[10px] text-emerald-700 font-medium text-center"></p>
            </div>
        </div>

        <!-- Status -->
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                Status
            </label>
            <select name="status" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="draft" {{ ($isEdit ? $document->status : 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="active" {{ ($isEdit ? $document->status : '') === 'active' ? 'selected' : '' }}>Active / Issued</option>
                <option value="final" {{ ($isEdit ? $document->status : '') === 'final' ? 'selected' : '' }}>Finalized</option>
            </select>
        </div>
    </div>
</div>
