<div x-data="dashboardGlobalSearch()" class="relative w-full mb-6 z-30" @click.outside="closeDropdown()">
    <!-- Search Bar Outer Wrapper -->
    <div class="relative bg-white rounded-2xl shadow-sm border border-gray-200/90 hover:border-indigo-300 focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10 transition-all duration-200">
        
        <!-- Search Input Bar -->
        <div class="flex items-center px-4 py-3.5 gap-3">
            <!-- Search Icon / Loading Spinner -->
            <div class="text-gray-400 shrink-0 flex items-center justify-center w-6 h-6">
                <template x-if="loading">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <template x-if="!loading">
                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </template>
            </div>

            <!-- Input Field -->
            <input
                id="dashboard-global-search-input"
                type="text"
                x-ref="searchInput"
                x-model="query"
                @focus="openDropdown()"
                @input.debounce.250ms="performSearch()"
                @keydown.arrow-down.prevent="navigateDown()"
                @keydown.arrow-up.prevent="navigateUp()"
                @keydown.enter.prevent="selectCurrent()"
                @keydown.escape.prevent="closeDropdown()"
                placeholder="Global instant search: Doc #, Buyer, Shipment BL, AWB, Reservation, Price SKU..."
                class="w-full bg-transparent text-gray-900 placeholder-gray-400 text-sm md:text-base font-medium border-0 focus:outline-none focus:ring-0 p-0"
                autocomplete="off"
                spellcheck="false"
            />

            <!-- Clear Button -->
            <button
                type="button"
                x-show="query.length > 0"
                x-cloak
                @click="clearSearch()"
                class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition shrink-0"
                title="Clear search"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Keyboard Shortcut Hint -->
            <div class="hidden sm:flex items-center gap-1 shrink-0">
                <kbd class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-500 rounded text-[11px] font-mono font-semibold shadow-2xs">/</kbd>
            </div>
        </div>

        <!-- Category Filter Tabs Bar -->
        <div class="px-4 pb-2.5 pt-0 flex items-center justify-between border-t border-gray-100 flex-wrap gap-2 text-xs">
            <div class="flex items-center gap-1.5 overflow-x-auto py-1 scrollbar-none">
                <button
                    type="button"
                    @click="setCategory('all')"
                    :class="category === 'all' ? 'bg-indigo-600 text-white font-bold shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap"
                >
                    <span>All</span>
                    <span x-show="counts.all > 0" x-text="counts.all" class="text-[10px] px-1 rounded-full" :class="category === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'"></span>
                </button>

                <button
                    type="button"
                    @click="setCategory('documents')"
                    :class="category === 'documents' ? 'bg-blue-600 text-white font-bold shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap"
                >
                    <span>📄 Documents</span>
                    <span x-show="counts.documents > 0" x-text="counts.documents" class="text-[10px] px-1 rounded-full" :class="category === 'documents' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'"></span>
                </button>

                @if(Auth::user()->canManageShipments())
                <button
                    type="button"
                    @click="setCategory('shipment_orders')"
                    :class="category === 'shipment_orders' ? 'bg-emerald-600 text-white font-bold shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap"
                >
                    <span>🚢 Shipments</span>
                    <span x-show="counts.shipment_orders > 0" x-text="counts.shipment_orders" class="text-[10px] px-1 rounded-full" :class="category === 'shipment_orders' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'"></span>
                </button>
                @endif

                @if(Auth::user()->canManageReservations())
                <button
                    type="button"
                    @click="setCategory('reservations')"
                    :class="category === 'reservations' ? 'bg-amber-600 text-white font-bold shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap"
                >
                    <span>📦 Reservations</span>
                    <span x-show="counts.reservations > 0" x-text="counts.reservations" class="text-[10px] px-1 rounded-full" :class="category === 'reservations' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'"></span>
                </button>
                @endif

                @if(Auth::user()->canManagePriceTracker())
                <button
                    type="button"
                    @click="setCategory('items')"
                    :class="category === 'items' ? 'bg-teal-600 text-white font-bold shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap"
                >
                    <span>🏷️ Price SKUs</span>
                    <span x-show="counts.items > 0" x-text="counts.items" class="text-[10px] px-1 rounded-full" :class="category === 'items' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'"></span>
                </button>
                @endif
            </div>

            <!-- Execution Time Badge -->
            <div x-show="tookMs > 0 && query.length > 0" x-cloak class="hidden sm:flex items-center text-[11px] text-gray-400 font-mono">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 me-1.5 animate-pulse"></span>
                <span>Found in <strong class="text-gray-700" x-text="tookMs + ' ms'"></strong></span>
            </div>
        </div>
    </div>

    <!-- Search Results Dropdown Overlay -->
    <div
        x-show="isOpen && (results.length > 0 || (query.length > 0 && !loading))"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-98"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-98"
        class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden z-50 max-h-[30rem] flex flex-col"
    >
        <!-- Results Scrollable Container -->
        <div class="overflow-y-auto flex-1 divide-y divide-gray-100 scrollbar-thin">
            <!-- Empty State -->
            <template x-if="results.length === 0 && query.length > 0 && !loading">
                <div class="p-8 text-center">
                    <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-3 text-xl">
                        🔍
                    </div>
                    <h4 class="text-sm font-bold text-gray-900 mb-1">No matching results found</h4>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto">
                        No matches found for "<span class="font-semibold text-gray-800" x-text="query"></span>". Try searching by invoice code (e.g. PI-, INV-), BL number, customer name, or item SKU.
                    </p>
                </div>
            </template>

            <!-- Result Items -->
            <template x-for="(item, index) in results" :key="item.category + '-' + item.id">
                <a
                    :href="item.url"
                    @mouseenter="selectedIndex = index"
                    :class="selectedIndex === index ? 'bg-indigo-50/80 border-l-4 border-indigo-600 pl-3' : 'hover:bg-slate-50 pl-4'"
                    class="block pr-4 py-3 transition-colors border-l-4 border-transparent"
                >
                    <div class="flex items-start justify-between gap-3">
                        <!-- Icon & Main info -->
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-base shrink-0 mt-0.5 shadow-2xs" x-text="item.icon"></div>
                            
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-gray-900 text-sm tracking-tight truncate" x-text="item.title"></span>
                                    
                                    <!-- Badge -->
                                    <span
                                        x-show="item.badge"
                                        x-text="item.badge"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-blue-100 text-blue-800': item.badge_color === 'blue',
                                            'bg-emerald-100 text-emerald-800': item.badge_color === 'emerald',
                                            'bg-amber-100 text-amber-800': item.badge_color === 'amber',
                                            'bg-purple-100 text-purple-800': item.badge_color === 'purple',
                                            'bg-rose-100 text-rose-800': item.badge_color === 'rose',
                                            'bg-teal-100 text-teal-800': item.badge_color === 'teal',
                                            'bg-indigo-100 text-indigo-800': !item.badge_color || item.badge_color === 'indigo'
                                        }"
                                    ></span>

                                    <!-- Category Pill -->
                                    <span class="text-[10px] text-gray-400 font-medium hidden sm:inline" x-text="'• ' + item.category_label"></span>
                                </div>

                                <div class="text-xs text-gray-500 truncate mt-0.5" x-text="item.subtitle"></div>
                            </div>
                        </div>

                        <!-- Right Meta & Score -->
                        <div class="text-right shrink-0">
                            <div class="text-xs font-bold text-gray-900" x-text="item.meta_primary"></div>
                            <div class="text-[11px] text-gray-400 mt-0.5" x-text="item.meta_secondary"></div>
                        </div>
                    </div>
                </a>
            </template>
        </div>

        <!-- Dropdown Footer with Navigation Guide -->
        <div class="px-4 py-2 bg-slate-50 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded font-mono text-[10px] shadow-2xs">↑</kbd>
                    <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded font-mono text-[10px] shadow-2xs">↓</kbd>
                    <span>to navigate</span>
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded font-mono text-[10px] shadow-2xs">↵</kbd>
                    <span>to open</span>
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded font-mono text-[10px] shadow-2xs">esc</kbd>
                    <span>to close</span>
                </span>
            </div>

            <div class="text-right font-semibold text-indigo-600">
                <span x-text="results.length"></span>
                <span x-text="results.length === 1 ? 'match' : 'matches'"></span>
            </div>
        </div>
    </div>
</div>

<script>
    function dashboardGlobalSearch() {
        return {
            query: '',
            category: 'all',
            results: [],
            counts: {
                all: 0,
                documents: 0,
                shipment_orders: 0,
                reservations: 0,
                items: 0
            },
            tookMs: 0,
            loading: false,
            isOpen: false,
            selectedIndex: -1,
            abortController: null,

            openDropdown() {
                this.isOpen = true;
                if (this.query.length >= 1 && this.results.length === 0 && !this.loading) {
                    this.performSearch();
                }
            },

            closeDropdown() {
                this.isOpen = false;
                this.selectedIndex = -1;
            },

            setCategory(cat) {
                this.category = cat;
                if (this.query.trim().length > 0) {
                    this.performSearch();
                }
            },

            clearSearch() {
                this.query = '';
                this.results = [];
                this.tookMs = 0;
                this.selectedIndex = -1;
                this.closeDropdown();
                this.$refs.searchInput.focus();
            },

            async performSearch() {
                const q = this.query.trim();
                if (q.length === 0) {
                    this.results = [];
                    this.tookMs = 0;
                    this.selectedIndex = -1;
                    return;
                }

                if (this.abortController) {
                    this.abortController.abort();
                }
                this.abortController = new AbortController();

                this.loading = true;
                this.isOpen = true;

                try {
                    const params = new URLSearchParams({
                        q: q,
                        category: this.category,
                        limit: 10
                    });

                    const response = await fetch(`/api/global-search?${params.toString()}`, {
                        signal: this.abortController.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Search request failed');
                    }

                    const data = await response.json();
                    this.results = data.results || [];
                    this.counts = data.counts || this.counts;
                    this.tookMs = data.took_ms || 0;
                    this.selectedIndex = this.results.length > 0 ? 0 : -1;
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Global search error:', err);
                    }
                } finally {
                    this.loading = false;
                }
            },

            navigateDown() {
                if (!this.isOpen || this.results.length === 0) return;
                this.selectedIndex = (this.selectedIndex + 1) % this.results.length;
                this.scrollToSelected();
            },

            navigateUp() {
                if (!this.isOpen || this.results.length === 0) return;
                this.selectedIndex = (this.selectedIndex - 1 + this.results.length) % this.results.length;
                this.scrollToSelected();
            },

            scrollToSelected() {
                this.$nextTick(() => {
                    const activeEl = document.querySelector('.border-indigo-600');
                    if (activeEl) {
                        activeEl.scrollIntoView({ block: 'nearest' });
                    }
                });
            },

            selectCurrent() {
                if (this.selectedIndex >= 0 && this.selectedIndex < this.results.length) {
                    const item = this.results[this.selectedIndex];
                    if (item && item.url) {
                        window.location.href = item.url;
                    }
                }
            }
        };
    }
</script>
