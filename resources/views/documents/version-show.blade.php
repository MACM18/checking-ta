<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('documents.show', $document) }}" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition" title="Back to Document">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-2xl font-black text-gray-900">{{ $document->document_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-200">
                            Historical Snapshot: Version {{ $version->version_number }}
                        </span>
                        @if($version->version_number === $document->current_version)
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200">
                                Current Active Version
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Recorded on {{ $version->created_at->format('M d, Y H:i:s') }} by {{ $version->creator?->name ?? 'User' }} &bull; "{{ $version->change_summary }}"
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                @if(Auth::user()->canEdit() && $version->version_number !== $document->current_version)
                    <form action="{{ route('documents.versions.restore', [$document, $version->version_number]) }}"
                          method="POST"
                          data-confirm="Restore document to Version {{ $version->version_number }}? This creates a new active version (v{{ $document->current_version + 1 }}) reflecting these exact contents."
                          data-confirm-title="Restore Version {{ $version->version_number }}"
                          data-confirm-button="Yes, Restore Version"
                          data-confirm-type="primary">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-bold text-xs rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Restore This Version
                        </button>
                    </form>
                @endif
                <a href="{{ route('documents.show', $document) }}" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 shadow-sm transition">
                    Return to Current Version
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $snap = $version->snapshot_data;
        $docData = $snap['document'] ?? [];
        $itemsData = $snap['items'] ?? [];
        $shipData = $snap['shipment_costs'] ?? [];
        $currency = $docData['currency'] ?? 'USD';
        $finDiff = $diff['financial_deltas'] ?? [];
        $totalDiff = $finDiff['final_total']['diff'] ?? 0;
        $netWtDiff = $finDiff['total_net_weight']['diff'] ?? 0;
    @endphp

    <div class="py-8" x-data="{ activeTab: (new URLSearchParams(window.location.search).get('view') === 'snapshot') ? 'snapshot' : 'diff', filterChangesOnly: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- GitHub Comparison Header & Stats Banner -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-white/10 text-white border border-white/10">
                                git diff
                            </span>
                            <h2 class="text-sm font-bold tracking-wide uppercase text-slate-200">
                                Comparing Version {{ $version->version_number }} with Current Version {{ $document->current_version }}
                            </h2>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            @if($version->version_number === $document->current_version)
                                Viewing the snapshot of the current active version. Content is identical.
                            @elseif($diff['has_changes'])
                                Showing additions (<span class="text-emerald-400 font-bold">+</span>), deletions (<span class="text-rose-400 font-bold">-</span>), and modifications (<span class="text-amber-300 font-bold">~</span>) in Current (v{{ $document->current_version }}) compared to this snapshot (v{{ $version->version_number }}).
                            @else
                                No changes detected between Version {{ $version->version_number }} and Current Version {{ $document->current_version }}.
                            @endif
                        </p>
                    </div>

                    <!-- GitHub Diff Stats Pills -->
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        @if($diff['has_changes'])
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md font-mono font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                +{{ $diff['summary']['additions'] }} additions
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md font-mono font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                -{{ $diff['summary']['deletions'] }} deletions
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                ~{{ $diff['summary']['modifications'] }} modified
                            </span>

                            @if(!$document->isWeightOnly() && abs($totalDiff) > 0.009)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md font-mono font-bold {{ $totalDiff > 0 ? 'bg-emerald-400 text-slate-950' : 'bg-rose-400 text-slate-950' }}">
                                    Total: {{ $totalDiff > 0 ? '+' : '-' }}{{ $currency }} {{ number_format(abs($totalDiff), 2) }}
                                </span>
                            @endif

                            @if(abs($netWtDiff) > 0.0009)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md font-mono font-bold bg-indigo-500/20 text-indigo-200 border border-indigo-500/30">
                                    Net Wt: {{ $netWtDiff > 0 ? '+' : '' }}{{ number_format($netWtDiff, 3) }} kg
                                </span>
                            @endif
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-md font-mono font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                &#10003; 0 Differences (Identical)
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="flex items-center justify-between border-t border-gray-200 bg-slate-50 px-6 py-2">
                    <div class="flex space-x-2">
                        <button type="button"
                                @click="activeTab = 'diff'"
                                :class="activeTab === 'diff' ? 'border-indigo-600 text-indigo-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-100 font-semibold'"
                                class="inline-flex items-center px-4 py-2 border rounded-lg text-xs transition cursor-pointer">
                            <svg class="w-4 h-4 me-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            <span>GitHub Diff View</span>
                            @if($diff['has_changes'])
                                <span class="ms-2 px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    {{ $diff['summary']['total_changes'] }}
                                </span>
                            @endif
                        </button>

                        <button type="button"
                                @click="activeTab = 'snapshot'"
                                :class="activeTab === 'snapshot' ? 'border-indigo-600 text-indigo-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-100 font-semibold'"
                                class="inline-flex items-center px-4 py-2 border rounded-lg text-xs transition cursor-pointer">
                            <svg class="w-4 h-4 me-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Historical Snapshot (v{{ $version->version_number }})</span>
                        </button>
                    </div>

                    <div class="hidden sm:flex items-center space-x-2 text-xs font-mono text-gray-500">
                        <span>Base:</span>
                        <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800 font-bold">v{{ $version->version_number }}</span>
                        <span>&rarr;</span>
                        <span>Current:</span>
                        <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold">v{{ $document->current_version }}</span>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 1: GITHUB DIFF VIEW ================= -->
            <div x-show="activeTab === 'diff'" class="space-y-6">

                <!-- Financial & Weight Deltas Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Final Total -->
                    @if(!$document->isWeightOnly())
                        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4">
                            <div class="flex justify-between items-start">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Final Total</span>
                                @if(abs($totalDiff) > 0.009)
                                    <span class="px-2 py-0.5 rounded font-mono font-bold text-xs {{ $totalDiff > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $totalDiff > 0 ? '+' : '-' }}{{ $currency }} {{ number_format(abs($totalDiff), 2) }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded font-mono text-xs bg-gray-100 text-gray-600">No Change</span>
                                @endif
                            </div>
                            <div class="mt-3 flex items-baseline justify-between text-xs font-mono">
                                <div>
                                    <span class="text-gray-400 block text-[10px]">v{{ $version->version_number }} (Snapshot)</span>
                                    <span class="text-gray-600 font-semibold">{{ $currency }} {{ number_format($finDiff['final_total']['old'] ?? 0, 2) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-indigo-500 block text-[10px]">v{{ $document->current_version }} (Current)</span>
                                    <span class="text-gray-900 font-black text-sm">{{ $currency }} {{ number_format($finDiff['final_total']['current'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Subtotal -->
                        @php $subDiff = $finDiff['subtotal']['diff'] ?? 0; @endphp
                        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4">
                            <div class="flex justify-between items-start">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Subtotal</span>
                                @if(abs($subDiff) > 0.009)
                                    <span class="px-2 py-0.5 rounded font-mono font-bold text-xs {{ $subDiff > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $subDiff > 0 ? '+' : '-' }}{{ $currency }} {{ number_format(abs($subDiff), 2) }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded font-mono text-xs bg-gray-100 text-gray-600">No Change</span>
                                @endif
                            </div>
                            <div class="mt-3 flex items-baseline justify-between text-xs font-mono">
                                <div>
                                    <span class="text-gray-400 block text-[10px]">v{{ $version->version_number }} (Snapshot)</span>
                                    <span class="text-gray-600 font-semibold">{{ $currency }} {{ number_format($finDiff['subtotal']['old'] ?? 0, 2) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-indigo-500 block text-[10px]">v{{ $document->current_version }} (Current)</span>
                                    <span class="text-gray-900 font-black text-sm">{{ $currency }} {{ number_format($finDiff['subtotal']['current'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Net Weight -->
                    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Net Weight</span>
                            @if(abs($netWtDiff) > 0.0009)
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-xs {{ $netWtDiff > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $netWtDiff > 0 ? '+' : '' }}{{ number_format($netWtDiff, 3) }} kg
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded font-mono text-xs bg-gray-100 text-gray-600">No Change</span>
                            @endif
                        </div>
                        <div class="mt-3 flex items-baseline justify-between text-xs font-mono">
                            <div>
                                <span class="text-gray-400 block text-[10px]">v{{ $version->version_number }} (Snapshot)</span>
                                <span class="text-gray-600 font-semibold">{{ number_format($finDiff['total_net_weight']['old'] ?? 0, 3) }} kg</span>
                            </div>
                            <div class="text-right">
                                <span class="text-indigo-500 block text-[10px]">v{{ $document->current_version }} (Current)</span>
                                <span class="text-gray-900 font-black text-sm">{{ number_format($finDiff['total_net_weight']['current'] ?? 0, 3) }} kg</span>
                            </div>
                        </div>
                    </div>

                    <!-- Gross Weight -->
                    @php $grossWtDiff = $finDiff['total_gross_weight']['diff'] ?? 0; @endphp
                    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-4">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Gross Weight</span>
                            @if(abs($grossWtDiff) > 0.0009)
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-xs {{ $grossWtDiff > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $grossWtDiff > 0 ? '+' : '' }}{{ number_format($grossWtDiff, 3) }} kg
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded font-mono text-xs bg-gray-100 text-gray-600">No Change</span>
                            @endif
                        </div>
                        <div class="mt-3 flex items-baseline justify-between text-xs font-mono">
                            <div>
                                <span class="text-gray-400 block text-[10px]">v{{ $version->version_number }} (Snapshot)</span>
                                <span class="text-gray-600 font-semibold">{{ number_format($finDiff['total_gross_weight']['old'] ?? 0, 3) }} kg</span>
                            </div>
                            <div class="text-right">
                                <span class="text-indigo-500 block text-[10px]">v{{ $document->current_version }} (Current)</span>
                                <span class="text-gray-900 font-black text-sm">{{ number_format($finDiff['total_gross_weight']['current'] ?? 0, 3) }} kg</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Changed Header Properties (GitHub Commit Style) -->
                @if(!empty($diff['header_diffs']))
                    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-xs font-bold text-gray-600 uppercase tracking-wider">Document Properties Changed</span>
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-100 text-amber-800">
                                    {{ count($diff['header_diffs']) }} field(s)
                                </span>
                            </div>
                            <span class="text-[11px] text-gray-500 font-mono">Comparing header attributes</span>
                        </div>
                        <div class="divide-y divide-gray-100 text-xs font-mono">
                            @foreach($diff['header_diffs'] as $hDiff)
                                <div class="p-4 space-y-1.5">
                                    <span class="font-bold text-gray-700 font-sans block text-xs uppercase tracking-wider">{{ $hDiff['label'] }}</span>
                                    <div class="space-y-1">
                                        <div class="flex items-start bg-rose-50/70 border-l-4 border-rose-500 px-3 py-1.5 rounded-r text-rose-900">
                                            <span class="font-black text-rose-600 select-none w-6 shrink-0">-</span>
                                            <span class="text-rose-700 text-[11px] me-2 font-sans font-semibold shrink-0">v{{ $version->version_number }}:</span>
                                            <span class="line-through break-all">{{ $hDiff['old'] }}</span>
                                        </div>
                                        <div class="flex items-start bg-emerald-50/70 border-l-4 border-emerald-500 px-3 py-1.5 rounded-r text-emerald-900">
                                            <span class="font-black text-emerald-600 select-none w-6 shrink-0">+</span>
                                            <span class="text-emerald-700 text-[11px] me-2 font-sans font-semibold shrink-0">Current:</span>
                                            <span class="font-bold break-all">{{ $hDiff['current'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Unified Line Items Diff (GitHub Style Table) -->
                <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider flex items-center gap-2">
                                <span>Line Items Diff</span>
                                <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-200 text-slate-800">
                                    {{ count($diff['items_diff']) }} total
                                </span>
                            </h3>
                        </div>

                        <!-- Diff Filter Toolbar -->
                        <div class="flex items-center space-x-2">
                            <div class="inline-flex rounded-lg border border-gray-300 p-0.5 bg-white shadow-2xs text-xs">
                                <button type="button"
                                        @click="filterChangesOnly = false"
                                        :class="!filterChangesOnly ? 'bg-indigo-600 text-white font-bold' : 'text-gray-600 hover:text-gray-900'"
                                        class="px-3 py-1 rounded-md transition cursor-pointer">
                                    All Items ({{ count($diff['items_diff']) }})
                                </button>
                                <button type="button"
                                        @click="filterChangesOnly = true"
                                        :class="filterChangesOnly ? 'bg-indigo-600 text-white font-bold' : 'text-gray-600 hover:text-gray-900'"
                                        class="px-3 py-1 rounded-md transition cursor-pointer flex items-center gap-1.5">
                                    <span>Changes Only</span>
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono" :class="filterChangesOnly ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-700'">
                                        {{ $diff['summary']['additions'] + $diff['summary']['deletions'] + $diff['summary']['modifications'] }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-100/80 text-gray-600 font-bold uppercase tracking-wider border-b border-gray-200">
                                <tr>
                                    <th class="px-3 py-2.5 text-center w-12 font-mono">+/-</th>
                                    <th class="px-4 py-2.5 text-left w-36">Item Code</th>
                                    <th class="px-4 py-2.5 text-left">Description</th>
                                    <th class="px-4 py-2.5 text-right w-28">Quantity</th>
                                    @if(!$document->isWeightOnly())
                                        <th class="px-4 py-2.5 text-right w-28">Unit Price</th>
                                        <th class="px-4 py-2.5 text-right w-32">Total ({{ $currency }})</th>
                                    @else
                                        <th class="px-4 py-2.5 text-right w-28">Unit Net Wt</th>
                                        <th class="px-4 py-2.5 text-right w-32">Total Net Wt</th>
                                    @endif
                                    <th class="px-4 py-2.5 text-left w-52">Change Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 font-mono">
                                @forelse($diff['items_diff'] as $item)
                                    @php
                                        $type = $item['type'];
                                        $isAdded = $type === 'added';
                                        $isRemoved = $type === 'removed';
                                        $isModified = $type === 'modified';
                                        $isUnchanged = $type === 'unchanged';
                                    @endphp

                                    {{-- 1. ADDED ITEM ROW (+) --}}
                                    @if($isAdded)
                                        <tr class="bg-emerald-50/70 border-l-4 border-emerald-500 hover:bg-emerald-100/60 transition">
                                            <td class="px-3 py-3 text-center">
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded font-black text-sm bg-emerald-100 text-emerald-800 border border-emerald-300 select-none">+</span>
                                            </td>
                                            <td class="px-4 py-3 font-bold text-emerald-950">
                                                + {{ $item['item_code'] }}
                                            </td>
                                            <td class="px-4 py-3 font-sans text-emerald-900">
                                                {{ $item['current']['description'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-emerald-950">
                                                +{{ number_format($item['current']['unit_amount'] ?? 1, 2) }}
                                            </td>
                                            @if(!$document->isWeightOnly())
                                                <td class="px-4 py-3 text-right text-emerald-900">
                                                    {{ number_format($item['current']['unit_price'] ?? 0, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-black text-emerald-950">
                                                    +{{ number_format($item['current']['total_amount'] ?? 0, 2) }}
                                                </td>
                                            @else
                                                <td class="px-4 py-3 text-right text-emerald-900">-</td>
                                                <td class="px-4 py-3 text-right font-black text-emerald-950">-</td>
                                            @endif
                                            <td class="px-4 py-3 font-sans">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    + Added in Current (v{{ $document->current_version }})
                                                </span>
                                            </td>
                                        </tr>

                                    {{-- 2. REMOVED ITEM ROW (-) --}}
                                    @elseif($isRemoved)
                                        <tr class="bg-rose-50/70 border-l-4 border-rose-500 hover:bg-rose-100/60 transition">
                                            <td class="px-3 py-3 text-center">
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded font-black text-sm bg-rose-100 text-rose-800 border border-rose-300 select-none">-</span>
                                            </td>
                                            <td class="px-4 py-3 font-bold text-rose-950 line-through">
                                                - {{ $item['item_code'] }}
                                            </td>
                                            <td class="px-4 py-3 font-sans text-rose-900 line-through">
                                                {{ $item['old']['description'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-rose-900 line-through">
                                                -{{ number_format($item['old']['unit_amount'] ?? 1, 2) }}
                                            </td>
                                            @if(!$document->isWeightOnly())
                                                <td class="px-4 py-3 text-right text-rose-900 line-through">
                                                    {{ number_format($item['old']['unit_price'] ?? 0, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-black text-rose-950 line-through">
                                                    -{{ number_format($item['old']['total_amount'] ?? 0, 2) }}
                                                </td>
                                            @else
                                                <td class="px-4 py-3 text-right text-rose-900 line-through">-</td>
                                                <td class="px-4 py-3 text-right font-black text-rose-950 line-through">-</td>
                                            @endif
                                            <td class="px-4 py-3 font-sans">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                    - Removed in Current (v{{ $document->current_version }})
                                                </span>
                                            </td>
                                        </tr>

                                    {{-- 3. MODIFIED ITEM ROW (DUAL SUB-ROWS) --}}
                                    @elseif($isModified)
                                        @php
                                            $qtyDelta = $item['deltas']['unit_amount'] ?? 0;
                                            $priceDelta = $item['deltas']['unit_price'] ?? 0;
                                            $totalDelta = $item['deltas']['total_amount'] ?? 0;
                                        @endphp
                                        <!-- Old Snapshot Line (-) -->
                                        <tr class="bg-rose-50/40 border-l-4 border-rose-400">
                                            <td class="px-3 py-2 text-center">
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded font-black text-xs bg-rose-100 text-rose-700 select-none">-</span>
                                            </td>
                                            <td class="px-4 py-2 font-bold text-rose-900">
                                                - {{ $item['item_code'] }} <span class="text-[10px] text-rose-600 font-sans">(v{{ $version->version_number }})</span>
                                            </td>
                                            <td class="px-4 py-2 font-sans text-rose-800 text-xs">
                                                {{ $item['old']['description'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-2 text-right text-rose-800">
                                                {{ number_format($item['old']['unit_amount'] ?? 1, 2) }}
                                            </td>
                                            @if(!$document->isWeightOnly())
                                                <td class="px-4 py-2 text-right text-rose-800">
                                                    {{ number_format($item['old']['unit_price'] ?? 0, 2) }}
                                                </td>
                                                <td class="px-4 py-2 text-right font-bold text-rose-900">
                                                    {{ number_format($item['old']['total_amount'] ?? 0, 2) }}
                                                </td>
                                            @else
                                                <td class="px-4 py-2 text-right text-rose-800">-</td>
                                                <td class="px-4 py-2 text-right font-bold text-rose-900">-</td>
                                            @endif
                                            <td class="px-4 py-2 font-sans text-[11px] text-rose-600 italic">
                                                Archived value
                                            </td>
                                        </tr>
                                        <!-- Current Line (+) -->
                                        <tr class="bg-emerald-50/50 border-l-4 border-emerald-500 border-b-2 border-b-gray-200">
                                            <td class="px-3 py-2.5 text-center">
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded font-black text-xs bg-emerald-100 text-emerald-800 select-none">+</span>
                                            </td>
                                            <td class="px-4 py-2.5 font-bold text-emerald-950">
                                                + {{ $item['item_code'] }} <span class="text-[10px] text-emerald-700 font-sans font-bold">(Current)</span>
                                            </td>
                                            <td class="px-4 py-2.5 font-sans text-emerald-900 text-xs">
                                                {{ $item['current']['description'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-bold text-emerald-950">
                                                {{ number_format($item['current']['unit_amount'] ?? 1, 2) }}
                                                @if(abs($qtyDelta) > 0.0001)
                                                    <span class="block text-[10px] {{ $qtyDelta > 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                                        ({{ $qtyDelta > 0 ? '+' : '' }}{{ number_format($qtyDelta, 2) }})
                                                    </span>
                                                @endif
                                            </td>
                                            @if(!$document->isWeightOnly())
                                                <td class="px-4 py-2.5 text-right text-emerald-950">
                                                    {{ number_format($item['current']['unit_price'] ?? 0, 2) }}
                                                    @if(abs($priceDelta) > 0.0001)
                                                        <span class="block text-[10px] {{ $priceDelta > 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                                            ({{ $priceDelta > 0 ? '+' : '' }}{{ number_format($priceDelta, 2) }})
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 text-right font-black text-emerald-950">
                                                    {{ number_format($item['current']['total_amount'] ?? 0, 2) }}
                                                    @if(abs($totalDelta) > 0.0001)
                                                        <span class="block text-[10px] {{ $totalDelta > 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                                            ({{ $totalDelta > 0 ? '+' : '' }}{{ number_format($totalDelta, 2) }})
                                                        </span>
                                                    @endif
                                                </td>
                                            @else
                                                <td class="px-4 py-2.5 text-right text-emerald-950">-</td>
                                                <td class="px-4 py-2.5 text-right font-black text-emerald-950">-</td>
                                            @endif
                                            <td class="px-4 py-2.5 font-sans">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                                    ~ Modified
                                                </span>
                                            </td>
                                        </tr>

                                    {{-- 4. UNCHANGED ITEM ROW --}}
                                    @elseif($isUnchanged)
                                        <tr x-show="!filterChangesOnly" class="bg-white hover:bg-gray-50/80 border-l-4 border-transparent text-gray-600 transition">
                                            <td class="px-3 py-2.5 text-center text-gray-300 select-none"> </td>
                                            <td class="px-4 py-2.5 font-bold text-gray-700">
                                                {{ $item['item_code'] }}
                                            </td>
                                            <td class="px-4 py-2.5 font-sans text-gray-600">
                                                {{ $item['old']['description'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                {{ number_format($item['old']['unit_amount'] ?? 1, 2) }}
                                            </td>
                                            @if(!$document->isWeightOnly())
                                                <td class="px-4 py-2.5 text-right">
                                                    {{ number_format($item['old']['unit_price'] ?? 0, 2) }}
                                                </td>
                                                <td class="px-4 py-2.5 text-right font-semibold text-gray-800">
                                                    {{ number_format($item['old']['total_amount'] ?? 0, 2) }}
                                                </td>
                                            @else
                                                <td class="px-4 py-2.5 text-right">-</td>
                                                <td class="px-4 py-2.5 text-right font-semibold text-gray-800">-</td>
                                            @endif
                                            <td class="px-4 py-2.5 font-sans">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600">
                                                    Unchanged
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-6 text-center text-gray-400 font-sans">
                                            No line items recorded.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Shipment Costs Diff (if any) -->
                @if(!empty($diff['shipment_diff']))
                    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="px-6 py-3.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="font-bold text-sm text-gray-800 uppercase tracking-wider">Shipment Costs Diff</h3>
                            <span class="text-xs text-gray-500 font-mono">{{ $currency }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs font-mono">
                                <thead class="bg-gray-100/70 text-gray-600 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="px-3 py-2 text-center w-12">+/-</th>
                                        <th class="px-4 py-2 text-left">Method</th>
                                        <th class="px-4 py-2 text-right">Checked Weight (kg)</th>
                                        <th class="px-4 py-2 text-right">Given Amount ({{ $currency }})</th>
                                        <th class="px-4 py-2 text-left font-sans">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($diff['shipment_diff'] as $sDiff)
                                        <tr class="{{ $sDiff['type'] === 'added' ? 'bg-emerald-50/70 border-l-4 border-emerald-500' : ($sDiff['type'] === 'removed' ? 'bg-rose-50/70 border-l-4 border-rose-500' : ($sDiff['type'] === 'modified' ? 'bg-amber-50/50 border-l-4 border-amber-400' : 'bg-white')) }}">
                                            <td class="px-3 py-2.5 text-center font-bold">
                                                @if($sDiff['type'] === 'added') <span class="text-emerald-700 font-black">+</span>
                                                @elseif($sDiff['type'] === 'removed') <span class="text-rose-700 font-black">-</span>
                                                @elseif($sDiff['type'] === 'modified') <span class="text-amber-700 font-black">~</span>
                                                @else <span class="text-gray-300"> </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 font-bold uppercase">
                                                {{ str_replace('_', ' ', $sDiff['method']) }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                @if($sDiff['current'])
                                                    {{ number_format($sDiff['current']['checked_weight'] ?? 0, 3) }}
                                                    @if(abs($sDiff['diff_weight'] ?? 0) > 0.001)
                                                        <span class="text-[10px] {{ ($sDiff['diff_weight'] ?? 0) > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                            ({{ ($sDiff['diff_weight'] ?? 0) > 0 ? '+' : '' }}{{ number_format($sDiff['diff_weight'], 3) }})
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="line-through text-rose-700">{{ number_format($sDiff['old']['checked_weight'] ?? 0, 3) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-bold">
                                                @if($sDiff['current'])
                                                    {{ number_format($sDiff['current']['given_amount'] ?? 0, 2) }}
                                                    @if(abs($sDiff['diff_amount'] ?? 0) > 0.01)
                                                        <span class="text-[10px] {{ ($sDiff['diff_amount'] ?? 0) > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                            ({{ ($sDiff['diff_amount'] ?? 0) > 0 ? '+' : '' }}{{ number_format($sDiff['diff_amount'], 2) }})
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="line-through text-rose-700">{{ number_format($sDiff['old']['given_amount'] ?? 0, 2) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 font-sans">
                                                @if($sDiff['type'] === 'added')
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">+ Added</span>
                                                @elseif($sDiff['type'] === 'removed')
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">- Removed</span>
                                                @elseif($sDiff['type'] === 'modified')
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">~ Modified</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">Unchanged</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>

            <!-- ================= TAB 2: HISTORICAL SNAPSHOT VIEW ================= -->
            <div x-show="activeTab === 'snapshot'" class="space-y-6">

                <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-r-lg text-purple-900 text-xs flex items-center justify-between">
                    <div>
                        <span class="font-bold">Archived Snapshot:</span> Viewing exact recorded data from Version {{ $version->version_number }}.
                        @if($diff['summary']['additions'] > 0)
                            <span class="ms-2 text-indigo-700 font-semibold font-sans">
                                (Note: Current active version has {{ $diff['summary']['additions'] }} newly added item(s) not present here).
                            </span>
                        @endif
                    </div>
                    <button type="button" @click="activeTab = 'diff'" class="font-bold text-indigo-700 hover:text-indigo-900 underline text-xs cursor-pointer">
                        Switch to Diff View &rarr;
                    </button>
                </div>

                <!-- Header Details -->
                <div class="bg-white rounded-xl shadow-xs border border-gray-100 p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Company / Recipient</h4>
                            <div class="text-lg font-bold text-gray-900 mt-1">{{ $docData['company_name'] ?? '-' }}</div>
                            <div class="text-sm font-semibold text-indigo-600 mt-0.5">{{ $docData['country'] ?? '-' }}</div>
                            @if(!empty($docData['address']))
                                <div class="text-xs text-gray-600 mt-2 bg-gray-50 p-2.5 rounded-lg whitespace-pre-line border border-gray-100">
                                    {{ $docData['address'] }}
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3 bg-slate-50/60 p-4 rounded-xl border border-slate-100 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-500 uppercase">Document Date</span>
                                <span class="font-semibold text-gray-900">{{ $docData['document_date'] ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-500 uppercase">Currency</span>
                                <span class="px-2 py-0.5 rounded font-mono font-bold bg-indigo-100 text-indigo-800">{{ $currency }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-500 uppercase">Status</span>
                                <span class="capitalize font-semibold text-emerald-700">{{ $docData['status'] ?? 'draft' }}</span>
                            </div>
                        </div>
                    </div>

                    @if(!empty($docData['contact_details']))
                        <div class="border-t border-gray-100 pt-3 text-xs text-gray-700">
                            <span class="font-bold text-gray-400 uppercase tracking-wider block mb-1">Contact Details</span>
                            {{ $docData['contact_details'] }}
                        </div>
                    @endif
                </div>

                <!-- Items Snapshot Table with Inline Comparison Indicators -->
                <div class="bg-white rounded-xl shadow-xs border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="font-bold text-sm text-gray-800 uppercase tracking-wider">Line Items Snapshot ({{ count($itemsData) }})</h3>
                        <span class="text-xs text-gray-500 font-mono">{{ $currency }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 text-left w-12">#</th>
                                    <th class="px-6 py-3 text-left">Item Code</th>
                                    <th class="px-6 py-3 text-left">Description</th>
                                    <th class="px-6 py-3 text-right">Unit Amount</th>
                                    @if(!$document->isWeightOnly())
                                        <th class="px-6 py-3 text-right">Unit Price</th>
                                        <th class="px-6 py-3 text-right">Total ({{ $currency }})</th>
                                    @else
                                        <th class="px-6 py-3 text-right">Unit Net Wt (kg)</th>
                                        <th class="px-6 py-3 text-right">Total Net Wt (kg)</th>
                                    @endif
                                    <th class="px-6 py-3 text-left">Status vs Current</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($itemsData as $idx => $item)
                                    @php
                                        $totAmt = floatval($item['total_amount'] ?? 0);
                                        $unitPrc = floatval($item['unit_price'] ?? 0);
                                        $code = $item['item_code'] ?? '-';
                                        $codeUpper = strtoupper(trim($code));
                                        $isDiscount = $totAmt < 0 || in_array($codeUpper, ['DISCOUNT', 'DISC']);
                                        $isTax = in_array($codeUpper, ['TAX', 'VAT', 'TAX / VAT', 'TAX/VAT']) && $totAmt >= 0;
                                        $isAddition = in_array($codeUpper, ['ADDITION', 'ADD', 'SURCHARGE']) && $totAmt >= 0;
                                        $isAdjustment = $isDiscount || $isTax || $isAddition;

                                        $itemStatus = $diff['historical_item_status_map'][$idx] ?? null;
                                        $statusType = $itemStatus['type'] ?? 'unchanged';
                                    @endphp
                                    <tr class="{{ $statusType === 'removed' ? 'bg-rose-50/40' : ($statusType === 'modified' ? 'bg-amber-50/40' : '') }}">
                                        <td class="px-6 py-3 text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                        <td class="px-6 py-3 font-mono font-bold text-gray-900">
                                            <div class="flex items-center space-x-1.5">
                                                @if($isDiscount)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-700">Discount (-)</span>
                                                @elseif($isTax)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800">Tax / VAT (+)</span>
                                                @elseif($isAddition)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-700">Addition (+)</span>
                                                @else
                                                    <span>{{ $code }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-gray-700">{{ $item['description'] ?? '-' }}</td>
                                        <td class="px-6 py-3 text-right font-mono">
                                            @if($isAdjustment)
                                                <span class="text-gray-400 font-bold">&mdash;</span>
                                            @else
                                                {{ number_format($item['unit_amount'] ?? 1, 2) }}
                                            @endif
                                        </td>
                                        @if(!$document->isWeightOnly())
                                            <td class="px-6 py-3 text-right font-mono {{ $unitPrc < 0 ? 'text-rose-600 font-bold' : '' }}">
                                                @if($isAdjustment)
                                                    <span class="text-gray-400 font-bold">&mdash;</span>
                                                @else
                                                    {{ number_format($unitPrc, 2) }}
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 text-right font-mono font-bold {{ $totAmt < 0 ? 'text-rose-600' : 'text-gray-900' }}">
                                                {{ $totAmt < 0 ? '-' . number_format(abs($totAmt), 2) : number_format($totAmt, 2) }}
                                            </td>
                                        @else
                                            <td class="px-6 py-3 text-right font-mono text-gray-700">
                                                {{ !empty($item['unit_weight']) ? number_format(floatval($item['unit_weight']), 3) : '-' }}
                                            </td>
                                            <td class="px-6 py-3 text-right font-mono font-bold text-gray-900">
                                                {{ !empty($item['total_weight']) ? number_format(floatval($item['total_weight']), 3) . ' kg' : '-' }}
                                            </td>
                                        @endif
                                        <td class="px-6 py-3 font-sans">
                                            @if($statusType === 'modified')
                                                @php $qD = $itemStatus['deltas']['unit_amount'] ?? 0; @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    ~ Modified in current {{ abs($qD) > 0.001 ? '(Qty '.($qD > 0 ? '+' : '').number_format($qD, 2).')' : '' }}
                                                </span>
                                            @elseif($statusType === 'removed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                    - Removed in current
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                    &#10003; Identical
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-6 text-center text-gray-400">No items recorded in this snapshot.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-6 bg-slate-50/70 border-t border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-center space-x-6 text-xs text-gray-600">
                            <div>
                                <span class="font-bold text-gray-500 uppercase">Net Weight:</span>
                                <span class="font-mono font-bold ms-1">{{ !empty($docData['total_net_weight']) ? number_format($docData['total_net_weight'], 3) . ' kg' : 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="font-bold text-gray-500 uppercase">Gross Weight:</span>
                                <span class="font-mono font-bold ms-1">{{ !empty($docData['total_gross_weight']) ? number_format($docData['total_gross_weight'], 3) . ' kg' : 'N/A' }}</span>
                            </div>
                        </div>

                        @if(!$document->isWeightOnly())
                            <div class="text-right space-y-1">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Final Total</span>
                                <span class="text-2xl font-mono font-black text-indigo-700">
                                    {{ $currency }} {{ number_format($docData['final_total'] ?? 0, 2) }}
                                </span>
                            </div>
                        @else
                            <div class="text-right space-y-1">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Weight-Focused Document</span>
                                <span class="text-sm font-semibold text-gray-600">Non-commercial &bull; No pricing recorded</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Shipment Costs Snapshot -->
                @if(!empty($shipData))
                    <div class="bg-white rounded-xl shadow-xs border border-gray-100 p-6 space-y-4">
                        <h4 class="font-bold text-sm text-gray-800 uppercase tracking-wider border-b border-gray-100 pb-2">
                            Shipment Costs Snapshot
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left">Carrier</th>
                                        <th class="px-4 py-2.5 text-right">Checked Weight (kg)</th>
                                        <th class="px-4 py-2.5 text-right">System Amount ({{ $currency }})</th>
                                        <th class="px-4 py-2.5 text-right">Added Amount ({{ $currency }})</th>
                                        <th class="px-4 py-2.5 text-right">Given Amount ({{ $currency }})</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($shipData as $ship)
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-gray-800 uppercase">{{ str_replace('_', ' ', $ship['method']) }}</td>
                                            <td class="px-4 py-2.5 text-right font-mono">{{ !empty($ship['checked_weight']) ? number_format($ship['checked_weight'], 3) : '-' }}</td>
                                            <td class="px-4 py-2.5 text-right font-mono">{{ !empty($ship['system_amount']) ? number_format($ship['system_amount'], 2) : '-' }}</td>
                                            <td class="px-4 py-2.5 text-right font-mono">{{ !empty($ship['added_amount']) ? number_format($ship['added_amount'], 2) : '-' }}</td>
                                            <td class="px-4 py-2.5 text-right font-mono font-bold text-indigo-700">{{ !empty($ship['given_amount']) ? number_format($ship['given_amount'], 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>

        </div>
    </div>
</x-app-layout>
