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
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg text-amber-900 text-xs">
                <span class="font-bold">Historical Snapshot Notice:</span> You are viewing the archived Version {{ $version->version_number }} snapshot. Current document is at Version {{ $document->current_version }}.
            </div>

            <!-- Header Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
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

            <!-- Items Snapshot -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
                                <th class="px-6 py-3 text-right">Unit Price</th>
                                <th class="px-6 py-3 text-right">Total ({{ $currency }})</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($itemsData as $idx => $item)
                                @php
                                    $totAmt = floatval($item['total_amount'] ?? 0);
                                    $unitPrc = floatval($item['unit_price'] ?? 0);
                                    $code = $item['item_code'] ?? '-';
                                    $isDiscount = $totAmt < 0 || strtoupper($code) === 'DISCOUNT';
                                    $isAddition = strtoupper($code) === 'ADDITION';
                                @endphp
                                <tr class="{{ $isDiscount ? 'bg-rose-50/40' : ($isAddition ? 'bg-emerald-50/30' : '') }}">
                                    <td class="px-6 py-3 text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="px-6 py-3 font-mono font-bold text-gray-900">
                                        <div class="flex items-center space-x-1.5">
                                            @if($isDiscount)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">Discount (-)</span>
                                            @elseif($isAddition)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Addition (+)</span>
                                            @endif
                                            <span>{{ $code }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">{{ $item['description'] ?? '-' }}</td>
                                    <td class="px-6 py-3 text-right font-mono">{{ number_format($item['unit_amount'] ?? 1, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-mono {{ $unitPrc < 0 ? 'text-rose-600 font-bold' : '' }}">{{ number_format($unitPrc, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-mono font-bold {{ $totAmt < 0 ? 'text-rose-600' : 'text-gray-900' }}">
                                        {{ $totAmt < 0 ? '-' . number_format(abs($totAmt), 2) : number_format($totAmt, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-6 text-center text-gray-400">No items recorded in this snapshot.</td>
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

                    <div class="text-right">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Final Total</span>
                        <span class="text-2xl font-mono font-black text-indigo-700">
                            {{ $currency }} {{ number_format($docData['final_total'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Shipment Costs Snapshot -->
            @if(!empty($shipData))
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
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
</x-app-layout>
