<div
    x-data="globalSearchModal()"
    @open-global-search.window="openModal()"
    x-cloak
>
    <!-- Modal Backdrop -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-start justify-center pt-16 sm:pt-24 p-4"
        style="display: none;"
        @keydown.window.escape="closeModal()"
        @click.self="closeModal()"
    >
        <!-- Modal Dialog Box -->
        <div
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 -translate-y-4"
            class="bg-white rounded-2xl shadow-2xl border border-gray-200/90 max-w-2xl w-full overflow-hidden flex flex-col max-h-[80vh]"
            @click.outside="closeModal()"
        >
            <!-- Top Search Input Header -->
            <div class="relative flex items-center px-4 py-3.5 border-b border-gray-200/80 gap-3">
                <div class="text-gray-400 shrink-0 flex items-center justify-center w-6 h-6">
                    <template x-if="loading">
                        <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="!loading">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </template>
                </div>

                <input
                    type="text"
                    x-ref="modalSearchInput"
                    x-model="query"
                    @input.debounce.250ms="performSearch()"
                    @keydown.arrow-down.prevent="navigateDown()"
                    @keydown.arrow-up.prevent="navigateUp()"
                    @keydown.enter.prevent="selectCurrent()"
                    placeholder="Search across documents, shipments, reservations, SKUs..."
                    class="w-full bg-transparent text-gray-900 placeholder-gray-400 text-base font-semibold border-0 focus:outline-none focus:ring-0 p-0"
                    autocomplete="off"
                    spellcheck="false"
                />

                <button
                    type="button"
                    @click="closeModal()"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition shrink-0"
                >
                    <kbd class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-500 rounded text-[11px] font-mono">ESC</kbd>
                </button>
            </div>

            <!-- Categories Tabs -->
            <div class="px-4 py-2 bg-slate-50/70 border-b border-gray-100 flex items-center gap-1.5 overflow-x-auto text-xs">
                <button
                    type="button"
                    @click="setCategory('all')"
                    :class="category === 'all' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg transition whitespace-nowrap"
                >
                    All
                </button>
                <button
                    type="button"
                    @click="setCategory('documents')"
                    :class="category === 'documents' ? 'bg-blue-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg transition whitespace-nowrap"
                >
                    Documents
                </button>
                @if(Auth::user()->canManageShipments())
                <button
                    type="button"
                    @click="setCategory('shipment_orders')"
                    :class="category === 'shipment_orders' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg transition whitespace-nowrap"
                >
                    Shipments
                </button>
                @endif
                @if(Auth::user()->canManageReservations())
                <button
                    type="button"
                    @click="setCategory('reservations')"
                    :class="category === 'reservations' ? 'bg-amber-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg transition whitespace-nowrap"
                >
                    Reservations
                </button>
                @endif
                @if(Auth::user()->canManagePriceTracker())
                <button
                    type="button"
                    @click="setCategory('items')"
                    :class="category === 'items' ? 'bg-teal-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-200 font-medium'"
                    class="px-2.5 py-1 rounded-lg transition whitespace-nowrap"
                >
                    Price SKUs
                </button>
                @endif

                <div class="ml-auto text-[11px] text-gray-400 font-mono" x-show="tookMs > 0 && query.length > 0">
                    <span x-text="tookMs + ' ms'"></span>
                </div>
            </div>

            <!-- Results Scrollable List -->
            <div class="overflow-y-auto flex-1 divide-y divide-gray-100 max-h-[55vh]">
                <!-- Initial Helper State when query is empty -->
                <template x-if="query.trim().length === 0">
                    <div class="p-8 text-center text-gray-400 text-xs space-y-2">
                        <div class="text-3xl mb-2">⚡</div>
                        <p class="font-bold text-gray-700">Quick Global Command Search</p>
                        <p class="text-gray-400 max-w-sm mx-auto">
                            Instantly locate Documents, Commercial Invoices, Shipping Orders, Warehouse Shortages, or Product SKUs across the company.
                        </p>
                    </div>
                </template>

                <!-- No Results State -->
                <template x-if="results.length === 0 && query.trim().length > 0 && !loading">
                    <div class="p-8 text-center">
                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-2 text-lg">
                            🔍
                        </div>
                        <p class="text-sm font-bold text-gray-800">No results found</p>
                        <p class="text-xs text-gray-400 mt-1">We couldn't find anything matching "<span x-text="query"></span>".</p>
                    </div>
                </template>

                <!-- Result Items -->
                <template x-for="(item, index) in results" :key="'modal-' + item.category + '-' + item.id">
                    <a
                        :href="item.url"
                        @mouseenter="selectedIndex = index"
                        :class="selectedIndex === index ? 'bg-indigo-50 border-l-4 border-indigo-600 pl-3' : 'hover:bg-slate-50 pl-4'"
                        class="block pr-4 py-3 transition-colors border-l-4 border-transparent"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-base shrink-0 mt-0.5" x-text="item.icon"></div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-gray-900 text-sm tracking-tight truncate" x-text="item.title"></span>
                                        <span
                                            x-show="item.badge"
                                            x-text="item.badge"
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
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
                                    </div>
                                    <div class="text-xs text-gray-500 truncate mt-0.5" x-text="item.subtitle"></div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-bold text-gray-900" x-text="item.meta_primary"></div>
                                <div class="text-[11px] text-gray-400 mt-0.5" x-text="item.meta_secondary"></div>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500">
                <div class="flex items-center gap-2">
                    <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded font-mono text-[10px]">↑↓</kbd>
                    <span>navigate</span>
                    <span class="text-gray-300">•</span>
                    <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded font-mono text-[10px]">↵</kbd>
                    <span>select</span>
                </div>
                <div class="font-bold text-indigo-600" x-text="results.length + ' results'"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function globalSearchModal() {
        return {
            isOpen: false,
            query: '',
            category: 'all',
            results: [],
            tookMs: 0,
            loading: false,
            selectedIndex: -1,
            abortController: null,

            init() {
                window.addEventListener('keydown', (e) => {
                    // Cmd+K or Ctrl+K opens modal from anywhere
                    if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
                        e.preventDefault();
                        this.openModal();
                    }
                });
            },

            openModal() {
                this.isOpen = true;
                this.$nextTick(() => {
                    this.$refs.modalSearchInput?.focus();
                });
            },

            closeModal() {
                this.isOpen = false;
                this.selectedIndex = -1;
            },

            setCategory(cat) {
                this.category = cat;
                if (this.query.trim().length > 0) {
                    this.performSearch();
                }
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

                    if (!response.ok) throw new Error('Search failed');

                    const data = await response.json();
                    this.results = data.results || [];
                    this.tookMs = data.took_ms || 0;
                    this.selectedIndex = this.results.length > 0 ? 0 : -1;
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Modal search error:', err);
                    }
                } finally {
                    this.loading = false;
                }
            },

            navigateDown() {
                if (this.results.length === 0) return;
                this.selectedIndex = (this.selectedIndex + 1) % this.results.length;
            },

            navigateUp() {
                if (this.results.length === 0) return;
                this.selectedIndex = (this.selectedIndex - 1 + this.results.length) % this.results.length;
            },

            selectCurrent() {
                if (this.selectedIndex >= 0 && this.selectedIndex < this.results.length) {
                    const item = this.results[this.selectedIndex];
                    if (item?.url) {
                        window.location.href = item.url;
                    }
                }
            }
        };
    }
</script>
