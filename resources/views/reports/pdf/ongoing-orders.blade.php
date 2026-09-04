<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ongoing Orders Progress Report</title>
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
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 5px 6px; }
        table.data tr:nth-child(even) td { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .badge { display: inline-block; padding: 1px 4px; font-size: 6.5pt; font-weight: bold; border-radius: 3px; }
        .badge-active { background: #e0f2fe; color: #0369a1; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .progress-bar { background: #e2e8f0; border-radius: 2px; height: 6px; width: 60px; display: inline-block; vertical-align: middle; }
        .progress-fill { background: #10b981; height: 6px; border-radius: 2px; }
    </style>
</head>
<body>

    <div class="header clearfix">
        <div style="float: left;">
            <div class="title">Shipment Orders Progress Tracker</div>
            <div class="subtitle">Live status, milestones completion, payment stages, and carrier tracking for active orders</div>
        </div>
        <div class="meta">
            <div><strong>Generated:</strong> {{ $generatedAt }}</div>
            <div><strong>Total Orders:</strong> {{ $orders->count() }}</div>
        </div>
    </div>

    @php
        $activeCount = $orders->where('status', 'active')->count();
        $completedCount = $orders->where('status', 'completed')->count();
        $avgProgress = $orders->count() > 0 ? (int) $orders->avg('progress_percent') : 0;
    @endphp

    <table class="kpi-table">
        <tr>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Active Ongoing Orders</div>
                <div class="kpi-val">{{ $activeCount }}</div>
            </td>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Completed Orders</div>
                <div class="kpi-val">{{ $completedCount }}</div>
            </td>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Average Order Progress</div>
                <div class="kpi-val">{{ $avgProgress }}%</div>
            </td>
            <td class="kpi-box" style="width: 25%;">
                <div class="kpi-label">Report Scope</div>
                <div class="kpi-val" style="font-size: 9pt;">{{ ucfirst($filters['status'] ?? 'Active') }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 10%;">Order #</th>
                <th style="width: 16%;">Client & Country</th>
                <th style="width: 11%;">Linked Docs</th>
                <th style="width: 11%;">Customer PO</th>
                <th style="width: 16%;">Current Milestone Stage</th>
                <th class="text-center" style="width: 10%;">Progress</th>
                <th style="width: 10%;">Payment Status</th>
                <th style="width: 16%;">Carrier & Tracking</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $ord)
                @php
                    $latestMilestone = $ord->milestones->where('is_completed', true)->last()?->stage_name ?? 'PI Initialized';
                @endphp
                <tr>
                    <td class="font-mono font-bold">{{ $ord->order_number }}</td>
                    <td>
                        <strong>{{ $ord->company_name }}</strong>
                        @if($ord->country)<br><span style="color:#64748b; font-size:6.5pt;">{{ $ord->country }}</span>@endif
                    </td>
                    <td class="font-mono" style="font-size:7pt;">
                        <div>PI: {{ $ord->proforma_invoice_no ?: '-' }}</div>
                        @if($ord->linked_invoice_no)<div>INV: {{ $ord->linked_invoice_no }}</div>@endif
                        @if($ord->linked_packing_list_no)<div>PL: {{ $ord->linked_packing_list_no }}</div>@endif
                    </td>
                    <td>
                        @if($ord->customer_po_number)
                            <strong class="font-mono">{{ $ord->customer_po_number }}</strong>
                            @if($ord->customer_po_date)<br><span style="color:#64748b; font-size:6.5pt;">{{ $ord->customer_po_date->format('Y-m-d') }}</span>@endif
                        @else
                            <span style="color:#94a3b8; font-style:italic;">None</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $latestMilestone }}</strong>
                    </td>
                    <td class="text-center">
                        <span class="font-bold font-mono">{{ $ord->progress_percent }}%</span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $ord->progress_percent }}%;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $ord->payment_status === 'fully_paid' ? 'badge-paid' : 'badge-pending' }}">
                            {{ ucwords(str_replace('_', ' ', $ord->payment_status)) }}
                        </span>
                    </td>
                    <td>
                        @if($ord->carrier_method || $ord->tracking_awb_no)
                            <strong>{{ $ord->carrier_method ?: 'Courier' }}</strong>
                            @if($ord->tracking_awb_no)<br><span class="font-mono" style="font-size:6.5pt;">AWB: {{ $ord->tracking_awb_no }}</span>@endif
                        @else
                            <span style="color:#94a3b8; font-style:italic;">Pending dispatch</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color:#94a3b8;">No ongoing orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
