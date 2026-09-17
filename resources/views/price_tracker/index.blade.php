<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                    <svg class="w-6 h-6 me-2.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    {{ __('Item Price Tracker') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Master catalogue of item codes, descriptions, and multi-tier pricing (AED 30%, 40%, 50%, USD 30%, 40%, 50%, etc.).') }}
                </p>
            </div>
            <a href="{{ route('price-tracker.import') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                Import / Paste from Excel
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Success Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center text-emerald-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 me-2 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Flash Error Message -->
            @if(session('error'))
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center text-red-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 me-2 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-sm shadow-xs">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Currency Types & Live Rates Section -->
            <div x-data="{
                    showAddModal: false,
                    code: '',
                    name: '',
                    symbol: '',
                    rate: '',
                    isFetchingRate: false,
                    rateNote: '',
                    presets: [
                        { code: 'EUR', name: 'Euro', symbol: '€' },
                        { code: 'GBP', name: 'British Pound', symbol: '£' },
                        { code: 'CAD', name: 'Canadian Dollar', symbol: 'CA$' },
                        { code: 'AUD', name: 'Australian Dollar', symbol: 'A$' },
                        { code: 'JPY', name: 'Japanese Yen', symbol: '¥' },
                        { code: 'CHF', name: 'Swiss Franc', symbol: 'CHF' },
                        { code: 'INR', name: 'Indian Rupee', symbol: '₹' },
                        { code: 'SAR', name: 'Saudi Riyal', symbol: '﷼' },
                        { code: 'QAR', name: 'Qatari Riyal', symbol: 'QR' },
                        { code: 'SGD', name: 'Singapore Dollar', symbol: 'S$' }
                    ],
                    applyPreset(p) {
                        this.code = p.code;
                        this.name = p.name;
                        this.symbol = p.symbol;
                        this.fetchLiveRate();
                    },
                    fetchLiveRate() {
                        const c = (this.code || '').trim().toUpperCase();
                        if (c.length < 3) return;
                        this.isFetchingRate = true;
                        this.rateNote = 'Fetching live exchange rate...';
                        fetch(`/api/currencies/rate?from=USD&to=${c}`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.rate) {
                                    this.rate = data.rate;
                                    this.rateNote = `Live rate: 1 USD = ${data.rate} ${c} (${data.source || 'Open API'})`;
                                } else {
                                    this.rateNote = data.message || 'Could not fetch live rate automatically. You can enter manually.';
                                }
                            })
                            .catch(() => {
                                this.rateNote = 'Network error fetching rate. You can enter manually.';
                            })
                            .finally(() => {
                                this.isFetchingRate = false;
                            });
                    }
                }"
                class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                
                <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-slate-50 to-white">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 flex items-center">
                            <svg class="w-4 h-4 me-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Currencies &amp; Live Exchange Rates
                        </h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            Supported document currencies. Currencies other than USD &amp; AED automatically pull live rates from Open Currency API against base USD prices.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <form action="{{ route('price-tracker.currencies.sync') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-200 hover:bg-gray-50 active:bg-gray-100 text-gray-700 rounded-xl text-xs font-semibold shadow-xs transition">
                                <svg class="w-3.5 h-3.5 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Sync Live Rates
                            </button>
                        </form>
                        <button type="button" @click="showAddModal = true" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                            <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Currency
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                                <th class="px-5 py-3">Currency</th>
                                <th class="px-5 py-3">Symbol</th>
                                <th class="px-5 py-3">Rate (vs 1 USD)</th>
                                <th class="px-5 py-3">Pricing Base</th>
                                <th class="px-5 py-3">Last Synced</th>
                                <th class="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($currencies as $c)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-5 py-3 font-medium text-gray-900 flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-[11px] font-mono font-bold {{ $c->is_default ? 'bg-amber-100 text-amber-800 border border-amber-200' : ($c->code === 'AED' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200') }}">
                                            {{ $c->code }}
                                        </span>
                                        <span class="text-xs text-gray-700 font-semibold">{{ $c->name }}</span>
                                        @if($c->is_default)
                                            <span class="text-[10px] bg-amber-50 text-amber-700 font-semibold px-1.5 py-0.5 rounded border border-amber-200">Default Base</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 font-mono font-bold text-gray-700">
                                        {{ $c->symbol ?: '-' }}
                                    </td>
                                    <td class="px-5 py-3 font-mono text-gray-800">
                                        <span class="font-bold text-slate-900">{{ number_format($c->exchange_rate, 4) }}</span>
                                        <span class="text-gray-400 text-[10px] ms-1">({{ $c->code }}/USD)</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($c->code === 'USD')
                                            <span class="text-[11px] text-gray-500 font-medium">Standard Master Catalog (1.0000)</span>
                                        @elseif($c->code === 'AED')
                                            <span class="text-[11px] text-emerald-700 font-medium">Direct AED Tiers or Pegged (3.6725)</span>
                                        @else
                                            <span class="text-[11px] text-indigo-600 font-medium">Auto-converted from USD Base (Live API)</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-[11px] text-gray-500">
                                        {{ $c->rate_updated_at ? $c->rate_updated_at->diffForHumans() : 'Default' }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        @if(!in_array($c->code, ['USD', 'AED']))
                                            <form action="{{ route('price-tracker.currencies.destroy', $c) }}"
                                                  method="POST"
                                                  class="inline-block"
                                                  data-confirm="Delete currency {{ $c->code }} ({{ $c->name }})?"
                                                  data-confirm-title="Delete Currency"
                                                  data-confirm-button="Delete"
                                                  data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 p-1 hover:bg-red-50 rounded transition" title="Delete Currency">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[10px] text-gray-400 italic">Protected</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Add Currency Modal -->
                <div x-show="showAddModal"
                     x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4 text-left border border-gray-100"
                         @click.away="showAddModal = false"
                         x-transition:enter="transition ease-out duration-200 transform"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150 transform"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95">
                        
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <h4 class="text-sm font-bold text-gray-900 flex items-center">
                                <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Add Currency Type
                            </h4>
                            <button type="button" @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
                        </div>

                        <!-- Quick Presets -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Quick Presets</label>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="p in presets" :key="p.code">
                                    <button type="button"
                                            @click="applyPreset(p)"
                                            class="px-2 py-1 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-700 text-gray-700 rounded-lg text-[11px] font-semibold transition"
                                            :class="code === p.code ? 'bg-indigo-100 text-indigo-800 ring-1 ring-indigo-400' : ''"
                                            x-text="`${p.code} (${p.symbol})`">
                                    </button>
                                </template>
                            </div>
                        </div>

                        <form action="{{ route('price-tracker.currencies.store') }}" method="POST" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Currency Code *</label>
                                    <input type="text"
                                           name="code"
                                           x-model="code"
                                           @change="fetchLiveRate()"
                                           placeholder="e.g. EUR"
                                           maxlength="10"
                                           required
                                           class="w-full text-xs uppercase font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Symbol</label>
                                    <input type="text"
                                           name="symbol"
                                           x-model="symbol"
                                           placeholder="e.g. €"
                                           maxlength="10"
                                           class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Currency Name *</label>
                                <input type="text"
                                       name="name"
                                       x-model="name"
                                       placeholder="e.g. Euro"
                                       required
                                       class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-bold text-gray-700">Exchange Rate (vs 1 USD)</label>
                                    <button type="button"
                                            @click="fetchLiveRate()"
                                            :disabled="isFetchingRate || !code"
                                            class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1 disabled:opacity-50">
                                        <svg class="w-3 h-3" :class="isFetchingRate ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Auto-fetch Rate
                                    </button>
                                </div>
                                <input type="number"
                                       name="exchange_rate"
                                       x-model="rate"
                                       step="0.000001"
                                       min="0.000001"
                                       placeholder="Auto-fetched if left blank"
                                       class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <p x-show="rateNote" x-text="rateNote" class="text-[11px] text-indigo-600 mt-1 font-mono"></p>
                            </div>

                            <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                                <button type="button" @click="showAddModal = false" class="px-3.5 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 rounded-xl hover:bg-gray-100 transition">Cancel</button>
                                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition">Save Currency</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Total Items</div>
                    <div class="text-2xl font-black font-mono text-gray-900 mt-1">{{ number_format($totalItems) }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Unique item codes</div>
                </div>

                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Total Price Points</div>
                    <div class="text-2xl font-black font-mono text-indigo-600 mt-1">{{ number_format($totalPrices) }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Recorded tier prices</div>
                </div>

                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Active Price Lists</div>
                    <div class="text-2xl font-black font-mono text-gray-900 mt-1">{{ count($availablePriceLists) }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ implode(', ', array_slice($availablePriceLists, 0, 2)) }}</div>
                </div>

                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Price Labels</div>
                    <div class="text-2xl font-black font-mono text-emerald-600 mt-1">{{ count($availablePriceLabels) }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">AED 30%, USD 40%, etc.</div>
                </div>
            </div>

            <!-- Search & Filters Toolbar -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5">
                <form action="{{ route('price-tracker.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                    
                    <!-- Search query -->
                    <div class="sm:col-span-5 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Search by Item Code or Description..."
                               class="pl-9 w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Price List filter -->
                    <div class="sm:col-span-3">
                        <select name="price_list" class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Price Lists</option>
                            @foreach($availablePriceLists as $list)
                                <option value="{{ $list }}" {{ request('price_list') === $list ? 'selected' : '' }}>
                                    {{ $list }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Price Label filter -->
                    <div class="sm:col-span-2">
                        <select name="price_label" class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Labels</option>
                            @foreach($availablePriceLabels as $lbl)
                                <option value="{{ $lbl }}" {{ request('price_label') === $lbl ? 'selected' : '' }}>
                                    {{ $lbl }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="sm:col-span-2 flex items-center space-x-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition text-center shadow-xs">
                            Filter
                        </button>
                        @if(request()->hasAny(['q', 'price_list', 'price_label']))
                            <a href="{{ route('price-tracker.index') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold transition" title="Reset Filters">
                                &times;
                            </a>
                        @endif
                    </div>

                </form>
            </div>

            <!-- Items Table -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                @if($items->isEmpty())
                    <div class="p-12 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900">No Item Prices Found</h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                            @if(request()->hasAny(['q', 'price_list', 'price_label']))
                                No items match your filter criteria. Try resetting the filters.
                            @else
                                Your item price tracker catalog is currently empty. Use the Excel column importer to paste items and prices in bulk!
                            @endif
                        </p>
                        <a href="{{ route('price-tracker.import') }}" class="inline-flex items-center px-4 py-2 mt-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            + Import First Items from Excel
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 text-left w-48">Item Code</th>
                                    <th scope="col" class="px-6 py-3.5 text-left">Description</th>
                                    <th scope="col" class="px-6 py-3.5 text-right w-36">Net Weight (kg)</th>
                                    <th scope="col" class="px-6 py-3.5 text-left">Recorded Tier Prices</th>
                                    <th scope="col" class="sticky right-0 z-20 bg-gray-50 px-6 py-3.5 text-right w-24 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($items as $item)
                                    <tr class="hover:bg-slate-50 transition group">
                                        
                                        <!-- Item Code -->
                                        <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-gray-900 text-xs">
                                            <a href="{{ route('price-tracker.items.show', $item) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline inline-flex items-center gap-1.5" title="View Item Details">
                                                <span>{{ $item->item_code }}</span>
                                                <svg class="w-3 h-3 text-indigo-400 group-hover:text-indigo-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>
                                        </td>

                                        <!-- Description -->
                                        <td class="px-6 py-4 text-xs text-gray-600 max-w-md">
                                            {{ $item->description ?: '—' }}
                                        </td>

                                        <!-- Net Weight with Quick Edit -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs" x-data="{ editing: false, weight: '{{ $item->net_weight !== null ? number_format($item->net_weight, 3, '.', '') : '' }}', saving: false }">
                                            <div x-show="!editing" class="flex items-center justify-end space-x-1.5 group/wt">
                                                <span class="font-mono font-bold" :class="weight ? 'text-gray-900' : 'text-gray-300'" x-text="weight ? `${parseFloat(weight).toFixed(3)} kg` : '—'"></span>
                                                <button type="button" @click="editing = true; $nextTick(() => $refs.wtInput.focus())" class="text-gray-400 hover:text-indigo-600 p-1 rounded transition opacity-60 group-hover/wt:opacity-100" title="Edit Net Weight">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                </button>
                                            </div>
                                            <form x-show="editing" x-cloak @submit.prevent="
                                                saving = true;
                                                fetch('{{ route('price-tracker.items.update-weight', $item) }}', {
                                                    method: 'PATCH',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                                                        'Accept': 'application/json'
                                                    },
                                                    body: JSON.stringify({ net_weight: weight })
                                                })
                                                .then(res => res.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        weight = data.net_weight !== null ? String(data.net_weight) : '';
                                                        editing = false;
                                                    }
                                                })
                                                .finally(() => saving = false)
                                            " class="flex items-center justify-end space-x-1">
                                                <input type="number" step="0.001" min="0" x-ref="wtInput" x-model="weight" placeholder="0.000" class="w-20 text-xs font-mono text-right rounded border-indigo-400 py-1 px-1.5 focus:ring-indigo-500">
                                                <button type="submit" :disabled="saving" class="p-1 text-emerald-600 hover:text-emerald-800 rounded font-bold" title="Save">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                </button>
                                                <button type="button" @click="editing = false" class="p-1 text-gray-400 hover:text-gray-600 rounded" title="Cancel">&times;</button>
                                            </form>
                                        </td>

                                        <!-- Prices Badges -->
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1.5">
                                                @forelse($item->prices as $p)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-mono font-semibold border {{ str_starts_with($p->price_label, 'USD') ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-indigo-50 text-indigo-800 border-indigo-200' }}"
                                                          title="Price List: {{ $p->price_list }}">
                                                        <span class="font-sans font-bold text-[10px] me-1 text-gray-500">{{ $p->price_label }}:</span>
                                                        <strong>{{ $p->currency }} {{ number_format($p->price, 2) }}</strong>
                                                    </span>
                                                @empty
                                                    <span class="text-xs text-gray-300 italic">No prices recorded</span>
                                                @endforelse
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-6 py-4 whitespace-nowrap text-right text-xs shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                            <div class="flex items-center justify-end space-x-1.5">
                                                <a href="{{ route('price-tracker.items.show', $item) }}" class="text-indigo-600 hover:text-indigo-800 transition p-1 hover:bg-indigo-50 rounded" title="View Item Details & Prices">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                </a>
                                                <form action="{{ route('price-tracker.destroy', $item) }}"
                                                      method="POST"
                                                      class="inline-block"
                                                      data-confirm="Delete item '{{ $item->item_code }}' and all associated prices?"
                                                      data-confirm-title="Delete Item"
                                                      data-confirm-button="Delete Item"
                                                      data-confirm-type="danger">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 transition p-1 hover:bg-red-50 rounded" title="Delete item">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($items->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                            {{ $items->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
