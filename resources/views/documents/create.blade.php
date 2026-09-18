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
                'price_list' => $it->price_list ?? '',
                'is_fallback' => (bool) ($it->is_fallback ?? false),
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

            <div class="max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8">

                @include('documents.partials.form.draft-banner')

                <div class="flex flex-col lg:flex-row gap-8 items-start">

                    <!-- Main Document Details Form -->
                    <div class="flex-1 min-w-0 space-y-6">

                        @include('documents.partials.form.source-import-card')

                        @include('documents.partials.form.identification-card')

                        @include('documents.partials.form.customer-card')

                        @include('documents.partials.form.items-card')

                        @include('documents.partials.form.packages-card')

                        @include('documents.partials.form.shipment-costs-card')

                        @include('documents.partials.form.totals-notes-card')

                    </div>

                    <!-- Right Column: Verification Checklist Panel (Sticky) -->
                    <div class="w-full lg:w-[360px] xl:w-[380px] flex-shrink-0 sticky top-6 space-y-6">

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

    @include('documents.partials.scripts.document-creator-script')
</x-app-layout>
