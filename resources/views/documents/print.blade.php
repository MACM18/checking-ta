<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->document_number }} - {{ $document->formatted_type }}</title>
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
            .page-break {
                page-break-after: always;
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
            <a href="{{ route('documents.show', $document) }}" class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 rounded-lg text-xs font-bold shadow-xs transition">
                &larr; Back to Document View
            </a>
            <span class="text-xs text-gray-500 font-mono">
                Document: <strong class="text-gray-900">{{ $document->document_number }}</strong> (v{{ $document->current_version }})
            </span>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-sm transition cursor-pointer">
                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print / Save to PDF
            </button>
        </div>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="max-w-5xl mx-auto bg-white p-8 md:p-12 shadow-sm rounded-xl border border-gray-200 print-page-card space-y-6">

        <!-- Document Header -->
        <div class="border-b-2 border-gray-900 pb-6 flex flex-col sm:flex-row justify-between items-start gap-4">
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <span class="w-3 h-3 rounded-full bg-indigo-600 inline-block"></span>
                    <span class="text-sm font-black tracking-wider text-gray-900 uppercase">Checking TA &bull; Trade Documentation</span>
                </div>
                <h1 class="text-2xl font-black uppercase tracking-wider text-gray-900">
                    @if($document->isPackingList())
                        PACKING LIST
                    @elseif($document->isReserve())
                        WAREHOUSE RESERVATION / ORDER
                    @elseif($document->isCommercialInvoice())
                        COMMERCIAL INVOICE
                    @elseif($document->isProformaInvoice())
                        PROFORMA INVOICE
                    @else
                        {{ strtoupper($document->formatted_type) }}
                    @endif
                </h1>
                <p class="text-xs text-gray-500 font-medium mt-0.5">
                    Official Document &bull; Version {{ $document->current_version }}
                </p>
            </div>

            <div class="text-right text-xs space-y-1">
                <div class="inline-block px-3 py-1 bg-gray-100 border border-gray-300 rounded font-mono font-bold text-sm text-gray-900 uppercase">
                    DOC #: {{ $document->document_number }}
                </div>
                <p class="pt-1"><strong>Document Date:</strong> {{ $document->document_date ? $document->document_date->format('d M Y') : now()->format('d M Y') }}</p>
                @if(!$document->isWeightOnly())
                    <p><strong>Currency:</strong> <span class="font-mono font-bold">{{ $document->currency }}</span></p>
                @endif
                <p><strong>Status:</strong> <span class="capitalize">{{ $document->status }}</span></p>
                @if($document->sourceDocument || $document->source_document_number)
                    <p><strong>Source Ref:</strong> <span class="font-mono">{{ $document->sourceDocument?->document_number ?? $document->source_document_number }}</span></p>
                @endif
            </div>
        </div>

        <!-- Bill To / Company Info & Specs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
            <div class="bg-gray-50/70 p-4 rounded-lg border border-gray-200 space-y-1">
                <h3 class="font-bold uppercase tracking-wider text-gray-500 text-[10px] mb-1.5">
                    @if($document->isWeightOnly())
                        Consignee / Destination
                    @else
                        Bill To / Customer Details
                    @endif
                </h3>
                <div class="text-sm font-bold text-gray-900">{{ $document->company_name }}</div>
                @if($document->country)
                    <div class="text-xs font-semibold text-gray-700">Country: {{ $document->country }}</div>
                @endif
                @if($document->address)
                    <div class="text-xs text-gray-600 whitespace-pre-line pt-1">{{ $document->address }}</div>
                @endif
                @if($document->contact_details)
                    <div class="text-[11px] text-gray-600 pt-1 border-t border-gray-200 mt-2">
                        <strong>Contact / Attn:</strong> {{ $document->contact_details }}
                    </div>
                @endif
            </div>

            <div class="bg-gray-50/70 p-4 rounded-lg border border-gray-200 space-y-2 flex flex-col justify-between">
                <div>
                    <h3 class="font-bold uppercase tracking-wider text-gray-500 text-[10px] mb-1.5">Shipment & Packaging Summary</h3>
                    <div class="space-y-1.5 text-xs">
                        @if(!$document->isReserve())
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Gross Weight:</span>
                                <strong class="font-mono text-gray-900">{{ $document->total_gross_weight ? number_format($document->total_gross_weight, 3) . ' kg' : '-' }}</strong>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Net Weight:</span>
                            <strong class="font-mono text-gray-900">{{ $document->total_net_weight ? number_format($document->total_net_weight, 3) . ' kg' : '-' }}</strong>
                        </div>
                        @if($document->packages->isNotEmpty())
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Packages:</span>
                                <strong class="font-mono text-gray-900">{{ $document->packages->sum('quantity') }} package(s)</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Volume:</span>
                                <strong class="font-mono text-gray-900">{{ number_format($document->packages->sum('cbm'), 3) }} m³</strong>
                            </div>
                        @endif
                    </div>
                </div>

                @php
                    $creatorName = $document->creator?->name;
                    if (! $creatorName || strtoupper(trim($creatorName)) === 'MACM' || str_contains(strtoupper($creatorName), 'MACM')) {
                        $creatorName = 'Authorized Officer';
                    }
                @endphp
                <div class="pt-2 border-t border-gray-200 text-[11px] text-gray-500 flex justify-between items-center">
                    <span>Generated By: {{ $creatorName }}</span>
                    <span>Printed: {{ now()->format('d M Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">
                    @if($document->isWeightOnly())
                        Items & Weight Breakdown ({{ $document->items->count() }} items)
                    @else
                        Line Items ({{ $document->items->count() }} items)
                    @endif
                </h3>
                @if($document->isWeightOnly())
                    <span class="text-[11px] text-gray-500 font-semibold italic">Weight Only &bull; Non-Commercial</span>
                @else
                    <span class="text-[11px] text-gray-500 font-mono">Currency: <strong>{{ $document->currency }}</strong></span>
                @endif
            </div>

            <table class="w-full border-collapse border border-gray-300 text-xs">
                <thead>
                    <tr class="bg-gray-100 text-gray-800 font-bold uppercase text-[10px]">
                        <th class="border border-gray-300 px-3 py-2 text-center w-10">#</th>
                        <th class="border border-gray-300 px-3 py-2 text-left w-36">Item Code</th>
                        <th class="border border-gray-300 px-3 py-2 text-left">Description</th>
                        <th class="border border-gray-300 px-3 py-2 text-right w-20">Qty</th>
                        @if($document->isWeightOnly())
                            <th class="border border-gray-300 px-3 py-2 text-right w-28">Unit Net Wt (kg)</th>
                            <th class="border border-gray-300 px-3 py-2 text-right w-32">Total Net Wt (kg)</th>
                        @else
                            <th class="border border-gray-300 px-3 py-2 text-right w-28">Unit Price</th>
                            <th class="border border-gray-300 px-3 py-2 text-right w-32">Total ({{ $document->currency }})</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($document->items as $idx => $item)
                        @php
                            $codeUpper = strtoupper($item->item_code);
                            $isDiscount = $item->total_amount < 0 || $codeUpper === 'DISCOUNT';
                            $isTax = in_array($codeUpper, ['TAX', 'VAT']) && $item->total_amount >= 0;
                            $isAddition = $codeUpper === 'ADDITION' && $item->total_amount >= 0;
                        @endphp
                        <tr class="border-b border-gray-200 {{ $isDiscount ? 'bg-rose-50/30' : ($isTax ? 'bg-amber-50/30' : ($isAddition ? 'bg-emerald-50/30' : '')) }}">
                            <td class="border border-gray-300 px-3 py-2 text-center font-mono text-gray-500">{{ $idx + 1 }}</td>
                            <td class="border border-gray-300 px-3 py-2 font-mono font-bold text-gray-900">
                                @if($isDiscount)
                                    <span class="text-rose-700 text-[10px] font-bold block">DISCOUNT (-)</span>
                                @elseif($isTax)
                                    <span class="text-amber-800 text-[10px] font-bold block">TAX/VAT (+)</span>
                                @elseif($isAddition)
                                    <span class="text-emerald-700 text-[10px] font-bold block">ADDITION (+)</span>
                                @endif
                                {{ $item->item_code }}
                            </td>
                            <td class="border border-gray-300 px-3 py-2 text-gray-700">{{ $item->description ?: '-' }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-mono font-semibold">{{ number_format($item->unit_amount, 2) }}</td>
                            @if($document->isWeightOnly())
                                <td class="border border-gray-300 px-3 py-2 text-right font-mono">{{ number_format($item->unit_weight, 3) }}</td>
                                <td class="border border-gray-300 px-3 py-2 text-right font-mono font-bold text-gray-900">
                                    {{ number_format($item->total_weight ?: ($item->unit_amount * $item->unit_weight), 3) }} kg
                                </td>
                            @else
                                <td class="border border-gray-300 px-3 py-2 text-right font-mono {{ $item->unit_price < 0 ? 'text-rose-700 font-bold' : '' }}">
                                    {{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="border border-gray-300 px-3 py-2 text-right font-mono font-bold {{ $item->total_amount < 0 ? 'text-rose-700' : 'text-gray-900' }}">
                                    {{ $item->total_amount < 0 ? '-' . number_format(abs($item->total_amount), 2) : number_format($item->total_amount, 2) }}
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $document->isWeightOnly() ? 6 : 6 }}" class="border border-gray-300 px-4 py-6 text-center text-gray-400">
                                No items listed on this document.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="3" class="border border-gray-300 px-3 py-2 text-right uppercase text-[10px] text-gray-600">Sum:</td>
                        <td class="border border-gray-300 px-3 py-2 text-right font-mono text-gray-900">{{ number_format($document->items->sum('unit_amount'), 2) }}</td>
                        @if($document->isWeightOnly())
                            <td class="border border-gray-300 px-3 py-2 text-right font-mono text-gray-500">-</td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-mono text-gray-900">
                                {{ number_format($document->items->sum('total_weight'), 3) }} kg
                            </td>
                        @else
                            <td class="border border-gray-300 px-3 py-2 text-right font-mono text-gray-500">-</td>
                            <td class="border border-gray-300 px-3 py-2 text-right font-mono text-gray-900">
                                {{ $document->currency }} {{ number_format($document->subtotal, 2) }}
                            </td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Package Dimensions Breakdown (If packages exist) -->
        @if($document->packages->isNotEmpty())
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">
                        Package Dimensions & Packaging Details ({{ $document->packages->sum('quantity') }} pkgs)
                    </h3>
                    <span class="text-[11px] text-gray-500 font-mono">
                        Vol. Wt: <strong>{{ number_format($document->packages->sum('volumetric_weight_kg'), 2) }} kg</strong> &bull; Volume: <strong>{{ number_format($document->packages->sum('cbm'), 3) }} m³</strong>
                    </span>
                </div>
                <table class="w-full border-collapse border border-gray-300 text-xs">
                    <thead>
                        <tr class="bg-gray-100 text-gray-800 font-bold uppercase text-[10px]">
                            <th class="border border-gray-300 px-3 py-2 text-left">Package Type</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Format</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Dimensions</th>
                            <th class="border border-gray-300 px-3 py-2 text-right w-16">Qty</th>
                            <th class="border border-gray-300 px-3 py-2 text-right w-24">Gross Wt / Pkg</th>
                            <th class="border border-gray-300 px-3 py-2 text-right w-24">Volumetric Wt</th>
                            <th class="border border-gray-300 px-3 py-2 text-right w-20">CBM (m³)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($document->packages as $pkg)
                            <tr class="border-b border-gray-200">
                                <td class="border border-gray-300 px-3 py-1.5 font-semibold text-gray-900">{{ $pkg->package_type }}</td>
                                <td class="border border-gray-300 px-3 py-1.5 text-gray-600">{{ $pkg->dimension_type === 'diameter' ? 'Cylinder (Ø×H)' : 'Box (L×W×H)' }}</td>
                                <td class="border border-gray-300 px-3 py-1.5 font-mono">{{ $pkg->formatted_dimensions }}</td>
                                <td class="border border-gray-300 px-3 py-1.5 text-right font-mono font-bold">{{ $pkg->quantity }}</td>
                                <td class="border border-gray-300 px-3 py-1.5 text-right font-mono">{{ $pkg->gross_weight_per_pkg_kg ? number_format($pkg->gross_weight_per_pkg_kg, 3) . ' kg' : '-' }}</td>
                                <td class="border border-gray-300 px-3 py-1.5 text-right font-mono">{{ $pkg->volumetric_weight_kg ? number_format($pkg->volumetric_weight_kg, 2) . ' kg' : '-' }}</td>
                                <td class="border border-gray-300 px-3 py-1.5 text-right font-mono">{{ $pkg->cbm ? number_format($pkg->cbm, 3) : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Shipment Charges (ONLY for financial documents, NEVER for Packing List or Reserve) -->
        @if(!$document->isWeightOnly() && $document->shipmentCosts->isNotEmpty())
            <div class="space-y-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">Shipping & Freight Charges</h3>
                <table class="w-full border-collapse border border-gray-300 text-xs">
                    <thead>
                        <tr class="bg-gray-100 text-gray-800 font-bold uppercase text-[10px]">
                            <th class="border border-gray-300 px-3 py-2 text-left">Carrier / Method</th>
                            <th class="border border-gray-300 px-3 py-2 text-right">Checked Wt (kg)</th>
                            <th class="border border-gray-300 px-3 py-2 text-right">Rate / kg ({{ $document->currency }})</th>
                            <th class="border border-gray-300 px-3 py-2 text-right">Freight Amount ({{ $document->currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($document->shipmentCosts as $ship)
                            @php
                                $amt = $ship->given_amount ?? $ship->system_amount;
                            @endphp
                            <tr class="border-b border-gray-200">
                                <td class="border border-gray-300 px-3 py-1.5 font-bold text-gray-900">{{ $ship->method_label }}</td>
                                <td class="border border-gray-300 px-3 py-1.5 text-right font-mono">{{ $ship->checked_weight !== null ? number_format($ship->checked_weight, 3) : '-' }}</td>
                                <td class="border border-gray-300 px-3 py-1.5 text-right font-mono">{{ $ship->rate_per_kg !== null ? number_format($ship->rate_per_kg, 2) : '-' }}</td>
                                <td class="border border-gray-300 px-3 py-1.5 text-right font-mono font-bold text-gray-900">
                                    {{ $amt !== null ? number_format($amt, 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Document Totals / Weight Summaries Card -->
        <div class="bg-gray-50 p-5 rounded-lg border border-gray-300 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="text-xs space-y-1 text-gray-600">
                @if($document->isPackingList())
                    <div>Total Gross Weight: <strong class="font-mono text-gray-900">{{ $document->total_gross_weight ? number_format($document->total_gross_weight, 3) . ' kg' : '-' }}</strong></div>
                    <div>Total Net Weight: <strong class="font-mono text-gray-900">{{ $document->total_net_weight ? number_format($document->total_net_weight, 3) . ' kg' : '-' }}</strong></div>
                    @if($document->packages->isNotEmpty())
                        <div>Total Packages: <strong class="font-mono text-gray-900">{{ $document->packages->sum('quantity') }} pkgs</strong></div>
                    @endif
                @elseif($document->isReserve())
                    <div>Total Net Weight: <strong class="font-mono text-gray-900">{{ $document->total_net_weight ? number_format($document->total_net_weight, 3) . ' kg' : '-' }}</strong></div>
                    <div>Total Line Items: <strong class="font-mono text-gray-900">{{ $document->items->count() }} item(s)</strong></div>
                @else
                    @php
                        $discountsSum = $document->items->where('total_amount', '<', 0)->sum('total_amount');
                        $taxesSum = $document->items->filter(fn($it) => in_array(strtoupper($it->item_code), ['TAX', 'VAT']) && $it->total_amount > 0)->sum('total_amount');
                        $additionsSum = $document->items->filter(fn($it) => strtoupper($it->item_code) === 'ADDITION' && $it->total_amount > 0)->sum('total_amount');
                        $baseItemsSum = $document->items->filter(fn($it) => $it->total_amount > 0 && !in_array(strtoupper($it->item_code), ['TAX', 'VAT', 'ADDITION']))->sum('total_amount');
                        $freightAmount = max(0, round($document->final_total - $document->subtotal, 2));
                    @endphp
                    <div>Items Subtotal: <span class="font-mono font-bold">{{ $document->currency }} {{ number_format($baseItemsSum, 2) }}</span></div>
                    @if($discountsSum < 0)
                        <div class="text-rose-700">Discounts: <span class="font-mono font-bold">-{{ $document->currency }} {{ number_format(abs($discountsSum), 2) }}</span></div>
                    @endif
                    @if($taxesSum > 0)
                        <div class="text-amber-800">Tax / VAT: <span class="font-mono font-bold">+{{ $document->currency }} {{ number_format($taxesSum, 2) }}</span></div>
                    @endif
                    @if($additionsSum > 0)
                        <div class="text-emerald-700">Additions: <span class="font-mono font-bold">+{{ $document->currency }} {{ number_format($additionsSum, 2) }}</span></div>
                    @endif
                    @if($freightAmount > 0)
                        <div class="text-indigo-800 font-semibold">Shipping / Freight: <span class="font-mono font-bold">+{{ $document->currency }} {{ number_format($freightAmount, 2) }}</span></div>
                    @endif
                @endif
            </div>

            <div class="text-right">
                @if($document->isPackingList())
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 block">Summary Gross & Net Weight</span>
                    <div class="text-xl font-mono font-black text-gray-900">
                        GW: {{ $document->total_gross_weight ? number_format($document->total_gross_weight, 3) . ' kg' : '-' }}
                    </div>
                    <div class="text-sm font-mono font-bold text-gray-700">
                        NW: {{ $document->total_net_weight ? number_format($document->total_net_weight, 3) . ' kg' : '-' }}
                    </div>
                @elseif($document->isReserve())
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 block">Warehouse Net Weight</span>
                    <div class="text-xl font-mono font-black text-gray-900">
                        NW: {{ $document->total_net_weight ? number_format($document->total_net_weight, 3) . ' kg' : '-' }}
                    </div>
                @else
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 block">Final Total Amount</span>
                    <div class="text-2xl font-mono font-black text-gray-900">
                        {{ $document->currency }} {{ number_format($document->final_total, 2) }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Notes / Terms & Conditions -->
        @if($document->notes)
            <div class="space-y-1 text-xs">
                <h4 class="font-bold uppercase tracking-wider text-gray-500 text-[10px]">Notes / Terms / Instructions</h4>
                <div class="p-3 bg-gray-50 rounded border border-gray-200 text-gray-700 whitespace-pre-line leading-relaxed">
                    {{ $document->notes }}
                </div>
            </div>
        @endif

        <!-- Signatures & Authorization -->
        <div class="pt-8 border-t-2 border-gray-300 grid grid-cols-2 gap-12 text-xs">
            <div>
                <p class="font-bold text-gray-800 uppercase text-[10px] mb-8">Prepared By:</p>
                <div class="border-b border-gray-400 pb-1">
                    <span class="font-semibold text-gray-900">{{ $creatorName }}</span>
                </div>
                <span class="text-[10px] text-gray-500">Authorized Signature / Date</span>
            </div>

            <div class="text-right">
                <p class="font-bold text-gray-800 uppercase text-[10px] mb-8">
                    @if($document->isWeightOnly())
                        Warehouse / Receiver Acknowledgment:
                    @else
                        Approved / Client Acknowledgment:
                    @endif
                </p>
                <div class="border-b border-gray-400 pb-1">
                    <span class="text-transparent">&nbsp;</span>
                </div>
                <span class="text-[10px] text-gray-500">Authorized Signature & Stamp</span>
            </div>
        </div>

    </div>

</body>
</html>
