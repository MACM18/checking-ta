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
            <input type="hidden" name="price_list" :value="selectedPriceList">
            <input type="hidden" name="price_label" :value="selectedPriceLabel">

            <div class="max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8">

                @include('documents.partials.form.draft-banner')

                <div class="flex flex-col lg:flex-row gap-8 items-start">

                    <!-- Main Document Details Form -->
                    <div class="flex-1 min-w-0 space-y-6">

                        @include('documents.partials.form.identification-card')

                        @include('documents.partials.form.customer-card')

                        @include('documents.partials.form.items-card')

                        @include('documents.partials.form.packages-card')

                        @include('documents.partials.form.shipment-costs-card')

                        @include('documents.partials.form.totals-notes-card')

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

    @include('documents.partials.scripts.document-editor-script')
</x-app-layout>
