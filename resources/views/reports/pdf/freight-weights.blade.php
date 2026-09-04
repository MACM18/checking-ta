<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Freight & Weights Orders Log</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 8pt; color: #1e293b; margin: 12px; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 10px; }
        .title { font-size: 13pt; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .subtitle { font-size: 7.5pt; color: #64748b; margin-top: 2px; }
        .meta { float: right; text-align: right; font-size: 7.5pt; color: #475569; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .kpi-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .kpi-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 5px 8px; text-align: center; }
        .kpi-label { font-size: 6.5pt; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .kpi-val { font-size: 10pt; font-weight: bold; color: #0f172a; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 7pt; }
        table.data th { background: #1e293b; color: #ffffff; text-align: left; padding: 5px 5px; font-size: 6.5pt; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 4px 5px; vertical-align: middle; }
        table.data tr:nth-child(even) td { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .badge { display: inline-block; padding: 1px 3px; font-size: 6pt; font-weight: bold; border-radius: 3px; }
        .badge-carrier { background: #e0e7ff; color: #3730a3; }
        .badge-type { background: #f1f5f9; color: #334155; }
        .badge-source { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    </style>
</head>
<body>

    @php
        $ordersList = $orders ?? $documents;
        $totNet = $ordersList->sum('net_weight');
        $totGross = $ordersList->sum('gross_weight');
        $totPkgs = $ordersList->sum('packages_count');
        $totFreight = $ordersList->sum('shipping_cost');
    @endphp

    <div class="header clearfix">
        <div style="float: left;">
            <div class="title">Orders Log: Weights & Freight Charges</div>
            <div class="subtitle">Consolidated single-row order breakdown of Net/Gross weights, package counts, linked documents, carrier methods, and quoted freight rates</div>
        </div>
        <div class="meta">
            <div><strong>Generated:</strong> {{ $generatedAt }}</div>
            <div><strong>Total Orders:</strong> {{ $ordersList->count() }}</div>
        </div>
    </div>

    <table class="kpi-table">
        <tr>
            <td class="kpi-box" style="width: 20%;">
                <div class="kpi-label">Total Orders</div>
                <div class="kpi-val">{{ number_format($ordersList->count()) }}</div>
            </td>
            <td class="kpi-box" style="width: 20%;">
                <div class="kpi-label">Total Net Weight</div>
                <div class="kpi-val">{{ number_format($totNet, 2) }} KG</div>
            </td>
            <td class="kpi-box" style="width: 20%;">
                <div class="kpi-label">Total Gross Weight</div>
                <div class="kpi-val">{{ number_format($totGross, 2) }} KG</div>
            </td>
            <td class="kpi-box" style="width: 20%;">
                <div class="kpi-label">Total Packages</div>
                <div class="kpi-val">{{ number_format($totPkgs) }} PKGS</div>
            </td>
            <td class="kpi-box" style="width: 20%;">
                <div class="kpi-label">Total Shipping Freight</div>
                <div class="kpi-val">{{ number_format($totFreight, 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 8%;">Order #</th>
                <th style="width: 6%;">Date</th>
                <th style="width: 13%;">Client / Country</th>
                <th style="width: 7%;">PI No</th>
                <th style="width: 7%;">Invoice No</th>
                <th style="width: 7%;">PKL No</th>
                <th style="width: 10%;">Other Docs / Refs</th>
                <th class="text-right" style="width: 6%;">Net Wt (KG)</th>
                <th class="text-right" style="width: 6%;">Gross Wt (KG)</th>
                <th class="text-center" style="width: 5%;">Source</th>
                <th class="text-center" style="width: 4%;">Pkgs</th>
                <th style="width: 9%;">Carrier & AWB</th>
                <th class="text-right" style="width: 6%;">Shipping Cost</th>
                <th class="text-right" style="width: 6%;">Order Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ordersList as $ord)
                <tr>
                    <td class="font-mono font-bold">{{ $ord->order_number }}</td>
                    <td>{{ $ord->formatted_date }}</td>
                    <td>
                        <strong>{{ $ord->company_name }}</strong>
                        @if($ord->country)<br><span style="color:#64748b; font-size:6.5pt;">{{ $ord->country }}</span>@endif
                    </td>
                    <td class="font-mono">{{ $ord->pi_number }}</td>
                    <td class="font-mono">{{ $ord->invoice_number }}</td>
                    <td class="font-mono">{{ $ord->pkl_number }}</td>
                    <td style="font-size:6.5pt; color:#475569;">{{ $ord->other_docs_refs }}</td>
                    <td class="text-right font-mono">{{ number_format((float)$ord->net_weight, 2) }}</td>
                    <td class="text-right font-mono">{{ number_format((float)$ord->gross_weight, 2) }}</td>
                    <td class="text-center"><span class="badge badge-source">{{ $ord->weight_source }}</span></td>
                    <td class="text-center font-mono">{{ $ord->packages_count ?: '-' }}</td>
                    <td>
                        @if($ord->carrier_method !== '-')
                            <span class="badge badge-carrier">{{ $ord->carrier_method }}</span>
                            @if($ord->tracking_awb !== '-')
                                <br><span style="color:#64748b; font-size:6.5pt;">AWB: {{ $ord->tracking_awb }}</span>
                            @endif
                        @else
                            <span style="color:#94a3b8;">-</span>
                        @endif
                    </td>
                    <td class="text-right font-mono font-bold">
                        @if($ord->shipping_cost > 0)
                            {{ number_format((float)$ord->shipping_cost, 2) }} <span style="font-size:6pt; font-weight:normal;">{{ $ord->currency }}</span>
                            @if($ord->rate_per_kg > 0)
                                <br><span style="color:#64748b; font-size:6pt; font-weight:normal;">@ {{ number_format((float)$ord->rate_per_kg, 2) }}/kg</span>
                            @endif
                        @else
                            <span style="color:#94a3b8;">-</span>
                        @endif
                    </td>
                    <td class="text-right font-mono font-bold">
                        @if($ord->order_total > 0)
                            {{ number_format((float)$ord->order_total, 2) }} <span style="font-size:6pt; font-weight:normal;">{{ $ord->currency }}</span>
                        @else
                            <span style="color:#94a3b8;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center" style="padding: 20px; color:#94a3b8;">No order records found matching the specified filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
