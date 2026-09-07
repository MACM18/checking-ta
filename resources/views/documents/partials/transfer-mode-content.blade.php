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
                <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                    <h3 class="font-black text-sm uppercase tracking-wider text-gray-800 flex items-center">
                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold me-2">2</span>
                        Sequential Items Transfer List ({{ $document->items->count() }} items)
                    </h3>
                    <span class="text-[11px] text-gray-400 font-medium">Grouped sequentially for external entry</span>
                </div>

                <div class="space-y-3">
                    @forelse($document->items as $idx => $item)
                        @php
                            $code = $item->item_code;
                            $codeUpper = strtoupper(trim($code));
                            $isDiscount = $item->total_amount < 0 || in_array($codeUpper, ['DISCOUNT', 'DISC']);
                            $isTax = in_array($codeUpper, ['TAX', 'VAT', 'TAX / VAT', 'TAX/VAT']) && $item->total_amount >= 0;
                            $isAddition = in_array($codeUpper, ['ADDITION', 'ADD', 'SURCHARGE']) && $item->total_amount >= 0;
                            $isAdjustment = $isDiscount || $isTax || $isAddition;
                        @endphp
                        
                        <div class="p-4 rounded-xl border-2 {{ $isDiscount ? 'bg-rose-50/30 border-rose-200' : ($isTax ? 'bg-amber-50/30 border-amber-200' : ($isAddition ? 'bg-emerald-50/30 border-emerald-200' : 'bg-slate-50/80 border-slate-300')) }} space-y-3">
                            
                            <!-- Card Header: Item Number & Badges -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-gray-900 text-white font-mono font-bold text-xs">
                                        #{{ $loop->iteration }}
                                    </span>
                                    @if($isDiscount)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Discount (-)</span>
                                    @elseif($isTax)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Tax / VAT (+)</span>
                                    @elseif($isAddition)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Addition (+)</span>
                                    @endif
                                </div>
                                <span class="text-[11px] font-mono font-bold text-gray-500">
                                    Line {{ $loop->iteration }} of {{ $document->items->count() }}
                                </span>
                            </div>

                            <!-- Transfer Data Fields Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                                
                                <!-- Field 1: Item Code -->
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-0.5">Item Code</span>
                                    <div class="flex items-center justify-between gap-1.5">
                                        <span class="font-mono text-sm font-black text-gray-900 truncate">{{ $item->item_code }}</span>
                                        <button type="button"
                                                @click="copyText('{{ addslashes($item->item_code) }}', 'Item Code')"
                                                class="px-2 py-0.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-bold transition flex-shrink-0"
                                                title="Copy Item Code">
                                            Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- Field 2: Quantity -->
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-0.5">Quantity</span>
                                    <div class="flex items-center justify-between gap-1.5">
                                        <span class="font-mono text-base font-black text-indigo-700">
                                            {{ $isAdjustment ? '—' : number_format($item->unit_amount, 2) }}
                                        </span>
                                        @if(!$isAdjustment)
                                            <button type="button"
                                                    @click="copyText('{{ number_format($item->unit_amount, 2, '.', '') }}', 'Quantity')"
                                                    class="px-2 py-0.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-bold transition flex-shrink-0"
                                                    title="Copy Quantity">
                                                Copy
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Field 3: Unit Price or Unit Weight --}}
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-0.5">
                                        {{ $document->isWeightOnly() ? 'Unit Net Wt (kg)' : 'Unit Price' }}
                                    </span>
                                    <div class="flex items-center justify-between gap-1.5">
                                        <span class="font-mono text-sm font-bold text-gray-900">
                                            @if($document->isWeightOnly())
                                                {{ $item->unit_weight ? number_format($item->unit_weight, 3) . ' kg' : '-' }}
                                            @else
                                                {{ $isAdjustment ? '—' : $document->currency . ' ' . number_format($item->unit_price, 2) }}
                                            @endif
                                        </span>
                                        <button type="button"
                                                @click="copyText('{{ $document->isWeightOnly() ? ($item->unit_weight ? number_format($item->unit_weight, 3, '.', '') : '') : ($isAdjustment ? '' : number_format($item->unit_price, 2, '.', '')) }}', '{{ $document->isWeightOnly() ? 'Unit Weight' : 'Unit Price' }}')"
                                                class="px-2 py-0.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-bold transition flex-shrink-0"
                                                title="Copy Value">
                                            Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- Field 4: Line Total / Weight Total -->
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-0.5">
                                        {{ $document->isWeightOnly() ? 'Total Net Wt' : 'Line Total' }}
                                    </span>
                                    <div class="flex items-center justify-between gap-1.5">
                                        <span class="font-mono text-sm font-black {{ $item->total_amount < 0 ? 'text-rose-600' : 'text-gray-900' }}">
                                            @if($document->isWeightOnly())
                                                {{ $item->total_weight ? number_format($item->total_weight, 3) . ' kg' : '-' }}
                                            @else
                                                {{ $document->currency }} {{ number_format($item->total_amount, 2) }}
                                            @endif
                                        </span>
                                        <button type="button"
                                                @click="copyText('{{ $document->isWeightOnly() ? ($item->total_weight ? number_format($item->total_weight, 3, '.', '') : '') : number_format($item->total_amount, 2, '.', '') }}', 'Line Total')"
                                                class="px-2 py-0.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-bold transition flex-shrink-0"
                                                title="Copy Total">
                                            Copy
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <!-- Description Row with Copy Button -->
                            @if($item->description)
                                <div class="bg-white p-2.5 rounded-lg border border-slate-200 flex items-start justify-between gap-2 text-xs">
                                    <div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Description</span>
                                        <span class="text-xs font-semibold text-gray-800 leading-snug">{{ $item->description }}</span>
                                    </div>
                                    <button type="button"
                                            @click="copyText('{{ addslashes($item->description) }}', 'Description')"
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 rounded-lg text-[10px] font-bold transition flex-shrink-0"
                                            title="Copy Description">
                                        Copy Desc
                                    </button>
                                </div>
                            @endif

                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 text-xs bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            No items recorded on this document.
                        </div>
                    @endforelse
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
