<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('documents.show', $document) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 flex items-center">
                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Exit Edit Mode
                    </a>
                </div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight mt-1 flex items-center">
                    Editing {{ $document->document_number }}
                    <span class="ms-2 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $document->formatted_type }}
                    </span>
                    <span class="ms-2 px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 font-mono">
                        v{{ $document->current_version }}
                    </span>
                </h2>
            </div>

            <!-- Active Lock Heartbeat Badge -->
            <div class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-sm"
                 x-data="{ lastPing: 'Just now' }"
                 x-init="
                    setInterval(() => {
                        fetch('{{ route('documents.lock.heartbeat', $document) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }).then(r => r.json()).then(() => {
                            lastPing = new Date().toLocaleTimeString();
                        });
                    }, 40000);
                 ">
                <span class="w-2 h-2 rounded-full bg-emerald-500 me-2 animate-pulse"></span>
                <span>Lock Active (Secured for you)</span>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="documentEditor(@js($document->items), @js($document->currency), @js($document->document_type), @js($document->packages), @js($shipmentCosts), {{ $document->total_gross_weight ?? 0 }}, {{ $document->total_net_weight ?? 0 }})">
        <form method="POST" action="{{ route('documents.update', $document) }}" @submit="prepareSubmit($event)">
            @csrf
            @method('PUT')

            <div class="max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Unsaved Draft Recovery Banner -->
                <div x-show="hasDraft" x-cloak x-transition class="mb-6 bg-gradient-to-r from-amber-50 via-indigo-50/40 to-amber-50 border border-amber-300/80 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 leading-tight">Unsaved Local Draft Available</h4>
                            <p class="text-xs text-gray-600 mt-0.5">
                                We found an auto-saved draft from <strong class="text-amber-800" x-text="draftSavedAt"></strong>. Would you like to restore your unsaved edits?
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

                <div class="flex flex-col lg:flex-row gap-8 items-start">

                    <!-- Main Document Details Form (Expands to fill extra page width) -->
                    <div class="flex-1 min-w-0 space-y-6">

                        <!-- Step 1: Identification & Classification -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                                <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">1</span>
                                    Document Classification
                                </h3>
                                <span class="text-xs text-gray-400 font-mono">Current Version: {{ $document->current_version }}</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Document Number <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           name="document_number"
                                           value="{{ old('document_number', $document->document_number) }}"
                                           required
                                           class="w-full text-base font-mono uppercase font-semibold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Document Type <span class="text-red-500">*</span>
                                    </label>
                                    <select name="document_type"
                                            x-model="documentType"
                                            @change="loadChecklistsForType(documentType)"
                                            required
                                            class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                        @foreach($types as $key => $label)
                                            <option value="{{ $key }}" {{ $document->document_type === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Document Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="document_date" value="{{ old('document_date', $document->document_date ? $document->document_date->format('Y-m-d') : '') }}" required class="w-full text-sm rounded-lg border-gray-300">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Currency <span class="text-red-500">*</span>
                                    </label>
                                    <select name="currency" x-model="currency" @change="onCurrencyChanged()" required class="w-full text-sm font-semibold rounded-lg border-gray-300">
                                        <option value="USD">USD ($)</option>
                                        <option value="AED">AED (AED)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Status
                                    </label>
                                    <select name="status" class="w-full text-sm rounded-lg border-gray-300">
                                        <option value="draft" {{ $document->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="active" {{ $document->status === 'active' ? 'selected' : '' }}>Active / Issued</option>
                                        <option value="final" {{ $document->status === 'final' ? 'selected' : '' }}>Finalized</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Customer / Company Details -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="border-b border-gray-100 pb-3">
                                <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">2</span>
                                    Company & Recipient Details
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Company Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="company_name" value="{{ old('company_name', $document->company_name) }}" required class="w-full text-sm rounded-lg border-gray-300">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Country <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="country" value="{{ old('country', $document->country) }}" required class="w-full text-sm rounded-lg border-gray-300">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Address Needed
                                    </label>
                                    <textarea name="address" rows="3" class="w-full text-sm rounded-lg border-gray-300">{{ old('address', $document->address) }}</textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Other Contact Details
                                    </label>
                                    <textarea name="contact_details" rows="3" class="w-full text-sm rounded-lg border-gray-300">{{ old('contact_details', $document->contact_details) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Line Items -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">3</span>
                                        <span x-text="isWeightOnly ? (documentType === 'delivery_note' ? 'Delivery Note & Weight Breakdown' : (documentType === 'reserve' ? 'Warehouse Reserve & Weight Breakdown' : 'Packing List & Weight Breakdown')) : 'Line Items & Pricing'"></span>
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="isWeightOnly ? 'Edit quantities, item weights (kg), and packaging. Pricing is omitted for weight-focused documents (packing lists, reserves, and delivery notes).' : 'Edit, add, or remove line items. Line total and subtotal auto-compute in real time.'"></p>
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

                            <!-- Price Tracker Tier Selection Bar (Only for documents with pricing) -->
                            <div x-show="!isWeightOnly" class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-gray-700 flex items-center text-xs">
                                            <svg class="w-4 h-4 text-indigo-600 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                            Price List:
                                        </span>
                                        <select x-model="selectedPriceList" @change="onPriceTierChanged()" class="text-xs rounded-lg border-gray-300 py-1 px-2 font-semibold focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                            <option value="">(All Price Lists)</option>
                                            <template x-for="list in filteredPriceLists" :key="list">
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
                                            <template x-for="lbl in filteredPriceLabels" :key="lbl">
                                                <option :value="lbl" x-text="lbl"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <span class="text-[11px] text-gray-400 font-medium flex items-center" x-show="selectedPriceLabel || selectedPriceList">
                                        <span x-show="isRepricing" class="inline-flex items-center text-indigo-600 font-bold me-1.5 animate-pulse">
                                            <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Updating prices...
                                        </span>
                                        <span x-show="!isRepricing">Auto-updates prices across all items on selection</span>
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
                                            <!-- Weight headers (available for all documents, auto-populated from item manager with edit option) -->
                                            <th class="px-3 py-2.5 text-right w-28">Unit Net Wt (kg)</th>
                                            <th class="px-3 py-2.5 text-right w-32">Total Net Wt (kg)</th>
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
                                                <td class="px-1 py-2 text-center align-middle text-gray-400 select-none relative">
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

                                                    <!-- Small hover plus button to insert row below -->
                                                    <button type="button"
                                                            @click="insertItemAfter(index)"
                                                            class="opacity-0 group-hover:opacity-100 transition absolute left-1/2 -translate-x-1/2 -bottom-2.5 z-20 w-5 h-5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-full flex items-center justify-center shadow-md hover:scale-110"
                                                            title="Insert new row below">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                                        </svg>
                                                    </button>
                                                </td>
                                                <td class="px-3 py-2 align-middle">
                                                    <div class="relative flex items-center">
                                                        <input type="text"
                                                               :name="`items[${index}][item_code]`"
                                                               x-model="item.item_code"
                                                               :list="`item-edit-datalist-${index}`"
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
                                                        <datalist :id="`item-edit-datalist-${index}`">
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
                                                           :placeholder="item.type === 'discount' ? 'e.g. Special client discount (10%)' : (item.type === 'tax' ? 'e.g. VAT / Tax (5%)' : (item.type === 'addition' ? 'e.g. Freight charge, packing fee' : 'Description'))"
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
                                                <!-- Weight-only mode fallback unit price -->
                                                <template x-if="isWeightOnly">
                                                    <input type="hidden" :name="`items[${index}][unit_price]`" value="0">
                                                </template>
                                                <!-- Unit Net Weight (editable) -->
                                                <td class="px-3 py-2 align-middle">
                                                    <template x-if="!isAdjustment(item)">
                                                        <input type="number"
                                                               step="0.001"
                                                               min="0"
                                                               :name="`items[${index}][unit_weight]`"
                                                               x-model.number="item.unit_weight"
                                                               @input="recalcItem(item)"
                                                               @focus="$event.target.select()"
                                                               @keydown="handleTableKeyNav($event, index, isWeightOnly ? 3 : 4)"
                                                               data-grid-item="true"
                                                               :data-grid-row="index"
                                                               :data-grid-col="isWeightOnly ? 3 : 4"
                                                               autocomplete="off"
                                                               autocorrect="off"
                                                               autocapitalize="off"
                                                               spellcheck="false"
                                                               data-lpignore="true"
                                                               placeholder="0.000"
                                                               title="Unit Net Weight in kg (from item master, editable)"
                                                               class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-indigo-500 focus:border-indigo-500">
                                                    </template>
                                                    <template x-if="isAdjustment(item)">
                                                        <div class="flex items-center justify-center py-1.5" title="Weight not applicable for adjustments">
                                                            <input type="hidden" :name="`items[${index}][unit_weight]`" value="0">
                                                            <span class="text-gray-400 font-mono font-bold text-xs select-none">—</span>
                                                        </div>
                                                    </template>
                                                </td>
                                                <!-- Total Net Weight (computed) -->
                                                <td class="px-3 py-2 align-middle text-right font-mono font-bold text-gray-800 relative">
                                                    <input type="hidden" :name="`items[${index}][total_weight]`" :value="item.total_weight">
                                                    <div class="flex items-center justify-end space-x-1.5">
                                                        <template x-if="!isAdjustment(item)">
                                                            <span><span x-text="formatWeight(item.total_weight)"></span> kg</span>
                                                        </template>
                                                        <template x-if="isAdjustment(item)">
                                                            <span class="text-gray-400 font-mono font-bold text-xs select-none">—</span>
                                                        </template>

                                                        <!-- Hover-only action buttons: Small Plus & Delete (no separate action area needed) -->
                                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center space-x-0.5 ml-1">
                                                            <button type="button"
                                                                    @click="insertItemAfter(index)"
                                                                    class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded p-1 transition"
                                                                    title="Insert new row below this item">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
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
                                            <!-- Financial footer -->
                                            <td x-show="!isWeightOnly" class="px-3 py-2.5 text-right font-mono text-gray-400 text-xs">—</td>
                                            <td x-show="!isWeightOnly" class="px-3 py-2.5 text-right font-mono font-black text-sm text-gray-900">
                                                <div><span x-text="currency"></span> <span x-text="formatNumber(finalTotal)"></span></div>
                                                <template x-if="appliedFreightAmount > 0">
                                                    <div class="text-[10px] font-normal text-indigo-600">
                                                        (incl. +<span x-text="currency"></span> <span x-text="formatNumber(appliedFreightAmount)"></span> <span x-text="selectedCarrierName"></span>)
                                                    </div>
                                                </template>
                                            </td>
                                            <!-- Weight footer -->
                                            <td class="px-3 py-2.5 text-right font-mono text-gray-400 text-xs">—</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-black text-sm text-gray-900">
                                                <span x-text="formatWeight(calculatedItemsNetWeight)"></span> kg
                                            </td>
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

                            <!-- Weights & Subtotal Bar -->
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
                                    <input type="number" step="0.001" min="0" name="total_net_weight" x-model.number="netWeight" class="w-full text-sm font-mono rounded-lg border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Total Gross Weight (kg)
                                    </label>
                                    <input type="number" step="0.001" min="0" name="total_gross_weight" x-model.number="grossWeight" class="w-full text-sm font-mono rounded-lg border-gray-300">
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
                                        Specify dimensions for multiple packages. Supports standard rectangular (L × W × H) or cylindrical (Diameter × Height) packaging.
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
                                            <th class="px-3 py-2 text-right w-24">CBM (m³)</th>
                                            <th class="sticky right-0 z-20 bg-gray-50 px-2 py-2 text-center w-10 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(pkg, pIndex) in packages" :key="pIndex">
                                            <tr class="hover:bg-slate-50 group">
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

                                                <td class="px-3 py-2">
                                                    <select :name="`packages[${pIndex}][dimension_type]`" x-model="pkg.dimension_type" @change="recalcPackage(pkg)" class="w-full text-xs font-semibold rounded border-gray-300 py-1.5 px-2 text-indigo-700 bg-indigo-50/50">
                                                        <option value="standard">Box (L×W×H)</option>
                                                        <option value="diameter">Cylinder (Ø×H)</option>
                                                    </select>
                                                </td>

                                                <td class="px-3 py-2">
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

                                                <td class="px-3 py-2">
                                                    <input type="number" min="1" :name="`packages[${pIndex}][quantity]`" x-model.number="pkg.quantity" @input="recalcPackage(pkg)" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true" class="w-full text-xs font-mono font-bold text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>

                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.001" min="0" :name="`packages[${pIndex}][gross_weight_per_pkg_kg]`" x-model.number="pkg.gross_weight_per_pkg_kg" @input="recalcPackage(pkg)" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>

                                                <td class="px-3 py-2 text-right font-mono text-gray-600">
                                                    <span x-text="pkg.cbm ? Number(pkg.cbm).toFixed(3) : '0.000'"></span> m³
                                                </td>

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

                        <!-- Step 5: Shipment Method Costs with Rate / kg (Hidden for Packing List and Reserve) -->
                        <div x-show="!isWeightOnly" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <input type="hidden" name="selected_shipment_method" :value="selectedCarrier">
                            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">5</span>
                                        Shipment Method Costs & Rate / KG
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Air freight & courier charges with rate per kg ($/kg or AED/kg). Click "+ Apply" to include carrier freight in the Final Total.
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[11px] text-gray-500 block">Chargeable Wt for Air/DHL:</span>
                                    <span class="font-mono font-bold text-sm text-indigo-700" x-text="`${chargeableWeight.toFixed(2)} kg`"></span>
                                    <span class="text-[10px] text-gray-400 block">(Actual Gross Weight)</span>
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
                                            <td class="px-4 py-2.5 font-bold text-amber-700">DHL Express</td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.001" min="0" name="shipment_costs[dhl][checked_weight]" x-model.number="carriers.dhl.checked_weight" @input="recalcCarrier('dhl')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][rate_per_kg]" x-model.number="carriers.dhl.rate_per_kg" @input="recalcCarrier('dhl')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][system_amount]" x-model.number="carriers.dhl.system_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][added_amount]" x-model.number="carriers.dhl.added_amount" @input="recalcCarrier('dhl')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center space-x-1.5">
                                                    <input type="number" step="0.01" min="0" name="shipment_costs[dhl][given_amount]" x-model.number="carriers.dhl.given_amount" @input="recalcCarrier('dhl')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    <button type="button" @click="toggleCarrierFreight('dhl')" :class="selectedCarrier === 'dhl' ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' : 'bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300'" class="text-[10px] whitespace-nowrap font-bold px-2 py-1 rounded transition cursor-pointer" :title="selectedCarrier === 'dhl' ? 'Freight included in total. Click to remove.' : 'Include this freight in Final Total'">
                                                        <span x-text="selectedCarrier === 'dhl' ? '✓ Applied' : '+ Apply'"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Air Freight -->
                                        <tr class="hover:bg-blue-50/40" :class="selectedCarrier === 'air_freight' ? 'bg-blue-50/60' : ''">
                                            <td class="px-4 py-2.5 font-bold text-blue-700">Air Freight</td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.001" min="0" name="shipment_costs[air_freight][checked_weight]" x-model.number="carriers.air_freight.checked_weight" @input="recalcCarrier('air_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][rate_per_kg]" x-model.number="carriers.air_freight.rate_per_kg" @input="recalcCarrier('air_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][system_amount]" x-model.number="carriers.air_freight.system_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][added_amount]" x-model.number="carriers.air_freight.added_amount" @input="recalcCarrier('air_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center space-x-1.5">
                                                    <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][given_amount]" x-model.number="carriers.air_freight.given_amount" @input="recalcCarrier('air_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    <button type="button" @click="toggleCarrierFreight('air_freight')" :class="selectedCarrier === 'air_freight' ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' : 'bg-blue-100 hover:bg-blue-200 text-blue-900 border border-blue-300'" class="text-[10px] whitespace-nowrap font-bold px-2 py-1 rounded transition cursor-pointer" :title="selectedCarrier === 'air_freight' ? 'Freight included in total. Click to remove.' : 'Include this freight in Final Total'">
                                                        <span x-text="selectedCarrier === 'air_freight' ? '✓ Applied' : '+ Apply'"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Sea Freight -->
                                        <tr class="hover:bg-emerald-50/40" :class="selectedCarrier === 'sea_freight' ? 'bg-emerald-50/60' : ''">
                                            <td class="px-4 py-2.5 font-bold text-emerald-700">Sea Freight</td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.001" min="0" name="shipment_costs[sea_freight][checked_weight]" x-model.number="carriers.sea_freight.checked_weight" @input="recalcCarrier('sea_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][rate_per_kg]" x-model.number="carriers.sea_freight.rate_per_kg" @input="recalcCarrier('sea_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][system_amount]" x-model.number="carriers.sea_freight.system_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][added_amount]" x-model.number="carriers.sea_freight.added_amount" @input="recalcCarrier('sea_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center space-x-1.5">
                                                    <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][given_amount]" x-model.number="carriers.sea_freight.given_amount" @input="recalcCarrier('sea_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
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

                        <!-- Step 5: Version Creation & Notes -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-3">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" name="create_new_version" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                    <div>
                                        <span class="text-sm font-bold text-indigo-900">Create New Version Snapshot (Version {{ $document->current_version + 1 }})</span>
                                        <p class="text-xs text-indigo-700">Recommended. Allows tracking changes and one-click restoring if items are added or removed.</p>
                                    </div>
                                </label>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Change Summary / Version Notes</label>
                                    <input type="text" name="change_summary" placeholder="e.g. Added item SKU-99, updated freight rates" class="w-full text-xs rounded-lg border-gray-300">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center pt-2">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Notes / Remarks
                                    </label>
                                    <textarea name="notes" rows="3" class="w-full text-sm rounded-lg border-gray-300">{{ old('notes', $document->notes) }}</textarea>
                                </div>

                                <div x-show="!isWeightOnly" class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 text-right space-y-2">
                                    <label class="block text-xs font-bold text-indigo-900 uppercase tracking-wider">
                                        Final Total (<span x-text="currency"></span>)
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

                    </div>

                    <!-- Right Column: Verification Checklist Panel (Sticky, consistent width) -->
                    <div class="w-full lg:w-[360px] xl:w-[380px] flex-shrink-0 sticky top-6 space-y-6">

                        <!-- Interactive Session Checklist Drawer -->
                        <div class="bg-white rounded-xl shadow-md border-2 border-indigo-100 p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-900">Verification Checklist</h4>
                                        <p class="text-[11px] text-gray-500" x-text="checklistHeading"></p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold font-mono"
                                      :class="allChecked ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                      x-text="`${checkedCount} / ${checklists.length}`">
                                </span>
                            </div>

                            <div class="text-[11px] bg-slate-50 text-slate-600 p-2.5 rounded-lg border border-slate-200">
                                Check off verified items to ensure document correctness before saving.
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300"
                                     :style="`width: ${checklists.length > 0 ? (checkedCount / checklists.length) * 100 : 0}%`">
                                </div>
                            </div>

                            <!-- Checklist Items -->
                            <div class="space-y-2 max-h-[380px] overflow-y-auto pr-1">
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
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Action Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                            <div x-show="!isWeightOnly" class="bg-indigo-50/60 rounded-lg p-3 border border-indigo-100 flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-700">Total Payable</div>
                                    <div x-show="appliedFreightAmount > 0" class="text-[10px] text-indigo-500 font-medium">
                                        (incl. <span x-text="selectedCarrierName"></span> freight)
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-gray-500 font-mono" x-text="currency"></span>
                                    <span class="text-base font-black font-mono text-indigo-950" x-text="formatNumber(finalTotal)"></span>
                                </div>
                            </div>
                            <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-sm rounded-lg shadow-sm flex items-center justify-center space-x-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Save & Release Lock</span>
                            </button>
                            <a href="{{ route('documents.show', $document) }}" class="block text-center py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition">
                                Cancel & Discard
                            </a>
                        </div>

                    </div>

                </div>
            </div>
        </form>
    </div>

    <!-- Alpine.js Document Editor Component -->
    <script>
        function documentEditor(initialItems, initialCurrency, initialType, initialPackages, initialCarriers, initialGrossWeight, initialNetWeight) {
            return {
                documentType: initialType || '',
                currency: initialCurrency || 'USD',
                subtotal: 0,
                finalTotal: {{ $document->final_total ?? 0 }},
                selectedCarrier: null,
                grossWeight: initialGrossWeight || null,
                netWeight: initialNetWeight || null,
                checklists: [],
                checkedItems: {},
                docNumber: '{{ $document->document_number }}',

                draftKey: 'doc_draft_edit_{{ $document->id }}',
                hasDraft: false,
                draftSavedAt: null,
                lastAutoSavedAt: null,
                autoSaveTimer: null,
                savedDraft: null,

                selectedPriceList: '',
                selectedPriceLabel: (initialCurrency || 'USD') === 'AED' ? 'AED 30%' : 'USD 30%',
                availablePriceLists: ['Price List', 'Union'],
                availablePriceLabels: ['AED 30%', 'AED 40%', 'AED 50%', 'USD 30%', 'USD 40%', 'USD 50%'],
                isRepricing: false,
                itemSuggestions: {},

                bulkPasteModalOpen: false,
                bulkPasteTab: 'add_items',
                bulkPasteItemsText: '',
                bulkPasteQuantitiesText: '',
                draggedRowIndex: null,
                dragOverRowIndex: null,

                get isWeightOnly() {
                    return this.documentType === 'packing_list' || this.documentType === 'reserve' || this.documentType === 'delivery_note';
                },

                get filteredPriceLists() {
                    const docCurr = (this.currency || 'USD').toUpperCase();
                    return this.availablePriceLists.filter(list => {
                        if (!list) return false;
                        const upper = list.toUpperCase();
                        if (docCurr === 'AED') {
                            if (upper.includes('USD')) return false;
                        } else if (docCurr === 'USD') {
                            if (upper.includes('AED')) return false;
                        }
                        return true;
                    });
                },

                get filteredPriceLabels() {
                    const docCurr = (this.currency || 'USD').toUpperCase();
                    return this.availablePriceLabels.filter(lbl => {
                        if (!lbl) return false;
                        const upper = lbl.toUpperCase();
                        if (docCurr === 'AED') {
                            if (upper.includes('USD')) return false;
                        } else if (docCurr === 'USD') {
                            if (upper.includes('AED')) return false;
                        }
                        return true;
                    });
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

                items: initialItems && initialItems.length > 0 ? initialItems.map(it => {
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
                }) : [
                    { type: 'item', item_code: '', description: '', calc_mode: 'fixed', percentage: null, unit_amount: '', unit_price: '', total_amount: 0, unit_weight: 0, total_weight: 0, price_from_tracker: false, price_editable: false }
                ],

                packages: initialPackages && initialPackages.length > 0 ? initialPackages.map(p => ({
                    package_type: p.package_type || 'Carton',
                    dimension_type: p.dimension_type || 'standard',
                    length_cm: p.length_cm ? parseFloat(p.length_cm) : null,
                    width_cm: p.width_cm ? parseFloat(p.width_cm) : null,
                    height_cm: p.height_cm ? parseFloat(p.height_cm) : null,
                    diameter_cm: p.diameter_cm ? parseFloat(p.diameter_cm) : null,
                    quantity: parseInt(p.quantity) || 1,
                    gross_weight_per_pkg_kg: p.gross_weight_per_pkg_kg ? parseFloat(p.gross_weight_per_pkg_kg) : null,
                    volumetric_weight_kg: parseFloat(p.volumetric_weight_kg) || 0,
                    cbm: parseFloat(p.cbm) || 0
                })) : [
                    { package_type: 'Carton', dimension_type: 'standard', length_cm: null, width_cm: null, height_cm: null, diameter_cm: null, quantity: 1, gross_weight_per_pkg_kg: null, volumetric_weight_kg: 0, cbm: 0 }
                ],

                carriers: {
                    dhl: {
                        checked_weight: initialCarriers?.dhl?.checked_weight ? parseFloat(initialCarriers.dhl.checked_weight) : null,
                        rate_per_kg: initialCarriers?.dhl?.rate_per_kg ? parseFloat(initialCarriers.dhl.rate_per_kg) : null,
                        system_amount: initialCarriers?.dhl?.system_amount ? parseFloat(initialCarriers.dhl.system_amount) : null,
                        added_amount: initialCarriers?.dhl?.added_amount ? parseFloat(initialCarriers.dhl.added_amount) : null,
                        given_amount: initialCarriers?.dhl?.given_amount ? parseFloat(initialCarriers.dhl.given_amount) : null
                    },
                    air_freight: {
                        checked_weight: initialCarriers?.air_freight?.checked_weight ? parseFloat(initialCarriers.air_freight.checked_weight) : null,
                        rate_per_kg: initialCarriers?.air_freight?.rate_per_kg ? parseFloat(initialCarriers.air_freight.rate_per_kg) : null,
                        system_amount: initialCarriers?.air_freight?.system_amount ? parseFloat(initialCarriers.air_freight.system_amount) : null,
                        added_amount: initialCarriers?.air_freight?.added_amount ? parseFloat(initialCarriers.air_freight.added_amount) : null,
                        given_amount: initialCarriers?.air_freight?.given_amount ? parseFloat(initialCarriers.air_freight.given_amount) : null
                    },
                    sea_freight: {
                        checked_weight: initialCarriers?.sea_freight?.checked_weight ? parseFloat(initialCarriers.sea_freight.checked_weight) : null,
                        rate_per_kg: initialCarriers?.sea_freight?.rate_per_kg ? parseFloat(initialCarriers.sea_freight.rate_per_kg) : null,
                        system_amount: initialCarriers?.sea_freight?.system_amount ? parseFloat(initialCarriers.sea_freight.system_amount) : null,
                        added_amount: initialCarriers?.sea_freight?.added_amount ? parseFloat(initialCarriers.sea_freight.added_amount) : null,
                        given_amount: initialCarriers?.sea_freight?.given_amount ? parseFloat(initialCarriers.sea_freight.given_amount) : null
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
                    return parseFloat(this.grossWeight) || 0;
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
                    pkg.volumetric_weight_kg = 0;

                    if (pkg.dimension_type === 'diameter') {
                        const dia = parseFloat(pkg.diameter_cm) || 0;
                        if (dia > 0 && h > 0) {
                            const r = dia / 2;
                            pkg.cbm = Math.round((Math.PI * r * r * h / 1000000) * qty * 10000) / 10000;
                        } else {
                            pkg.cbm = 0;
                        }
                    } else {
                        const l = parseFloat(pkg.length_cm) || 0;
                        const w = parseFloat(pkg.width_cm) || 0;
                        if (l > 0 && w > 0 && h > 0) {
                            pkg.cbm = Math.round(((l * w * h) / 1000000) * qty * 10000) / 10000;
                        } else {
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

                toggleCarrierFreight(carrier) {
                    if (this.selectedCarrier === carrier) {
                        this.selectedCarrier = null;
                    } else {
                        this.selectedCarrier = carrier;
                        const c = this.carriers[carrier];
                        if (c && (c.given_amount === null || c.given_amount === '' || c.given_amount === undefined)) {
                            const sys = parseFloat(c.system_amount) || 0;
                            const added = parseFloat(c.added_amount) || 0;
                            if (sys > 0 || added > 0) {
                                c.given_amount = Math.round((sys + added) * 100) / 100;
                            }
                        }
                    }
                    this.recalcTotals();
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

                    // Auto-fill given_amount from system_amount + added_amount if given_amount is empty
                    const sys = parseFloat(c.system_amount) || 0;
                    const added = parseFloat(c.added_amount) || 0;
                    if ((sys > 0 || added > 0) && (c.given_amount === null || c.given_amount === '' || c.given_amount === undefined)) {
                        c.given_amount = Math.round((sys + added) * 100) / 100;
                    }

                    const freightVal = (c.given_amount !== null && c.given_amount !== '' && !isNaN(c.given_amount))
                        ? (parseFloat(c.given_amount) || 0)
                        : (sys + added);

                    // If freight charges are added and no carrier is currently selected, auto-select this carrier
                    if (freightVal > 0 && !this.selectedCarrier) {
                        this.selectedCarrier = method;
                    }

                    this.recalcTotals();
                },

                recalcAllCarriers() {
                    ['dhl', 'air_freight', 'sea_freight'].forEach(m => this.recalcCarrier(m));
                },

                init() {
                    this.items.forEach(it => this.recalcItem(it));

                    // Auto-detect existing applied carrier freight
                    const initialSavedFinal = {{ $document->final_total ?? 0 }};
                    const itemsSum = Math.round(this.items.reduce((s, it) => s + (parseFloat(it.total_amount) || 0), 0) * 100) / 100;
                    const diff = Math.round((initialSavedFinal - itemsSum) * 100) / 100;

                    if (diff > 0) {
                        ['dhl', 'air_freight', 'sea_freight'].forEach(m => {
                            const ga = parseFloat(this.carriers[m]?.given_amount) || 0;
                            const sa = parseFloat(this.carriers[m]?.system_amount) || 0;
                            if (Math.abs(ga - diff) < 0.01 || Math.abs(sa - diff) < 0.01) {
                                this.selectedCarrier = m;
                            }
                        });
                        if (!this.selectedCarrier) {
                            ['dhl', 'air_freight', 'sea_freight'].forEach(m => {
                                if (parseFloat(this.carriers[m]?.given_amount) > 0 && !this.selectedCarrier) {
                                    this.selectedCarrier = m;
                                }
                            });
                        }
                    }

                    this.recalcTotals();
                    if (initialSavedFinal > 0 && !this.isWeightOnly) {
                        this.finalTotal = initialSavedFinal;
                    }
                    this.loadChecklistsForType(this.documentType);
                    this.initPriceLabels();
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
                        if (this.selectedPriceLabel) {
                            const filtered = this.filteredPriceLabels;
                            if (!filtered.includes(this.selectedPriceLabel)) {
                                this.selectedPriceLabel = filtered[0] || '';
                            }
                        }
                    } catch (e) {
                        console.error('Failed to load price labels', e);
                    }
                },

                onCurrencyChanged() {
                    const docCurr = (this.currency || 'USD').toUpperCase();
                    const labels = this.filteredPriceLabels;
                    if (this.selectedPriceLabel) {
                        const upper = this.selectedPriceLabel.toUpperCase();
                        const hasOtherCurr = docCurr === 'AED' ? upper.includes('USD') : upper.includes('AED');
                        if (hasOtherCurr) {
                            const pctMatch = this.selectedPriceLabel.match(/(\d+%)/);
                            let match = null;
                            if (pctMatch) {
                                match = labels.find(l => l.includes(pctMatch[1]));
                            }
                            this.selectedPriceLabel = match || labels[0] || '';
                        }
                    }
                    const lists = this.filteredPriceLists;
                    if (this.selectedPriceList && !lists.includes(this.selectedPriceList)) {
                        this.selectedPriceList = '';
                    }
                    this.batchRepriceAllItems();
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
                            price_list: this.selectedPriceList || '',
                            currency: this.currency || ''
                        });
                        const res = await fetch(`/api/price-items/search?${params.toString()}`);
                        const data = await res.json();
                        this.itemSuggestions[index] = data.items || [];
                    } catch (e) {
                        console.error('Item suggestions fetch error', e);
                    }

                    this.lookupItemPrice(item);
                },

                async lookupItemPrice(item) {
                    const code = item.item_code ? item.item_code.trim() : '';
                    if (!code) return;

                    try {
                        const params = new URLSearchParams({
                            item_code: code,
                            price_label: this.selectedPriceLabel || '',
                            price_list: this.selectedPriceList || '',
                            currency: this.currency || ''
                        });
                        const res = await fetch(`/api/price-items/lookup?${params.toString()}`);
                        const data = await res.json();

                        if (data.found) {
                            if (data.description && !item.description) {
                                item.description = data.description;
                            }
                            if (data.unit_weight !== null && data.unit_weight !== undefined && (!item.unit_weight || item.unit_weight === 0)) {
                                item.unit_weight = parseFloat(data.unit_weight);
                            }
                            if (!this.isWeightOnly && data.unit_price !== null && data.unit_price !== undefined) {
                                item.unit_price = parseFloat(data.unit_price);
                                item.price_from_tracker = true;
                            }
                            this.recalcItem(item);
                        }
                    } catch (e) {
                        console.error('Item price lookup error', e);
                    }
                },

                async batchRepriceAllItems() {
                    const codes = this.items
                        .map(it => (it.item_code || '').trim())
                        .filter(code => code.length > 0 && !this.isAdjustment({ item_code: code }));

                    if (codes.length === 0) return;

                    this.isRepricing = true;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                        const res = await fetch('/api/price-items/batch-lookup', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                item_codes: codes,
                                price_label: this.selectedPriceLabel || '',
                                price_list: this.selectedPriceList || '',
                                currency: this.currency || ''
                            })
                        });
                        const data = await res.json();
                        const results = data.results || {};

                        let updatedCount = 0;
                        this.items.forEach(item => {
                            if (this.isAdjustment(item)) return;
                            const code = (item.item_code || '').trim();
                            if (!code) return;

                            const match = results[code];
                            if (match && match.found) {
                                if (match.description && !item.description) {
                                    item.description = match.description;
                                }
                                if (match.unit_weight !== null && match.unit_weight !== undefined && (!item.unit_weight || item.unit_weight === 0)) {
                                    item.unit_weight = parseFloat(match.unit_weight);
                                }
                                if (!this.isWeightOnly && match.unit_price !== null && match.unit_price !== undefined) {
                                    item.unit_price = parseFloat(match.unit_price);
                                    item.price_from_tracker = true;
                                }
                                this.recalcItem(item);
                                updatedCount++;
                            }
                        });

                        this.recalcTotals();
                        if (updatedCount > 0) {
                            window.showToast?.(`Updated ${updatedCount} items with ${this.selectedPriceLabel || this.selectedPriceList || 'pricing & weights'}!`, 'info');
                        }
                    } catch (e) {
                        console.error('Batch reprice error', e);
                    } finally {
                        this.isRepricing = false;
                    }
                },

                async repriceAllLineItems() {
                    return this.batchRepriceAllItems();
                },

                onPriceTierChanged() {
                    this.batchRepriceAllItems();
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

                get appliedFreightAmount() {
                    if (!this.selectedCarrier || !this.carriers[this.selectedCarrier]) return 0;
                    const c = this.carriers[this.selectedCarrier];
                    const given = parseFloat(c.given_amount);
                    if (!isNaN(given) && given > 0) return Math.round(given * 100) / 100;
                    const sys = parseFloat(c.system_amount) || 0;
                    const added = parseFloat(c.added_amount) || 0;
                    return (sys + added > 0) ? Math.round((sys + added) * 100) / 100 : 0;
                },

                get selectedCarrierName() {
                    if (!this.selectedCarrier) return '';
                    if (this.selectedCarrier === 'dhl') return 'DHL Express';
                    if (this.selectedCarrier === 'air_freight') return 'Air Freight';
                    if (this.selectedCarrier === 'sea_freight') return 'Sea Freight';
                    return this.selectedCarrier;
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
                    const freight = this.appliedFreightAmount;
                    this.finalTotal = Math.round((this.subtotal + freight) * 100) / 100;
                    if (this.calculatedItemsNetWeight > 0 && !this.netWeight) {
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
                    return (this.documentType || '').replace('_', ' ').toUpperCase();
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
                    return `chk_${this.docNumber}_${itemId}`;
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

                async loadChecklistsForType(type) {
                    if (!type) return;
                    try {
                        const response = await fetch(`/api/checklists/${type}`);
                        const data = await response.json();
                        this.checklists = data.items || [];
                        this.loadChecklistSession();
                    } catch (e) {
                        console.error('Checklist error', e);
                    }
                },

                checkSavedDraft() {
                    try {
                        const raw = localStorage.getItem(this.draftKey);
                        if (!raw) return;
                        const draft = JSON.parse(raw);
                        const serverUpdatedAt = {{ $document->updated_at ? $document->updated_at->timestamp * 1000 : 0 }};
                        if (!draft.timestamp || draft.timestamp <= serverUpdatedAt) {
                            localStorage.removeItem(this.draftKey);
                            return;
                        }
                        const hasContent = Array.isArray(draft.items) && draft.items.length > 0;
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
                        const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        const payload = {
                            timestamp: Date.now(),
                            savedAtFormatted: timeStr,
                            items: this.items,
                            packages: this.packages,
                            carriers: this.carriers,
                            selectedCarrier: this.selectedCarrier,
                            grossWeight: this.grossWeight,
                            netWeight: this.netWeight,
                            currency: this.currency,
                            finalTotal: this.finalTotal,
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
                    if (Array.isArray(d.items) && d.items.length > 0) this.items = d.items;
                    if (Array.isArray(d.packages) && d.packages.length > 0) this.packages = d.packages;
                    if (d.carriers) this.carriers = d.carriers;
                    if (d.selectedCarrier !== undefined) this.selectedCarrier = d.selectedCarrier;
                    if (d.grossWeight !== undefined) this.grossWeight = d.grossWeight;
                    if (d.netWeight !== undefined) this.netWeight = d.netWeight;
                    if (d.currency) this.currency = d.currency;
                    if (d.finalTotal !== undefined) this.finalTotal = d.finalTotal;

                    this.items.forEach(it => this.recalcItem(it));
                    this.packages.forEach(p => this.recalcPackage(p));
                    this.recalcTotals();
                    this.hasDraft = false;
                    window.showToast?.('Local draft changes restored successfully!', 'success');
                },

                discardDraft() {
                    try {
                        localStorage.removeItem(this.draftKey);
                    } catch (e) {}
                    this.hasDraft = false;
                    this.savedDraft = null;
                    window.showToast?.('Saved draft changes discarded.', 'info');
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

                    await this.batchRepriceAllItems();
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

                    this.bulkPasteModalOpen = false;
                    this.bulkPasteQuantitiesText = '';
                    this.recalcTotals();
                    window.showToast?.(`Updated quantities for ${updatedCount} item(s)!`, 'success');
                },

                async handleItemCodePaste(e, startRowIdx) {
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
                        }
                    });

                    await this.batchRepriceAllItems();
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
