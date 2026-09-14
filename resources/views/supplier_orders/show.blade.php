<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('supplier-orders.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 transition" title="Back to Supplier Orders">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-3">
                        <h2 class="font-bold text-2xl text-gray-900 leading-tight font-mono">
                            {{ $document->document_number }}
                        </h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                            Supplier Order Sheet
                        </span>
                        @if($summary['overall_status'] === 'completed')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Fully Received (100%)
                            </span>
                        @elseif($summary['overall_status'] === 'partially_received')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                Partially Received ({{ $summary['completion_percentage'] }}%)
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                Pending Delivery
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 mt-0.5">
                        Supplier: <strong class="text-gray-900">{{ $document->company_name }}</strong> &bull; Order Date: {{ $document->document_date ? \Carbon\Carbon::parse($document->document_date)->format('M d, Y') : '—' }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('documents.show', $document) }}" class="inline-flex items-center px-3 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 rounded-xl text-xs font-bold transition shadow-2xs">
                    <svg class="w-4 h-4 me-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Original Doc
                </a>
                <a href="{{ route('supplier-orders.print-sheet', $document) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 rounded-xl text-xs font-bold transition shadow-2xs">
                    <svg class="w-4 h-4 me-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Sheet
                </a>
                @if(Auth::user()->canEdit())
                    <a href="{{ route('documents.create', ['type' => 'factory_invoice', 'source_document_number' => $document->document_number]) }}" class="inline-flex items-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        + Receive Shipment (Factory Invoice)
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Summary Metrics & Progress -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pb-6 border-b border-gray-100">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Ordered Qty</span>
                        <span class="text-3xl font-black text-gray-900 mt-1 block font-mono">{{ number_format($summary['total_ordered_qty']) }}</span>
                        <span class="text-[11px] text-gray-500 mt-0.5 block">Total items ordered</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-teal-700 uppercase tracking-wider block">Received Qty</span>
                        <span class="text-3xl font-black text-teal-900 mt-1 block font-mono">{{ number_format($summary['total_received_qty']) }}</span>
                        <span class="text-[11px] text-teal-600 mt-0.5 block">From {{ count($summary['factory_invoices']) }} factory shipment(s)</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-amber-800 uppercase tracking-wider block">Remaining Qty</span>
                        <span class="text-3xl font-black mt-1 block font-mono {{ $summary['total_remaining_qty'] > 0 ? 'text-amber-800' : 'text-emerald-700' }}">
                            {{ number_format($summary['total_remaining_qty']) }}
                        </span>
                        <span class="text-[11px] text-amber-700 mt-0.5 block">Awaiting receipt</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-purple-700 uppercase tracking-wider block">Fulfillment</span>
                        <span class="text-3xl font-black text-purple-900 mt-1 block font-mono">{{ $summary['completion_percentage'] }}%</span>
                        <span class="text-[11px] text-purple-600 mt-0.5 block">{{ count($summary['items']) }} distinct item codes</span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-4 pt-2">
                    <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                        <span class="text-gray-600">Order Sheet Delivery Progress</span>
                        <span class="font-mono text-purple-800">{{ number_format($summary['total_received_qty']) }} / {{ number_format($summary['total_ordered_qty']) }} units ({{ $summary['completion_percentage'] }}%)</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                        <div class="h-3 rounded-full transition-all duration-500 {{ $summary['completion_percentage'] >= 100 ? 'bg-emerald-500' : ($summary['completion_percentage'] > 0 ? 'bg-amber-500' : 'bg-gray-300') }}"
                             style="width: {{ min(100, $summary['completion_percentage']) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Linked Factory Invoices Section -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-base text-gray-900 flex items-center">
                            <svg class="w-5 h-5 me-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Linked Factory Invoices & Shipments ({{ count($summary['factory_invoices']) }})
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Shipments received from supplier fulfilling this order sheet.</p>
                    </div>

                    @if(Auth::user()->canEdit())
                        <a href="{{ route('documents.create', ['type' => 'factory_invoice', 'source_document_number' => $document->document_number]) }}" class="inline-flex items-center px-3 py-1.5 bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 rounded-lg text-xs font-bold transition">
                            + Add Receipt Invoice
                        </a>
                    @endif
                </div>

                @if(count($summary['factory_invoices']) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                        @foreach($summary['factory_invoices'] as $inv)
                            <div class="p-4 rounded-xl border border-teal-100 bg-teal-50/30 flex items-start justify-between">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('documents.show', $inv) }}" class="font-mono font-bold text-sm text-teal-800 hover:text-teal-950 hover:underline">
                                            {{ $inv->document_number }}
                                        </a>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-teal-100 text-teal-800">
                                            Receipt
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1">
                                        Date: {{ $inv->document_date ? \Carbon\Carbon::parse($inv->document_date)->format('M d, Y') : '—' }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Units received: <strong class="font-mono text-teal-900">{{ number_format($inv->items->sum('unit_amount')) }}</strong> ({{ $inv->items->count() }} line items)
                                    </p>
                                </div>
                                <a href="{{ route('documents.show', $inv) }}" class="text-teal-600 hover:text-teal-800 p-1 rounded hover:bg-teal-100" title="View Document">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-dashed border-gray-200 text-center text-gray-500 text-xs">
                        No factory receipt invoices recorded yet for this order sheet. When shipments arrive, record a Factory Invoice to update received quantities.
                    </div>
                @endif
            </div>

            <!-- Detailed Line-by-Line Reconciliation Table -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 bg-slate-50/50">
                    <div>
                        <h3 class="font-bold text-base text-gray-900">Line Items Reconciliation Sheet</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Comparison between ordered units and units received from factory invoices.</p>
                    </div>
                    <div class="flex items-center space-x-3 text-xs">
                        <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 me-1.5"></span> Completed</span>
                        <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 me-1.5"></span> Partial</span>
                        <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-gray-400 me-1.5"></span> Pending</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-center w-12 text-gray-400">#</th>
                                <th class="px-4 py-3 text-left w-48">Item Code</th>
                                <th class="px-4 py-3 text-left">Description</th>
                                <th class="px-4 py-3 text-right w-28">Ordered</th>
                                <th class="px-4 py-3 text-right w-28">Received</th>
                                <th class="px-4 py-3 text-right w-28">Remaining</th>
                                <th class="px-4 py-3 text-center w-32">Status</th>
                                <th class="px-5 py-3 text-left">Shipment Breakdown</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($summary['items'] as $index => $item)
                                <tr class="hover:bg-slate-50 transition {{ $item['status'] === 'completed' ? 'bg-emerald-50/10' : ($item['status'] === 'partial' ? 'bg-amber-50/20' : '') }}">
                                    <!-- Index -->
                                    <td class="px-4 py-3.5 text-center text-gray-400 font-mono">
                                        {{ $index + 1 }}
                                    </td>

                                    <!-- Item Code -->
                                    <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-gray-900">
                                        {{ $item['item_code'] }}
                                    </td>

                                    <!-- Description -->
                                    <td class="px-4 py-3.5 text-gray-700">
                                        {{ $item['description'] ?: '—' }}
                                    </td>

                                    <!-- Ordered Qty -->
                                    <td class="px-4 py-3.5 text-right font-mono font-bold text-gray-900">
                                        {{ number_format($item['ordered_qty']) }}
                                    </td>

                                    <!-- Received Qty -->
                                    <td class="px-4 py-3.5 text-right font-mono font-bold text-teal-700">
                                        {{ number_format($item['received_qty']) }}
                                    </td>

                                    <!-- Remaining Qty -->
                                    <td class="px-4 py-3.5 text-right font-mono font-bold whitespace-nowrap">
                                        @if($item['remaining_qty'] > 0)
                                            <span class="text-amber-800 font-black">{{ number_format($item['remaining_qty']) }}</span>
                                        @else
                                            <span class="text-emerald-700 font-bold flex items-center justify-end">
                                                <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                0
                                            </span>
                                        @endif
                                        @if($item['surplus_qty'] > 0)
                                            <span class="block text-[10px] text-blue-600 font-normal">+{{ $item['surplus_qty'] }} extra</span>
                                        @endif
                                    </td>

                                    <!-- Line Status -->
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        @if($item['status'] === 'completed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                Fulfilled
                                            </span>
                                        @elseif($item['status'] === 'partial')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                Partial
                                            </span>
                                        @elseif($item['status'] === 'surplus')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                Surplus
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                Pending
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Receipts Breakdown -->
                                    <td class="px-5 py-3.5">
                                        @if(count($item['receipts']) > 0)
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($item['receipts'] as $rec)
                                                    <a href="{{ route('documents.show', $rec['invoice_uuid']) }}"
                                                       class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 transition"
                                                       title="Received on {{ $rec['date'] }}">
                                                        <span class="font-mono">{{ $rec['invoice_number'] }}</span>:
                                                        <span class="ms-1 font-mono font-black">+{{ number_format($rec['received_qty']) }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic text-[11px]">No shipments yet</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-bold">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right text-gray-700 uppercase tracking-wider text-[11px]">
                                    Grand Totals:
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-sm text-gray-900">
                                    {{ number_format($summary['total_ordered_qty']) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-sm text-teal-800">
                                    {{ number_format($summary['total_received_qty']) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-black {{ $summary['total_remaining_qty'] > 0 ? 'text-amber-800' : 'text-emerald-700' }}">
                                    {{ number_format($summary['total_remaining_qty']) }}
                                </td>
                                <td colspan="2" class="px-4 py-3 text-left font-mono text-xs text-purple-800">
                                    Overall: {{ $summary['completion_percentage'] }}% Completed
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
