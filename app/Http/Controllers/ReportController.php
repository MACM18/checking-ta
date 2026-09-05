<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\OrderReservation;
use App\Models\OrderReservationItem;
use App\Models\ShipmentOrder;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportExportService $reportService
    ) {}

    protected function authorizeReports(): void
    {
        if (! Auth::user()?->canViewReports()) {
            abort(403, 'You do not have permission to view or export reports.');
        }
    }

    /**
     * Reports Center Hub
     */
    public function index(Request $request): View
    {
        $this->authorizeReports();

        $docStats = Document::selectRaw('count(*) as total_orders, coalesce(sum(total_gross_weight), 0) as total_weight_kg')->first();
        $shortStats = OrderReservationItem::where('short_qty', '>', 0)
            ->selectRaw('coalesce(sum(short_qty), 0) as total_short_parts, count(*) as short_items_count')
            ->first();

        $metrics = [
            'total_orders' => (int) ($docStats->total_orders ?? 0),
            'total_weight_kg' => (float) ($docStats->total_weight_kg ?? 0),
            'active_shipments' => ShipmentOrder::where('status', 'active')->count(),
            'total_short_parts' => (float) ($shortStats->total_short_parts ?? 0),
            'short_items_count' => (int) ($shortStats->short_items_count ?? 0),
        ];

        $documentTypes = Document::documentTypes();
        $carrierMethods = [
            'DHL' => 'DHL Express',
            'Air Freight' => 'Air Freight',
            'Sea Freight' => 'Sea Freight',
            'Road Freight' => 'Road Freight',
            'Courier' => 'Courier / Other',
        ];

        return view('reports.index', compact('metrics', 'documentTypes', 'carrierMethods'));
    }

    /**
     * 1. Export Freight & Weights Log (Excel or PDF)
     */
    public function exportFreightWeights(Request $request): Response|StreamedResponse
    {
        $this->authorizeReports();

        $validated = $request->validate([
            'format' => ['required', 'string', 'in:excel,pdf'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'document_type' => ['nullable', 'string'],
            'carrier_method' => ['nullable', 'string'],
        ]);

        return $this->reportService->exportFreightWeightsLog($validated, $validated['format']);
    }

    /**
     * 2. Export Ongoing Orders Progress (Excel or PDF)
     */
    public function exportOngoingOrders(Request $request): Response|StreamedResponse
    {
        $this->authorizeReports();

        $validated = $request->validate([
            'format' => ['required', 'string', 'in:excel,pdf'],
            'status' => ['nullable', 'string', 'in:active,completed,all'],
            'category' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
        ]);

        return $this->reportService->exportOngoingOrders($validated, $validated['format']);
    }

    /**
     * 3A. Export Master Consolidated Shortage Report (Excel or PDF)
     */
    public function exportMasterShortage(Request $request): Response|StreamedResponse
    {
        $this->authorizeReports();

        $validated = $request->validate([
            'format' => ['required', 'string', 'in:excel,pdf'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        return $this->reportService->exportMasterShortage($validated, $validated['format']);
    }

    /**
     * 3B. Export Single Order / Reservation Shortage Report (Excel or PDF)
     */
    public function exportReservationShortage(Request $request, OrderReservation $orderReservation): Response|StreamedResponse
    {
        $this->authorizeReports();

        $format = $request->input('format', 'excel');
        if (! in_array($format, ['excel', 'pdf'])) {
            $format = 'excel';
        }

        return $this->reportService->exportReservationShortage($orderReservation, $format);
    }
}
