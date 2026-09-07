<div class="space-y-6">
    <!-- Transfer Mode Active Banner & Quick Actions -->
    <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-700 text-white rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center space-x-2.5">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-white/20 text-white backdrop-blur-xs">
                    ⚡ Split-Screen Transfer Mode Active
                </span>
                <span class="text-emerald-200 text-xs font-medium hidden md:inline-block">
                    Optimized for side-by-side data entry
                </span>
            </div>
            <p class="text-xs text-white/90 font-medium">
                High-contrast view with 1-click field copy. Keeps your verification checklist sticky and unobstructed.
            </p>
        </div>
        <div class="flex items-center space-x-2.5 flex-shrink-0">
            <button type="button"
                    @click="resetChecklist(checklists.length)"
                    class="inline-flex items-center px-3 py-1.5 bg-white/15 hover:bg-white/25 text-white rounded-xl text-xs font-bold transition">
                <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Reset Checklist
            </button>
            <button type="button"
                    @click="toggleTransferMode()"
                    class="inline-flex items-center px-3.5 py-1.5 bg-white text-gray-900 hover:bg-emerald-50 rounded-xl text-xs font-black transition shadow-xs">
                Exit Transfer Mode (Full View)
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left 8 Cols: Transferable Header & Sequential Items -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Section 1: Header / Company Details (1-Click Copy) -->
            <div class="bg-white rounded-2xl shadow-sm border-2 border-slate-300 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                    <h3 class="font-black text-sm uppercase tracking-wider text-gray-800 flex items-center">
                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">1</span>
                        Document & Customer Header (Click any button to copy)
                    </h3>
                    <span class="text-[11px] text-gray-400 font-mono font-medium">Doc #: {{ $document->document_number }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    
                    <!-- Customer Name -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Customer / Company</span>
                            <span class="text-sm font-black text-gray-900 block leading-snug">{{ $document->company_name }}</span>
                        </div>
                        <div class="pt-2">
                            <button type="button"
                                    @click="copyText('{{ addslashes($document->company_name) }}', 'Company Name')"
                                    class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-indigo-50 border border-slate-300 hover:border-indigo-300 rounded-lg text-[11px] font-bold text-gray-700 hover:text-indigo-700 transition w-full justify-center shadow-2xs">
                                <svg class="w-3 h-3 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                Copy Name
                            </button>
                        </div>
                    </div>

                    <!-- Destination Country -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Destination / Country</span>
                            <span class="text-sm font-black text-indigo-900 block">{{ $document->country ?: 'N/A' }}</span>
                        </div>
                        <div class="pt-2">
                            <button type="button"
                                    @click="copyText('{{ addslashes($document->country ?? '') }}', 'Country')"
                                    class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-indigo-50 border border-slate-300 hover:border-indigo-300 rounded-lg text-[11px] font-bold text-gray-700 hover:text-indigo-700 transition w-full justify-center shadow-2xs">
                                <svg class="w-3 h-3 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                Copy Country
                            </button>
                        </div>
                    </div>

                    <!-- Document Number -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Document Number</span>
                            <span class="text-sm font-mono font-black text-gray-900 block">{{ $document->document_number }}</span>
                        </div>
                        <div class="pt-2">
                            <button type="button"
                                    @click="copyText('{{ addslashes($document->document_number) }}', 'Doc Number')"
                                    class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-indigo-50 border border-slate-300 hover:border-indigo-300 rounded-lg text-[11px] font-bold text-gray-700 hover:text-indigo-700 transition w-full justify-center shadow-2xs">
                                <svg class="w-3 h-3 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                Copy Doc #
                            </button>
                        </div>
                    </div>

                    <!-- Document Date -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Document Date</span>
                            <span class="text-sm font-bold text-gray-900 block">
                                {{ $document->document_date ? $document->document_date->format('Y-m-d') : '-' }}
                            </span>
                        </div>
                        <div class="pt-2">
                            <button type="button"
                                    @click="copyText('{{ $document->document_date ? $document->document_date->format('Y-m-d') : '' }}', 'Date')"
                                    class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-indigo-50 border border-slate-300 hover:border-indigo-300 rounded-lg text-[11px] font-bold text-gray-700 hover:text-indigo-700 transition w-full justify-center shadow-2xs">
                                <svg class="w-3 h-3 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                Copy Date
                            </button>
                        </div>
                    </div>

                    <!-- Currency & Total -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">
                                {{ $document->isWeightOnly() ? 'Total Net Weight' : 'Final Total' }}
                            </span>
                            <span class="text-sm font-mono font-black text-emerald-700 block">
                                @if($document->isWeightOnly())
                                    {{ number_format($document->total_net_weight ?? 0, 3) }} kg
                                @else
                                    {{ $document->currency }} {{ number_format($document->final_total, 2) }}
                                @endif
                            </span>
                        </div>
                        <div class="pt-2">
                            <button type="button"
                                    @click="copyText('{{ $document->isWeightOnly() ? number_format($document->total_net_weight ?? 0, 3, '.', '') : number_format($document->final_total, 2, '.', '') }}', '{{ $document->isWeightOnly() ? 'Total Weight' : 'Total Amount' }}')"
                                    class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-indigo-50 border border-slate-300 hover:border-indigo-300 rounded-lg text-[11px] font-bold text-gray-700 hover:text-indigo-700 transition w-full justify-center shadow-2xs">
                                <svg class="w-3 h-3 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                Copy {{ $document->isWeightOnly() ? 'Weight' : 'Total' }}
                            </button>
                        </div>
                    </div>

                    <!-- Source Reference (if available) -->
                    @if($document->source_document_number || $document->sourceDocument)
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Source Reference</span>
                                <span class="text-sm font-mono font-black text-indigo-900 block">
                                    {{ $document->sourceDocument?->document_number ?? $document->source_document_number }}
                                </span>
                            </div>
                            <div class="pt-2">
                                <button type="button"
                                        @click="copyText('{{ addslashes($document->sourceDocument?->document_number ?? $document->source_document_number) }}', 'Source Ref')"
                                        class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-indigo-50 border border-slate-300 hover:border-indigo-300 rounded-lg text-[11px] font-bold text-gray-700 hover:text-indigo-700 transition w-full justify-center shadow-2xs">
                                    <svg class="w-3 h-3 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                    Copy Source Ref
                                </button>
                            </div>
                        </div>
                    @endif

                </div>

                @if($document->address)
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex items-start justify-between gap-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-0.5">Billing Address</span>
                            <span class="text-xs text-gray-800 whitespace-pre-line">{{ $document->address }}</span>
                        </div>
                        <button type="button"
                                @click="copyText('{{ addslashes(str_replace(["\r", "\n"], ' ', $document->address)) }}', 'Address')"
                                class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-indigo-50 border border-slate-300 hover:border-indigo-300 rounded-lg text-[11px] font-bold text-gray-700 hover:text-indigo-700 transition flex-shrink-0 shadow-2xs">
                            <svg class="w-3 h-3 me-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                            Copy Address
                        </button>
                    </div>
                @endif
            </div>

            <!-- Section 2: Sequential Items Transfer List -->
            <div class="bg-white rounded-2xl shadow-sm border-2 border-slate-300 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="font-black text-sm uppercase tracking-wider text-gray-800 flex items-center">
                            <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold me-2">2</span>
                            Sequential Items Transfer List ({{ $document->items->count() }} items)
                        </h3>
                        <p class="text-[11px] text-gray-500 font-medium mt-0.5">Click any cell or value to copy it directly &bull; Optimized for side-by-side data entry</p>
                    </div>

                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <button type="button"
                                @click="copyAllItems()"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold border border-indigo-200 transition shadow-2xs">
                            <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Copy All Items (Excel / ERP Table)
                        </button>
                        <button type="button"
                                @click="copyItemCodesList()"
                                class="inline-flex items-center px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold border border-slate-300 transition shadow-2xs"
                                title="Copy all item codes as a list">
                            Codes List
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-300">
                    <table class="min-w-full divide-y divide-slate-300 text-xs">
                        <thead class="bg-slate-100 text-slate-700 font-black uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="px-3.5 py-3 text-left w-10">#</th>
                                <th class="px-3.5 py-3 text-left">Item Code</th>
                                <th class="px-3.5 py-3 text-left">Description</th>
                                <th class="px-3.5 py-3 text-right">Quantity</th>
                                @if(!$document->isWeightOnly())
                                    <th class="px-3.5 py-3 text-right">Unit Price</th>
                                    <th class="px-3.5 py-3 text-right">Total ({{ $document->currency }})</th>
                                @else
                                    <th class="px-3.5 py-3 text-right">Unit Net Wt (kg)</th>
                                    <th class="px-3.5 py-3 text-right">Total Net Wt (kg)</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($document->items as $idx => $item)
                                @php
                                    $codeUpper = strtoupper(trim($item->item_code ?? ''));
                                    $isDiscount = $item->total_amount < 0 || in_array($codeUpper, ['DISCOUNT', 'DISC']);
                                    $isTax = in_array($codeUpper, ['TAX', 'VAT', 'TAX / VAT', 'TAX/VAT']) && $item->total_amount >= 0;
                                    $isAddition = in_array($codeUpper, ['ADDITION', 'ADD', 'SURCHARGE']) && $item->total_amount >= 0;
                                    $isAdjustment = $isDiscount || $isTax || $isAddition;
                                @endphp
                                <tr class="hover:bg-indigo-50/40 transition {{ $isDiscount ? 'bg-rose-50/30' : ($isTax ? 'bg-amber-50/30' : ($isAddition ? 'bg-emerald-50/30' : '')) }}">
                                    <!-- Index -->
                                    <td class="px-3.5 py-3 font-mono font-bold text-gray-500 bg-slate-50/60 text-center">
                                        {{ $loop->iteration }}
                                    </td>

                                    <!-- Item Code (Click to copy) -->
                                    <td class="px-3.5 py-3 font-mono font-black text-sm text-gray-900 cursor-pointer hover:text-indigo-600 hover:underline select-all"
                                        @click="copyText('{{ addslashes($item->item_code) }}', 'Item Code')"
                                        title="Click to copy Item Code">
                                        <div class="flex items-center space-x-1.5">
                                            @if($isDiscount)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Disc</span>
                                            @elseif($isTax)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Tax</span>
                                            @elseif($isAddition)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Add</span>
                                            @endif
                                            <span>{{ $item->item_code }}</span>
                                        </div>
                                    </td>

                                    <!-- Description (Click to copy) -->
                                    <td class="px-3.5 py-3 text-xs text-gray-800 font-medium cursor-pointer hover:text-indigo-600 select-all"
                                        @click="copyText('{{ addslashes($item->description ?? '') }}', 'Description')"
                                        title="Click to copy Description">
                                        {{ $item->description ?: '-' }}
                                    </td>

                                    <!-- Quantity (Click to copy) -->
                                    <td class="px-3.5 py-3 text-right font-mono font-black text-sm {{ $isAdjustment ? 'text-gray-400' : 'text-indigo-700 cursor-pointer hover:text-indigo-900 select-all' }}"
                                        @if(!$isAdjustment)
                                            @click="copyText('{{ number_format($item->unit_amount, 2, '.', '') }}', 'Quantity')"
                                            title="Click to copy Quantity"
                                        @endif>
                                        {{ $isAdjustment ? '—' : number_format($item->unit_amount, 2) }}
                                    </td>

                                    {{-- Rate or Weight --}}
                                    @if(!$document->isWeightOnly())
                                        <td class="px-3.5 py-3 text-right font-mono font-bold text-sm {{ $isAdjustment ? 'text-gray-400' : 'text-gray-900 cursor-pointer hover:text-indigo-600 select-all' }}"
                                            @if(!$isAdjustment)
                                                @click="copyText('{{ number_format($item->unit_price, 2, '.', '') }}', 'Unit Price')"
                                                title="Click to copy Unit Price"
                                            @endif>
                                            {{ $isAdjustment ? '—' : number_format($item->unit_price, 2) }}
                                        </td>
                                        <td class="px-3.5 py-3 text-right font-mono font-black text-sm {{ $item->total_amount < 0 ? 'text-rose-600' : 'text-gray-900' }} cursor-pointer hover:text-indigo-600 select-all"
                                            @click="copyText('{{ number_format($item->total_amount, 2, '.', '') }}', 'Line Total')"
                                            title="Click to copy Line Total">
                                            {{ number_format($item->total_amount, 2) }}
                                        </td>
                                    @else
                                        <td class="px-3.5 py-3 text-right font-mono font-bold text-sm text-gray-900 cursor-pointer hover:text-indigo-600 select-all"
                                            @click="copyText('{{ $item->unit_weight ? number_format($item->unit_weight, 3, '.', '') : '' }}', 'Unit Weight')"
                                            title="Click to copy Unit Weight">
                                            {{ $item->unit_weight !== null ? number_format($item->unit_weight, 3) : '-' }}
                                        </td>
                                        <td class="px-3.5 py-3 text-right font-mono font-black text-sm text-gray-900 cursor-pointer hover:text-indigo-600 select-all"
                                            @click="copyText('{{ $item->total_weight ? number_format($item->total_weight, 3, '.', '') : '' }}', 'Total Weight')"
                                            title="Click to copy Total Weight">
                                            {{ $item->total_weight !== null ? number_format($item->total_weight, 3) : '-' }}
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-400 text-xs">
                                        No items recorded on this document.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 3: Packages Breakdown (if packages exist) -->
            @if($document->packages->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border-2 border-slate-300 p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                        <h3 class="font-black text-sm uppercase tracking-wider text-gray-800 flex items-center">
                            <span class="flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-800 text-xs font-bold me-2">3</span>
                            Packaging Details ({{ $document->packages->sum('quantity') }} Total Packages)
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($document->packages as $pkg)
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2 text-xs">
                                <div class="flex justify-between items-center font-bold">
                                    <span class="text-gray-900">{{ $pkg->package_type }} (× {{ $pkg->quantity }})</span>
                                    <span class="font-mono text-indigo-700">{{ $pkg->total_gross_weight_kg ? number_format($pkg->total_gross_weight_kg, 2) . ' kg' : '-' }}</span>
                                </div>
                                <div class="text-[11px] text-gray-600 font-mono">
                                    @if($pkg->dimension_type === 'cylindrical')
                                        Dia: {{ $pkg->diameter_cm }} cm × H: {{ $pkg->height_cm }} cm
                                    @else
                                        {{ $pkg->length_cm }} × {{ $pkg->width_cm }} × {{ $pkg->height_cm }} cm
                                    @endif
                                    &bull; Vol: {{ number_format($pkg->volumetric_weight_kg, 2) }} kg &bull; CBM: {{ number_format($pkg->cbm, 3) }}
                                </div>
                                <button type="button"
                                        @click="copyText('{{ $pkg->package_type }}: {{ $pkg->quantity }} pkgs ({{ $pkg->length_cm ?? $pkg->diameter_cm }}x{{ $pkg->width_cm ?? '' }}x{{ $pkg->height_cm }} cm), Gross: {{ $pkg->total_gross_weight_kg }} kg', 'Package Details')"
                                        class="px-2.5 py-1 bg-white hover:bg-indigo-50 border border-slate-300 rounded text-[10px] font-bold text-gray-700 transition">
                                    Copy Packaging Info
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        <!-- Right 4 Cols: Sticky Verification Checklist -->
        <div class="lg:col-span-4 sticky top-6 space-y-4">
            @include('documents.partials.verification-checklist', ['isTransferMode' => true])
        </div>

    </div>
</div>
