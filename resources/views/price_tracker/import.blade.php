<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                    <svg class="w-6 h-6 me-2.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                    {{ __('Excel Column-by-Column Price Importer') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Copy columns directly from Excel and paste here. Existing prices for the chosen label will be automatically updated/overridden.') }}
                </p>
            </div>
            <a href="{{ route('price-tracker.index') }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                &larr; Back to Price Tracker
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="excelImporter()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Error Message -->
            @if(session('error'))
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center text-red-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 me-2 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('price-tracker.import.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Step 1: Import Setup (Price List, Currency, Price Label) -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-6">
                    <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                        <h3 class="font-bold text-base text-gray-900 flex items-center">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">1</span>
                            Import Target & Categorization
                        </h3>
                        <span class="text-xs text-gray-400">All pasted items will be mapped to this catalogue tier</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                        <!-- 1. Price List -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Price List <span class="text-red-500">*</span>
                            </label>
                            <select name="price_list_select" x-model="priceListSelect" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allPriceLists as $list)
                                    <option value="{{ $list }}">{{ $list }}</option>
                                @endforeach
                                <option value="custom">+ New Custom Price List...</option>
                            </select>

                            <div x-show="priceListSelect === 'custom'" x-cloak class="pt-2">
                                <input type="text" name="price_list_custom" x-model="priceListCustom" placeholder="Enter custom price list name" class="w-full text-xs rounded-xl border-indigo-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- 2. Currency -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center justify-center p-2.5 rounded-xl border cursor-pointer transition text-xs font-bold"
                                       :class="currency === 'AED' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                    <input type="radio" name="currency" value="AED" x-model="currency" class="sr-only">
                                    <span class="text-sm me-1.5">🇦🇪</span> AED (Dirham)
                                </label>
                                <label class="flex items-center justify-center p-2.5 rounded-xl border cursor-pointer transition text-xs font-bold"
                                       :class="currency === 'USD' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                    <input type="radio" name="currency" value="USD" x-model="currency" class="sr-only">
                                    <span class="text-sm me-1.5">🇺🇸</span> USD (Dollar)
                                </label>
                            </div>
                        </div>

                        <!-- 3. Price Label -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Price Label / Tier <span class="text-red-500">*</span>
                            </label>
                            <select name="price_label_select" x-model="priceLabelSelect" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allPriceLabels as $lbl)
                                    <option value="{{ $lbl }}">{{ $lbl }}</option>
                                @endforeach
                                <option value="custom">+ New Custom Label...</option>
                            </select>

                            <div x-show="priceLabelSelect === 'custom'" x-cloak class="pt-2">
                                <input type="text" name="price_label_custom" x-model="priceLabelCustom" placeholder="e.g. AED 25%, USD 20%, Export Special" class="w-full text-xs rounded-xl border-indigo-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Step 2: Excel Column-by-Column Workstation -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4">
                    <div class="border-b border-gray-100 pb-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                        <div>
                            <h3 class="font-bold text-base text-gray-900 flex items-center">
                                <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center me-2">2</span>
                                Paste Excel Columns
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Select a column in Excel, press <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border text-gray-700 font-mono text-[10px]">Ctrl+C</kbd>, and paste into the corresponding box below.
                            </p>
                        </div>

                        <!-- Dual-Paste Tab Splitter Button (Visible if tabs detected) -->
                        <button type="button" @click="autoSplitTabbedData()" x-show="hasTabbedData" x-cloak class="inline-flex items-center px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-xs font-bold border border-purple-200 transition">
                            <svg class="w-3.5 h-3.5 me-1 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            Multi-Column Detected: Auto-Split to Columns
                        </button>
                    </div>

                    <!-- Column Textareas Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <!-- Column A: Item Codes -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center">
                                    <span class="w-4 h-4 rounded bg-gray-200 text-gray-700 text-[10px] font-bold inline-flex items-center justify-center me-1.5">A</span>
                                    Item Codes <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] font-mono font-bold" :class="codesCount > 0 ? 'text-indigo-600' : 'text-gray-400'" x-text="`${codesCount} rows`"></span>
                            </div>
                            <textarea name="item_codes"
                                      x-model="rawCodes"
                                      @input="checkTabs()"
                                      rows="12"
                                      required
                                      placeholder="Paste Item Codes from Excel:&#10;ITM-1001&#10;ITM-1002&#10;ITM-1003&#10;..."
                                      class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 leading-relaxed"></textarea>
                        </div>

                        <!-- Column B: Description (Optional) -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center">
                                    <span class="w-4 h-4 rounded bg-gray-200 text-gray-700 text-[10px] font-bold inline-flex items-center justify-center me-1.5">B</span>
                                    Description <span class="text-gray-400 font-normal lowercase">(optional)</span>
                                </label>
                                <span class="text-[11px] font-mono font-bold" :class="descsCount > 0 ? 'text-indigo-600' : 'text-gray-400'" x-text="`${descsCount} rows`"></span>
                            </div>
                            <textarea name="descriptions"
                                      x-model="rawDescs"
                                      rows="12"
                                      placeholder="Paste Descriptions (Optional):&#10;Stainless Steel Flange 2 inch&#10;High Pressure Valve 50mm&#10;..."
                                      class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 leading-relaxed"></textarea>
                        </div>

                        <!-- Column C: Price Amounts -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center">
                                    <span class="w-4 h-4 rounded bg-gray-200 text-gray-700 text-[10px] font-bold inline-flex items-center justify-center me-1.5">C</span>
                                    Price Amounts <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] font-mono font-bold" :class="pricesCount > 0 ? 'text-indigo-600' : 'text-gray-400'" x-text="`${pricesCount} rows`"></span>
                            </div>
                            <textarea name="prices"
                                      x-model="rawPrices"
                                      rows="12"
                                      required
                                      placeholder="Paste Prices from Excel:&#10;145.50&#10;230.00&#10;18.75&#10;..."
                                      class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 leading-relaxed text-right"></textarea>
                        </div>

                    </div>

                    <!-- Row Matching Status Strip -->
                    <div class="p-4 rounded-xl border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs"
                         :class="isValidMatch ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : (codesCount > 0 && pricesCount > 0 ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-gray-50 border-gray-200 text-gray-600')">
                        <div class="flex items-center space-x-2">
                            <template x-if="isValidMatch">
                                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            </template>
                            <template x-if="!isValidMatch && codesCount > 0 && pricesCount > 0">
                                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            </template>
                            <span class="font-bold" x-text="statusMessage"></span>
                        </div>

                        <div class="flex items-center space-x-4 font-mono text-[11px]">
                            <span>Codes: <strong x-text="codesCount"></strong></span>
                            <span>Prices: <strong x-text="pricesCount"></strong></span>
                            <span x-show="descsCount > 0">Descriptions: <strong x-text="descsCount"></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Live Preview Table (First 5 Rows) -->
                <div x-show="previewRows.length > 0" class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-3">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700">Live Parsed Preview (First 5 Items)</h4>
                        <span class="text-[11px] text-gray-400">Targeting: <strong class="text-indigo-600" x-text="targetLabel"></strong> under <strong class="text-gray-700" x-text="targetList"></strong></span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs divide-y divide-gray-100">
                            <thead class="bg-gray-50 text-gray-500 font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-2 text-left w-12">#</th>
                                    <th class="px-3 py-2 text-left">Item Code</th>
                                    <th class="px-3 py-2 text-left">Description</th>
                                    <th class="px-3 py-2 text-center">Price List</th>
                                    <th class="px-3 py-2 text-center">Price Label</th>
                                    <th class="px-3 py-2 text-right">Price Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(row, idx) in previewRows" :key="idx">
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-3 py-2 font-mono text-gray-400" x-text="idx + 1"></td>
                                        <td class="px-3 py-2 font-mono font-bold text-gray-900" x-text="row.code"></td>
                                        <td class="px-3 py-2 text-gray-600" x-text="row.desc || '(No description)'"></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2 py-0.5 rounded bg-gray-100 font-semibold text-gray-700 text-[10px]" x-text="targetList"></span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[10px]" x-text="targetLabel"></span>
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-bold text-indigo-900">
                                            <span x-text="currency"></span> <span x-text="row.price.toFixed(2)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Submit Action Strip -->
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="clearAll()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition">
                        Clear All Columns
                    </button>

                    <button type="submit"
                            :disabled="!isValidMatch || codesCount === 0"
                            class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl text-sm font-bold shadow-xs transition">
                        <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span x-text="`Import / Override ${codesCount} Item Prices &rarr;`"></span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- Alpine.js Excel Importer Script -->
    <script>
        function excelImporter() {
            return {
                priceListSelect: 'Price List',
                priceListCustom: '',
                currency: 'AED',
                priceLabelSelect: 'AED 30%',
                priceLabelCustom: '',

                rawCodes: '',
                rawDescs: '',
                rawPrices: '',
                hasTabbedData: false,

                get targetList() {
                    return this.priceListSelect === 'custom' && this.priceListCustom.trim()
                        ? this.priceListCustom.trim()
                        : this.priceListSelect;
                },

                get targetLabel() {
                    return this.priceLabelSelect === 'custom' && this.priceLabelCustom.trim()
                        ? this.priceLabelCustom.trim()
                        : this.priceLabelSelect;
                },

                get codeLines() {
                    if (!this.rawCodes.trim()) return [];
                    return this.rawCodes.split(/\r\n|\r|\n/).map(l => l.trim()).filter(l => l.length > 0);
                },

                get descLines() {
                    if (!this.rawDescs.trim()) return [];
                    return this.rawDescs.split(/\r\n|\r|\n/).map(l => l.trim());
                },

                get priceLines() {
                    if (!this.rawPrices.trim()) return [];
                    return this.rawPrices.split(/\r\n|\r|\n/).map(l => l.trim()).filter(l => l.length > 0);
                },

                get codesCount() {
                    return this.codeLines.length;
                },

                get descsCount() {
                    return this.descLines.length;
                },

                get pricesCount() {
                    return this.priceLines.length;
                },

                get isValidMatch() {
                    return this.codesCount > 0 && this.codesCount === this.pricesCount;
                },

                get statusMessage() {
                    if (this.codesCount === 0 && this.pricesCount === 0) {
                        return 'Paste columns from Excel to begin.';
                    }
                    if (this.codesCount > 0 && this.pricesCount === 0) {
                        return `${this.codesCount} item codes pasted. Now paste price amounts in column C.`;
                    }
                    if (this.codesCount === 0 && this.pricesCount > 0) {
                        return `${this.pricesCount} prices pasted. Now paste item codes in column A.`;
                    }
                    if (this.codesCount === this.pricesCount) {
                        return `Ready to import! ${this.codesCount} item codes and prices match perfectly.`;
                    }
                    return `Row count mismatch: ${this.codesCount} Item Codes vs ${this.pricesCount} Prices. Please ensure both columns have equal rows.`;
                },

                get previewRows() {
                    const count = Math.min(this.codesCount, this.pricesCount, 5);
                    const rows = [];
                    for (let i = 0; i < count; i++) {
                        const code = this.codeLines[i] || '';
                        const desc = this.descLines[i] || '';
                        const price = parseFloat(this.priceLines[i].replace(/[^0-9.]/g, '')) || 0;
                        rows.push({ code, desc, price });
                    }
                    return rows;
                },

                checkTabs() {
                    this.hasTabbedData = this.rawCodes.includes('\t');
                },

                autoSplitTabbedData() {
                    const lines = this.rawCodes.split(/\r\n|\r|\n/);
                    const codes = [];
                    const descs = [];
                    const prices = [];

                    lines.forEach(line => {
                        const parts = line.split('\t');
                        if (parts.length >= 3) {
                            codes.push(parts[0].trim());
                            descs.push(parts[1].trim());
                            prices.push(parts[2].trim());
                        } else if (parts.length === 2) {
                            codes.push(parts[0].trim());
                            prices.push(parts[1].trim());
                        } else if (parts[0].trim()) {
                            codes.push(parts[0].trim());
                        }
                    });

                    this.rawCodes = codes.join('\n');
                    if (descs.length > 0) {
                        this.rawDescs = descs.join('\n');
                    }
                    if (prices.length > 0) {
                        this.rawPrices = prices.join('\n');
                    }
                    this.hasTabbedData = false;
                },

                clearAll() {
                    this.rawCodes = '';
                    this.rawDescs = '';
                    this.rawPrices = '';
                    this.hasTabbedData = false;
                }
            };
        }
    </script>
</x-app-layout>
