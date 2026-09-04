<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shortage Report - {{ $orderReservation->reserve_document_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9pt; color: #1e293b; margin: 20px; }
        .header { border-bottom: 2px solid #991b1b; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 16pt; font-weight: bold; color: #991b1b; text-transform: uppercase; }
        .subtitle { font-size: 8.5pt; color: #64748b; margin-top: 2px; }
        .meta-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 8.5pt; }
        .clearfix::after { content: ""; clear: both; display: table; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 8pt; }
        table.data th { background: #991b1b; color: #ffffff; text-align: left; padding: 6px 8px; font-size: 7.5pt; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; }
        table.data tr:nth-child(even) td { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .badge-short { background: #fee2e2; color: #991b1b; font-weight: bold; padding: 2px 6px; border-radius: 3px; }
        .signature-table { width: 100%; margin-top: 35px; border-collapse: collapse; font-size: 8pt; }
        .signature-table td { width: 50%; padding: 10px; vertical-align: top; }
        .sign-line { border-bottom: 1px solid #94a3b8; height: 35px; margin-bottom: 5px; }
    </style>
</head>
<body>

    <div class="header clearfix">
        <div style="float: left;">
            <div class="title">ORDER SHORTAGE REPORT</div>
            <div class="subtitle">Official Warehouse Stock Discrepancy & Missing Parts Notice</div>
        </div>
        <div style="float: right; text-align: right; font-size: 8pt; color: #475569;">
            <div><strong>Report Date:</strong> {{ $generatedAt }}</div>
            <div><strong>Status:</strong> Shortage Recorded</div>
        </div>
    </div>

    <div class="meta-box clearfix">
        <div style="float: left; width: 50%;">
            <div><strong>Reserve Document #:</strong> <span class="font-mono font-bold" style="font-size:10pt;">{{ $orderReservation->reserve_document_number }}</span></div>
            <div><strong>Client / Company:</strong> {{ $orderReservation->company_name ?: 'N/A' }}</div>
            <div><strong>Destination Country:</strong> {{ $orderReservation->country ?: 'N/A' }}</div>
        </div>
        <div style="float: right; width: 48%;">
            <div><strong>Reservation Date:</strong> {{ $orderReservation->reservation_date?->format('M d, Y') ?: '-' }}</div>
            <div><strong>Warehouse Location:</strong> {{ $orderReservation->warehouse_location ?: 'Main Warehouse' }}</div>
            @php
                $verifiedName = $orderReservation->confirmedBy?->name;
                if (! $verifiedName || strtoupper(trim($verifiedName)) === 'MACM' || str_contains(strtoupper($verifiedName), 'MACM')) {
                    $verifiedName = 'Warehouse Staff';
                }
            @endphp
            <div><strong>Verified By:</strong> {{ $verifiedName }}</div>
        </div>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 20%;">Item Code</th>
                <th style="width: 30%;">Description</th>
                <th class="text-right" style="width: 10%;">Req Qty</th>
                <th class="text-right" style="width: 10%;">Avail Qty</th>
                <th class="text-right" style="width: 10%;">Short Qty</th>
                <th style="width: 15%;">Bin & Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shortItems as $idx => $it)
                <tr>
                    <td class="font-mono">{{ $idx + 1 }}</td>
                    <td class="font-mono font-bold">{{ $it->item_code }}</td>
                    <td>{{ $it->description ?: '-' }}</td>
                    <td class="text-right font-mono">{{ number_format($it->requested_qty, 2) }}</td>
                    <td class="text-right font-mono" style="color:#059669;">{{ number_format($it->available_qty, 2) }}</td>
                    <td class="text-right font-mono font-bold">
                        <span class="badge-short">-{{ number_format($it->short_qty, 2) }}</span>
                    </td>
                    <td>
                        @if($it->bin_location)<div><strong>Bin:</strong> {{ $it->bin_location }}</div>@endif
                        <div style="color:#64748b; font-style:italic;">{{ $it->shortage_reason ?: 'Out of stock' }}</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color:#059669; font-weight: bold;">
                        No shortages for this reservation. All items are verified available.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($orderReservation->warehouse_notes)
        <div style="margin-top: 15px; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 8pt;">
            <strong>Warehouse Notes:</strong> {{ $orderReservation->warehouse_notes }}
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td>
                <div><strong>Warehouse Inspector Sign-off:</strong></div>
                <div class="sign-line"></div>
                <div>Name: {{ $verifiedName !== 'Warehouse Staff' ? $verifiedName : '____________________' }}</div>
                <div>Date: ____________________</div>
            </td>
            <td>
                <div><strong>Procurement / Operations Acknowledgment:</strong></div>
                <div class="sign-line"></div>
                <div>Name: ____________________</div>
                <div>Date: ____________________</div>
            </td>
        </tr>
    </table>

</body>
</html>
