<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Master Short Parts & Shortages Report</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #1e293b; margin: 15px; }
        .header { border-bottom: 2px solid #991b1b; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 14pt; font-weight: bold; color: #991b1b; text-transform: uppercase; }
        .subtitle { font-size: 8pt; color: #64748b; margin-top: 2px; }
        .meta { float: right; text-align: right; font-size: 8pt; color: #475569; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .kpi-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .kpi-box { background: #fef2f2; border: 1px solid #fecaca; padding: 6px 10px; text-align: center; }
        .kpi-label { font-size: 7pt; color: #991b1b; text-transform: uppercase; font-weight: bold; }
        .kpi-val { font-size: 11pt; font-weight: bold; color: #7f1d1d; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 7.5pt; }
        table.data th { background: #991b1b; color: #ffffff; text-align: left; padding: 5px 6px; font-size: 7pt; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 5px 6px; }
        table.data tr:nth-child(even) td { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .badge-short { background: #fee2e2; color: #991b1b; font-weight: bold; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>

    <div class="header clearfix">
        <div style="float: left;">
            <div class="title">Master Short Parts & Shortage Report</div>
            <div class="subtitle">Consolidated inventory stockouts and short parts across all active orders & reservations</div>
        </div>
        <div class="meta">
            <div><strong>Generated:</strong> {{ $generatedAt }}</div>
            <div><strong>Short Item Codes:</strong> {{ $grouped->count() }}</div>
        </div>
    </div>

    @php
        $totalMissingQty = $grouped->sum('total_short');
        $totalImpactedOrders = $grouped->sum('orders_count');
    @endphp

    <table class="kpi-table">
        <tr>
            <td class="kpi-box" style="width: 33%;">
                <div class="kpi-label">Distinct Items with Shortage</div>
                <div class="kpi-val">{{ $grouped->count() }} Codes</div>
            </td>
            <td class="kpi-box" style="width: 34%;">
                <div class="kpi-label">Total Short / Missing Quantity</div>
                <div class="kpi-val">{{ number_format($totalMissingQty, 2) }} Units</div>
            </td>
            <td class="kpi-box" style="width: 33%;">
                <div class="kpi-label">Total Affected Order Instances</div>
                <div class="kpi-val">{{ $totalImpactedOrders }} Reservations</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 13%;">Item Code</th>
                <th style="width: 22%;">Description</th>
                <th class="text-right" style="width: 8%;">Total Req</th>
                <th class="text-right" style="width: 8%;">Total Avail</th>
                <th class="text-right" style="width: 10%;">Total Short</th>
                <th style="width: 18%;">Affected Orders (Reserve #)</th>
                <th style="width: 10%;">Bin / Shelf</th>
                <th style="width: 11%;">Reasons</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grouped as $g)
                <tr>
                    <td class="font-mono font-bold">{{ $g->item_code }}</td>
                    <td>{{ $g->description ?: '-' }}</td>
                    <td class="text-right font-mono">{{ number_format($g->total_requested, 2) }}</td>
                    <td class="text-right font-mono" style="color: #059669;">{{ number_format($g->total_available, 2) }}</td>
                    <td class="text-right font-mono font-bold">
                        <span class="badge-short">-{{ number_format($g->total_short, 2) }}</span>
                    </td>
                    <td class="font-mono" style="font-size: 7pt; color: #4338ca;">
                        {{ $g->reservations }}
                    </td>
                    <td>{{ $g->bins }}</td>
                    <td style="font-style: italic; color: #64748b; font-size: 7pt;">{{ $g->reasons }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 25px; color:#059669; font-weight: bold;">
                        ✓ Zero Shortages! All items across all active reservations are fully available in warehouse.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
