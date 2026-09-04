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
                                    <th scope="col" class="px-6 py-3.5 text-left">Recorded Tier Prices</th>
                                    <th scope="col" class="sticky right-0 z-20 bg-gray-50 px-6 py-3.5 text-right w-24 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($items as $item)
                                    <tr class="hover:bg-slate-50 transition group">
                                        
                                        <!-- Item Code -->
                                        <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-gray-900 text-xs">
                                            {{ $item->item_code }}
                                        </td>

                                        <!-- Description -->
                                        <td class="px-6 py-4 text-xs text-gray-600 max-w-md">
                                            {{ $item->description ?: '—' }}
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
                                            <form action="{{ route('price-tracker.destroy', $item) }}"
                                                  method="POST"
                                                  class="inline-block"
                                                  data-confirm="Delete item '{{ $item->item_code }}' and all associated prices?"
                                                  data-confirm-title="Delete Item"
                                                  data-confirm-button="Delete Item"
                                                  data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 transition p-1" title="Delete item">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
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
