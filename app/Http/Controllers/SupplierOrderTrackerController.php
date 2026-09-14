<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\SupplierOrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierOrderTrackerController extends Controller
{
    public function __construct(
        protected SupplierOrderFulfillmentService $fulfillmentService
    ) {}

    /**
     * Display order sheets dashboard & reconciliation summary.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'all');
        $searchQuery = $request->query('q', '');
        $activeTab = $request->query('tab', 'sheets'); // 'sheets' or 'pending_items'

        // All order sheets with summary
        $orderSheets = $this->fulfillmentService->getAllOrderSheets([
            'status' => $statusFilter,
            'search' => $searchQuery,
        ]);

        // Unfiltered list to compute global KPIs
        $allSheets = $this->fulfillmentService->getAllOrderSheets();
        $kpis = [
            'total_orders' => $allSheets->count(),
            'total_ordered_qty' => $allSheets->sum('total_ordered_qty'),
            'total_received_qty' => $allSheets->sum('total_received_qty'),
            'total_remaining_qty' => $allSheets->sum('total_remaining_qty'),
            'completed_count' => $allSheets->where('overall_status', 'completed')->count(),
            'partial_count' => $allSheets->where('overall_status', 'partially_received')->count(),
            'pending_count' => $allSheets->where('overall_status', 'pending')->count(),
        ];

        $pendingItems = $activeTab === 'pending_items' ? $this->fulfillmentService->getPendingItemsMatrix() : collect();

        return view('supplier_orders.index', compact(
            'orderSheets',
            'kpis',
            'statusFilter',
            'searchQuery',
            'activeTab',
            'pendingItems'
        ));
    }

    /**
     * Show detailed reconciliation for a single Order Sheet (B-Number).
     */
    public function show(Document $document): View
    {
        $summary = $this->fulfillmentService->getOrderSheetSummary($document);

        return view('supplier_orders.show', compact('document', 'summary'));
    }

    /**
     * Printable view of an Order Sheet with received & pending items.
     */
    public function printSheet(Document $document): View
    {
        $summary = $this->fulfillmentService->getOrderSheetSummary($document);

        return view('supplier_orders.print-sheet', compact('document', 'summary'));
    }
}
