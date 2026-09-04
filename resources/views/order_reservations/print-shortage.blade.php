<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shortage Report - {{ $orderReservation->reserve_document_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11pt; color: #000; background: #fff; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body class="bg-gray-100 p-4 md:p-8 text-gray-900 font-sans antialiased">

    <!-- Print Controls -->
    <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center no-print">
        <a href="{{ route('order-reservations.show', $orderReservation) }}" class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 rounded-lg text-xs font-bold shadow-xs transition">
            &larr; Back to Reservation Cockpit
        </a>
        <button onclick="window.print()" class="inline-flex items-center px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print / Save to PDF
        </button>
    </div>

    <!-- Printable Report Sheet -->
    <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 shadow-sm rounded-xl border border-gray-200">
        
        <!-- Document Header -->
        <div class="border-b-2 border-gray-900 pb-6 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-wider text-rose-700">WAREHOUSE SHORTAGE REPORT</h1>
                <p class="text-xs text-gray-500 mt-1 uppercase font-semibold">Missing Parts & Inventory Discrepancy Notice</p>
                <div class="mt-4 space-y-1 text-xs">
                    <p><strong>Reserve Doc Number:</strong> <span class="font-mono font-bold text-sm">{{ $orderReservation->reserve_document_number }}</span></p>
                    <p><strong>Client / Company:</strong> {{ $orderReservation->company_name ?: 'N/A' }}</p>
                    <p><strong>Destination:</strong> {{ $orderReservation->country ?: 'N/A' }}</p>
                </div>
            </div>
            <div class="text-right text-xs space-y-1">
                <div class="inline-block px-3 py-1 bg-rose-50 border border-rose-200 rounded font-bold text-rose-700 uppercase">
                    Status: Shortage Recorded
                </div>
                <p class="pt-2"><strong>Report Date:</strong> {{ now()->format('M d, Y H:i') }}</p>
                <p><strong>Reservation Date:</strong> {{ $orderReservation->reservation_date?->format('M d, Y') ?: '-' }}</p>
                @if($orderReservation->warehouse_location)
                    <p><strong>Location:</strong> {{ $orderReservation->warehouse_location }}</p>
                @endif
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="my-6 grid grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200 text-center text-xs">
            <div>
                <span class="text-gray-500 font-semibold block uppercase">Total Line Items</span>
                <span class="text-base font-bold text-gray-800">{{ $orderReservation->total_items_count }}</span>
            </div>
            <div>
                <span class="text-gray-500 font-semibold block uppercase">Items with Shortage</span>
                <span class="text-base font-black text-rose-700">{{ $orderReservation->short_items_count }}</span>
            </div>
            <div>
                <span class="text-gray-500 font-semibold block uppercase">Total Missing Quantity</span>
                <span class="text-base font-black text-rose-700 font-mono">{{ number_format($orderReservation->total_short_qty, 2) }}</span>
            </div>
        </div>

        <!-- Missing Items Table -->
        <div class="space-y-2">
            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-700">Missing Parts & Short Items List</h2>
            <table class="w-full border-collapse border border-gray-300 text-xs">
                <thead>
                    <tr class="bg-gray-100 text-gray-800 font-bold uppercase text-[10px]">
                        <th class="border border-gray-300 px-3 py-2 text-left w-10">#</th>
                        <th class="border border-gray-300 px-3 py-2 text-left w-36">Item Code</th>
                        <th class="border border-gray-300 px-3 py-2 text-left">Description</th>
                        <th class="border border-gray-300 px-3 py-2 text-right w-20">Req Qty</th>
                        <th class="border border-gray-300 px-3 py-2 text-right w-20">Avail Qty</th>
                        <th class="border border-gray-300 px-3 py-2 text-right w-24">Short Qty</th>
                        <th class="border border-gray-300 px-3 py-2 text-left w-28">Bin / Shelf</th>
                        <th class="border border-gray-300 px-3 py-2 text-left">Shortage Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $shortList = $orderReservation->items->filter(function($it) {
                            return (float)$it->short_qty > 0 || $it->status === 'missing' || $it->status === 'short';
                        });
                    @endphp
                    @forelse($shortList as $idx => $item)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="border border-gray-300 px-3 py-2 text-center font-mono">{{ $idx + 1 }}</td>
                            <td class="border border-gray-300 px-3 py-2 font-mono font-bold">{{ $item->item_code }}</td>
                            <td class="border border-gray-300 px-3 py-2">{{ $item->description ?: '-' }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-mono">{{ number_format($item->requested_qty, 2) }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-mono font-bold text-emerald-700">{{ number_format($item->available_qty, 2) }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-mono font-black text-rose-700 bg-rose-50/50">
                                -{{ number_format($item->short_qty, 2) }}
                            </td>
                            <td class="border border-gray-300 px-3 py-2">{{ $item->bin_location ?: '-' }}</td>
                            <td class="border border-gray-300 px-3 py-2 italic text-gray-700">{{ $item->shortage_reason ?: 'Out of stock' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border border-gray-300 px-4 py-8 text-center text-gray-500 italic">
                                No shortages recorded for this reservation. All items are available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orderReservation->warehouse_notes)
            <div class="mt-6 p-4 border border-gray-200 rounded-lg bg-gray-50 text-xs">
                <span class="font-bold uppercase text-gray-700 block mb-1">Warehouse Notes:</span>
                <p class="text-gray-800 whitespace-pre-line">{{ $orderReservation->warehouse_notes }}</p>
            </div>
        @endif

        <!-- Signature Sign-off Block -->
        <div class="mt-12 pt-8 border-t border-gray-300 grid grid-cols-2 gap-8 text-xs text-gray-700">
            <div>
                <p class="font-bold">Verified By (Warehouse Inspector):</p>
                <div class="h-12 border-b border-gray-400 mt-2"></div>
                @php
                    $inspectorName = $orderReservation->confirmedBy?->name;
                    if (! $inspectorName || strtoupper(trim($inspectorName)) === 'MACM' || str_contains(strtoupper($inspectorName), 'MACM')) {
                        $inspectorName = '____________________';
                    }
                @endphp
                <p class="mt-1 text-[11px] text-gray-500">Name: {{ $inspectorName }}</p>
                <p class="text-[11px] text-gray-500">Date: ____________________</p>
            </div>
            <div>
                <p class="font-bold">Procurement / Operations Sign-off:</p>
                <div class="h-12 border-b border-gray-400 mt-2"></div>
                <p class="mt-1 text-[11px] text-gray-500">Name: ____________________</p>
                <p class="text-[11px] text-gray-500">Date: ____________________</p>
            </div>
        </div>

    </div>

</body>
</html>
