<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Orders & Factory Shipments Report</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 8pt; color: #1e293b; margin: 12px; }
        .header { border-bottom: 2px solid #7e22ce; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 14pt; font-weight: bold; color: #581c87; text-transform: uppercase; }
        .subtitle { font-size: 8pt; color: #64748b; margin-top: 2px; }
        .meta { float: right; text-align: right; font-size: 7.5pt; color: #475569; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .kpi-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .kpi-box { background: #faf5ff; border: 1px solid #e9d5ff; padding: 6px 8px; text-align: center; }
        .kpi-label { font-size: 6.5pt; color: #7e22ce; text-transform: uppercase; font-weight: bold; }
        .kpi-val { font-size: 11pt; font-weight: bold; color: #3b0764; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 7.5pt; }
        table.data th { background: #581c87; color: #ffffff; text-align: left; padding: 5px 6px; font-size: 6.5pt; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 5px 6px; vertical-align: top; }
        table.data tr:nth-child(even) td { background-color: #faf5ff; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .badge { display: inline-block; padding: 1px 5px; font-size: 6.5pt; font-weight: bold; border-radius: 3px; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-partial { background: #fef3c7; color: #92400e; }
        .badge-pending { background: #f1f5f9; color: #475569; }
        .progress-bar { background: #e2e8f0; border-radius: 2px; height: 5px; width: 45px; display: inline-block; vertical-align: middle; }
        .progress-fill { background: #10b981; height: 5px; border-radius: 2px; }
        .tag-factory { display: inline-block; background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4; padding: 1px 3px; font-size: 6pt; border-radius: 2px; margin-top: 1px; }
        tfoot td { background: #f3e8ff !important; font-weight: bold; border-top: 2px solid #a855f7; }
    </style>
</head>
<body>

    <div class="header clearfix">
        <div style="float: left;">
            <div class="title">Purchase Orders & Factory Shipments Report</div>
            <div class="subtitle">Official Procurement Ledger &bull; Inward Factory Shipments Verification &bull; Pending Balances</div>
        </div>
        <div class="meta">
            <div><strong>Generated:</strong> {{ $generatedAt }}</div>
            <div><strong>Total Purchase Orders:</strong> {{ $kpis['total_pos'] }}</div>
            @if(!empty($filters['start_date']) || !empty($filters['end_date']))
                <div><strong>Period:</strong> {{ $filters['start_date'] ?? 'Any' }} to {{ $filters['end_date'] ?? 'Any' }}</div>
            @endif
        </div>
    </div>

    <table class="kpi-table">
        <tr>
            <td class="kpi-box" style="width: 20%;">
                <div class="kpi-label">Purchase Orders</div>
                <div class="kpi-val">{{ number_format($kpis['total_pos']) }}</div>
            </td>
            <td class="kpi-box" style="width: 20%;">
                <div class="kpi-label">Total Ordered Units</div>
                <div class="kpi-val">{{ number_format($kpis['total_ordered']) }}</div>
            </td>
            <td class="kpi-box" style="width: 20%; background: #f0fdf4; border-color: #bbf7d0;">
                <div class="kpi-label" style="color: #15803d;">Received from Factory</div>
                <div class="kpi-val" style="color: #166534;">{{ number_format($kpis['total_received']) }}</div>
            </td>
            <td class="kpi-box" style="width: 20%; background: #fffbeb; border-color: #fde68a;">
                <div class="kpi-label" style="color: #b45309;">Pending Delivery</div>
                <div class="kpi-val" style="color: #92400e;">{{ number_format($kpis['total_remaining']) }}</div>
            </td>
            <td class="kpi-box" style="width: 20%;">
                <div class="kpi-label">Overall Fulfillment</div>
                <div class="kpi-val">{{ $kpis['fulfillment_pct'] }}%</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 10%;">PO # (B-No)</th>
                <th style="width: 18%;">Supplier / Factory</th>
                <th class="text-center" style="width: 9%;">PO Date</th>
                <th class="text-right" style="width: 8%;">Ordered</th>
                <th class="text-right" style="width: 8%;">Received</th>
                <th class="text-right" style="width: 8%;">Remaining</th>
                <th class="text-center" style="width: 11%;">Fulfillment</th>
                <th class="text-center" style="width: 9%;">Status</th>
                <th style="width: 19%;">Inward Factory Invoices</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $ord)
                @php
                    $pct = $ord['fulfillment_percentage'];
                    $status = $ord['overall_status'];
                @endphp
                <tr>
                    <td class="font-mono font-bold">{{ $ord['document_number'] }}</td>
                    <td>
                        <strong>{{ $ord['company_name'] }}</strong>
                        @if($ord['document']->country)
                            <div style="color:#64748b; font-size:6.5pt;">{{ $ord['document']->country }}</div>
                        @endif
                        <div style="color:#94a3b8; font-size:6pt;">{{ $ord['total_items_count'] }} unique item(s)</div>
                    </td>
                    <td class="text-center font-mono" style="font-size:7pt;">{{ $ord['formatted_date'] ?? '—' }}</td>
                    <td class="text-right font-mono font-bold">{{ number_format($ord['total_ordered_qty']) }}</td>
                    <td class="text-right font-mono font-bold" style="color: #0f766e;">{{ number_format($ord['total_received_qty']) }}</td>
                    <td class="text-right font-mono font-bold" style="{{ $ord['total_remaining_qty'] > 0 ? 'color: #b45309;' : 'color: #16a34a;' }}">
                        {{ number_format($ord['total_remaining_qty']) }}
                    </td>
                    <td class="text-center">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ min(100, $pct) }}%; {{ $pct >= 100 ? 'background:#10b981;' : ($pct > 0 ? 'background:#f59e0b;' : 'background:#cbd5e1;') }}"></div>
                        </div>
                        <span class="font-mono font-bold" style="font-size: 7pt; margin-left: 2px;">{{ $pct }}%</span>
                    </td>
                    <td class="text-center">
                        @if($status === 'completed')
                            <span class="badge badge-completed">COMPLETED</span>
                        @elseif($status === 'partially_received')
                            <span class="badge badge-partial">PARTIAL ({{ $pct }}%)</span>
                        @else
                            <span class="badge badge-pending">PENDING</span>
                        @endif
                    </td>
                    <td>
                        @forelse($ord['factory_invoices'] as $fi)
                            <span class="tag-factory font-mono">
                                {{ $fi['document_number'] }}
                                @if($fi['formatted_date']) ({{ $fi['formatted_date'] }}) @endif
                                &bull; {{ number_format($fi['total_qty']) }}u
                            </span>
                        @empty
                            <span style="color: #94a3b8; font-size: 6.5pt; font-style: italic;">No shipments received yet</span>
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #94a3b8;">
                        No Purchase Orders found matching the criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($orders->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right font-bold" style="text-transform: uppercase; font-size: 7pt;">Total Across {{ $orders->count() }} Purchase Orders:</td>
                    <td class="text-right font-mono font-bold">{{ number_format($kpis['total_ordered']) }}</td>
                    <td class="text-right font-mono font-bold" style="color: #0f766e;">{{ number_format($kpis['total_received']) }}</td>
                    <td class="text-right font-mono font-bold" style="color: #b45309;">{{ number_format($kpis['total_remaining']) }}</td>
                    <td class="text-center font-mono font-bold">{{ $kpis['fulfillment_pct'] }}%</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div style="margin-top: 15px; font-size: 6.5pt; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px;" class="clearfix">
        <div style="float: left;">Checking TA Document Management &bull; Purchase Orders &amp; Factory Shipment Tracking</div>
        <div style="float: right;">Page 1 of 1</div>
    </div>

</body>
</html>
