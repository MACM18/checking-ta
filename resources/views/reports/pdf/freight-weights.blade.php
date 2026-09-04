<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Freight & Weights Orders Log</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #1e293b; margin: 15px; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 14pt; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .subtitle { font-size: 8pt; color: #64748b; margin-top: 2px; }
        .meta { float: right; text-align: right; font-size: 8pt; color: #475569; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .kpi-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .kpi-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 10px; text-align: center; }
        .kpi-label { font-size: 7pt; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .kpi-val { font-size: 11pt; font-weight: bold; color: #0f172a; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 7.5pt; }
        table.data th { background: #1e293b; color: #ffffff; text-align: left; padding: 5px 6px; font-size: 7pt; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; }
        table.data tr:nth-child(even) td { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .badge { display: inline-block; padding: 1px 4px; font-size: 6.5pt; font-weight: bold; border-radius: 3px; }
        .badge-carrier { background: #e0e7ff; color: #3730a3; }
        .badge-type { background: #f1f5f9; color: #334155; }
    </style>
</head>
<body>

    <div class="header clearfix">
        <div style="float: left;">
            <div class="title">Orders Log: Weights & Freight Charges</div>
            <div class="subtitle">Detailed breakdown of Net/Gross weights, package counts, carrier methods, and quoted freight rates</div>
        </div>
        <div class="meta">
            <div><strong>Generated:</strong> {{ $generatedAt }}</div>
            <div><strong>Total Orders:</strong> {{ $documents->count() }}</div>
        </div>
    </div>

    @php
        $totNet = $documents->sum('total_net_weight');
        $totGross = $documents->sum('total_gross_weight');
        $totPkgs = $documents->sum(fn($d) => $d->packages->sum('quantity'));
        $totFreight = $documents->sum(fn($d) => $d->shipmentCosts->sum('given_amount'));
    @endphp

    <table class="kpi-table">
        <tr>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Total Net Weight</div>
                <div class="kpi-val">{{ number_format($totNet, 2) }} KG</div>
            </td>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Total Gross Weight</div>
                <div class="kpi-val">{{ number_format($totGross, 2) }} KG</div>
            </td>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Total Packages</div>
                <div class="kpi-val">{{ number_format($totPkgs) }} PKGS</div>
            </td>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Total Quoted Freight</div>
                <div class="kpi-val">{{ number_format($totFreight, 2) }} USD</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 10%;">Doc Number</th>
                <th style="width: 10%;">Type</th>
                <th style="width: 8%;">Date</th>
                <th style="width: 17%;">Client / Country</th>
                <th class="text-right" style="width: 8%;">Net Wt (KG)</th>
                <th class="text-right" style="width: 8%;">Gross Wt (KG)</th>
                <th class="text-center" style="width: 6%;">Pkgs</th>
                <th style="width: 11%;">Carrier Method</th>
                <th class="text-right" style="width: 7%;">Rate / KG</th>
                <th class="text-right" style="width: 8%;">Given Freight</th>
                <th class="text-right" style="width: 7%;">Order Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $doc)
                @if($doc->shipmentCosts->isNotEmpty())
                    @foreach($doc->shipmentCosts as $ship)
                        <tr>
                            <td class="font-mono font-bold">{{ $doc->document_number }}</td>
                            <td><span class="badge badge-type">{{ $doc->formatted_type }}</span></td>
                            <td>{{ $doc->document_date ? $doc->document_date->format('Y-m-d') : '-' }}</td>
                            <td>
                                <strong>{{ $doc->company_name }}</strong>
                                @if($doc->country)<br><span style="color:#64748b; font-size:6.5pt;">{{ $doc->country }}</span>@endif
                            </td>
                            <td class="text-right font-mono">{{ number_format((float)$doc->total_net_weight, 2) }}</td>
                            <td class="text-right font-mono">{{ number_format((float)$doc->total_gross_weight, 2) }}</td>
                            <td class="text-center font-mono">{{ $doc->packages->sum('quantity') ?: '-' }}</td>
                            <td><span class="badge badge-carrier">{{ $ship->method_label ?? $ship->method }}</span></td>
                            <td class="text-right font-mono">{{ number_format((float)$ship->rate_per_kg, 2) }}</td>
                            <td class="text-right font-mono font-bold">{{ number_format((float)$ship->given_amount, 2) }} {{ $doc->currency }}</td>
                            <td class="text-right font-mono">{{ number_format((float)$doc->final_total, 2) }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="font-mono font-bold">{{ $doc->document_number }}</td>
                        <td><span class="badge badge-type">{{ $doc->formatted_type }}</span></td>
                        <td>{{ $doc->document_date ? $doc->document_date->format('Y-m-d') : '-' }}</td>
                        <td>
                            <strong>{{ $doc->company_name }}</strong>
                            @if($doc->country)<br><span style="color:#64748b; font-size:6.5pt;">{{ $doc->country }}</span>@endif
                        </td>
                        <td class="text-right font-mono">{{ number_format((float)$doc->total_net_weight, 2) }}</td>
                        <td class="text-right font-mono">{{ number_format((float)$doc->total_gross_weight, 2) }}</td>
                        <td class="text-center font-mono">{{ $doc->packages->sum('quantity') ?: '-' }}</td>
                        <td style="color:#94a3b8; font-style:italic;">No freight quotes</td>
                        <td class="text-right font-mono">-</td>
                        <td class="text-right font-mono">-</td>
                        <td class="text-right font-mono">{{ number_format((float)$doc->final_total, 2) }}</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px; color:#94a3b8;">No document records found matching the specified filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
