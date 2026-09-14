<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->document_number }} - Purchase Order & Factory Shipment Reconciliation Sheet</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10pt;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-page-card {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            tr {
                page-break-inside: avoid;
            }
            table {
                page-break-inside: auto;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 15mm 15mm 15mm;
            }
        }
    </style>
</head>
<body class="bg-gray-100 p-4 md:p-8 text-gray-900 font-sans antialiased">

    <!-- Top Action Bar (Screen Only) -->
    <div class="max-w-5xl mx-auto mb-6 flex flex-col sm:flex-row justify-between items-center gap-3 no-print">
        <div class="flex items-center space-x-3">
            <a href="{{ route('supplier-orders.show', $document) }}" class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 rounded-lg text-xs font-bold shadow-xs transition">
                &larr; Back to Order Sheet
            </a>
            <span class="text-xs text-gray-500 font-mono">
                Order Sheet: <strong class="text-gray-900">{{ $document->document_number }}</strong>
            </span>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="inline-flex items-center px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold shadow-sm transition cursor-pointer">
                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Reconciliation Sheet
            </button>
        </div>
    </div>

    <!-- Printable Paper Card -->
    <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-md border border-gray-200 p-8 md:p-12 print-page-card space-y-6">

        <!-- Header -->
        <div class="border-b-2 border-purple-600 pb-5 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-gray-900">PURCHASE ORDER &amp; FACTORY SHIPMENT RECONCILIATION SHEET</h1>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mt-0.5">Physical Inward Verification &amp; Remaining Item Ledger</p>
                <div class="mt-3 text-xs text-gray-700 space-y-0.5">
                    <p><span class="font-bold text-gray-500">Supplier:</span> <strong class="text-gray-900 text-sm">{{ $document->company_name }}</strong></p>
                    @if($document->country)<p><span class="font-bold text-gray-500">Country:</span> {{ $document->country }}</p>@endif
                    @if($document->address)<p><span class="font-bold text-gray-500">Address:</span> {{ $document->address }}</p>@endif
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block bg-purple-50 border border-purple-200 rounded-xl px-4 py-3 text-right">
                    <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider block">Order Sheet #</span>
                    <span class="text-2xl font-mono font-black text-purple-900 block leading-tight">{{ $document->document_number }}</span>
                    <span class="text-xs text-gray-600 block mt-1">Date: <strong>{{ $document->document_date ? \Carbon\Carbon::parse($document->document_date)->format('d M Y') : '—' }}</strong></span>
                </div>
            </div>
        </div>

        <!-- KPI Summary Bar -->
        <div class="grid grid-cols-4 gap-3 bg-gray-50 border border-gray-200 rounded-xl p-4 text-center">
            <div>
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Total Ordered</span>
                <span class="text-xl font-black font-mono text-gray-900 mt-0.5 block">{{ number_format($summary['total_ordered_qty']) }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-teal-700 uppercase tracking-wider block">Total Received</span>
                <span class="text-xl font-black font-mono text-teal-800 mt-0.5 block">{{ number_format($summary['total_received_qty']) }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block">Pending Remaining</span>
                <span class="text-xl font-black font-mono {{ $summary['total_remaining_qty'] > 0 ? 'text-amber-800' : 'text-emerald-700' }} mt-0.5 block">{{ number_format($summary['total_remaining_qty']) }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider block">Status</span>
                <span class="text-sm font-bold uppercase mt-1 block {{ $summary['overall_status'] === 'completed' ? 'text-emerald-700' : ($summary['overall_status'] === 'partially_received' ? 'text-amber-800' : 'text-gray-700') }}">
                    {{ str_replace('_', ' ', $summary['overall_status']) }} ({{ $summary['completion_percentage'] }}%)
                </span>
            </div>
        </div>

        <!-- Linked Shipments Note -->
        @if(count($summary['factory_invoices']) > 0)
            <div class="text-xs text-gray-600 bg-slate-50 border border-slate-200 rounded-lg p-2.5">
                <strong class="text-gray-800">Linked Inward Factory Shipments:</strong>
                {{ $summary['factory_invoices']->map(fn($f) => $f->document_number . ' (' . ($f->document_date ? \Carbon\Carbon::parse($f->document_date)->format('d M') : 'N/A') . ')')->join(', ') }}
            </div>
        @endif

        <!-- Items Table -->
        <table class="w-full border-collapse text-xs">
            <thead>
                <tr class="bg-gray-100 border-y-2 border-gray-300 text-gray-700 font-bold uppercase tracking-wider">
                    <th class="py-2.5 px-3 text-center w-10">#</th>
                    <th class="py-2.5 px-3 text-left w-44">Item Code</th>
                    <th class="py-2.5 px-3 text-left">Description</th>
                    <th class="py-2.5 px-3 text-right w-24">Ordered</th>
                    <th class="py-2.5 px-3 text-right w-24">Received</th>
                    <th class="py-2.5 px-3 text-right w-24">Remaining</th>
                    <th class="py-2.5 px-3 text-center w-28">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($summary['items'] as $idx => $it)
                    <tr class="{{ $it['remaining_qty'] > 0 ? 'bg-amber-50/20' : '' }}">
                        <td class="py-2 px-3 text-center font-mono text-gray-500">{{ $idx + 1 }}</td>
                        <td class="py-2 px-3 font-mono font-bold text-gray-900 whitespace-nowrap">{{ $it['item_code'] }}</td>
                        <td class="py-2 px-3 text-gray-700">{{ $it['description'] ?: '—' }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-gray-900">{{ number_format($it['ordered_qty']) }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-teal-800">{{ number_format($it['received_qty']) }}</td>
                        <td class="py-2 px-3 text-right font-mono font-black {{ $it['remaining_qty'] > 0 ? 'text-amber-800' : 'text-gray-400' }}">
                            {{ number_format($it['remaining_qty']) }}
                        </td>
                        <td class="py-2 px-3 text-center font-bold text-[11px] uppercase">
                            @if($it['status'] === 'completed')
                                <span class="text-emerald-700 font-black">Fulfilled</span>
                            @elseif($it['status'] === 'partial')
                                <span class="text-amber-800 font-black">Partial</span>
                            @else
                                <span class="text-gray-500">Pending</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-400 font-bold bg-gray-50">
                    <td colspan="3" class="py-2.5 px-3 text-right uppercase tracking-wider text-gray-700">Total Units:</td>
                    <td class="py-2.5 px-3 text-right font-mono text-gray-900">{{ number_format($summary['total_ordered_qty']) }}</td>
                    <td class="py-2.5 px-3 text-right font-mono text-teal-800">{{ number_format($summary['total_received_qty']) }}</td>
                    <td class="py-2.5 px-3 text-right font-mono font-black {{ $summary['total_remaining_qty'] > 0 ? 'text-amber-800' : 'text-gray-700' }}">{{ number_format($summary['total_remaining_qty']) }}</td>
                    <td class="py-2.5 px-3 text-center text-purple-900">{{ $summary['completion_percentage'] }}% Done</td>
                </tr>
            </tfoot>
        </table>

        <!-- Verification Sign-off -->
        <div class="pt-8 grid grid-cols-2 gap-8 text-xs">
            <div class="border-t border-gray-300 pt-2">
                <p class="font-bold text-gray-700">Warehouse Receiver / Auditor:</p>
                <div class="h-12"></div>
                <p class="text-gray-400">Signature / Date</p>
            </div>
            <div class="border-t border-gray-300 pt-2 text-right">
                <p class="font-bold text-gray-700">Operations Manager Sign-off:</p>
                <div class="h-12"></div>
                <p class="text-gray-400">Signature / Date</p>
            </div>
        </div>

    </div>

</body>
</html>
