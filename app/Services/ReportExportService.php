<?php

namespace App\Services;

use App\Models\Document;
use App\Models\OrderReservation;
use App\Models\OrderReservationItem;
use App\Models\ShipmentOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * 1. Freight & Weights Orders Log Report (Excel or PDF)
     */
    public function exportFreightWeightsLog(array $filters, string $format): Response|StreamedResponse
    {
        $query = Document::with(['packages', 'shipmentCosts'])
            ->orderByDesc('document_date')
            ->orderByDesc('id');

        if (! empty($filters['start_date'])) {
            $query->whereDate('document_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('document_date', '<=', $filters['end_date']);
        }
        if (! empty($filters['document_type']) && $filters['document_type'] !== 'all') {
            $query->where('document_type', $filters['document_type']);
        }

        $documents = $query->get();

        // If carrier filter is applied
        if (! empty($filters['carrier_method']) && $filters['carrier_method'] !== 'all') {
            $documents = $documents->filter(function ($doc) use ($filters) {
                return $doc->shipmentCosts->contains('method', $filters['carrier_method']);
            });
        }

        $filename = 'Freight_Weights_Log_'.now()->format('Ymd_His');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.freight-weights', [
                'documents' => $documents,
                'filters' => $filters,
                'generatedAt' => now()->format('M d, Y h:i A'),
            ])->setPaper('a4', 'landscape');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]);
        }

        return $this->generateFreightWeightsExcel($documents, $filename);
    }

    /**
     * 2. Current Ongoing Orders List with Progress Report (Excel or PDF)
     */
    public function exportOngoingOrders(array $filters, string $format): Response|StreamedResponse
    {
        $query = ShipmentOrder::with(['milestones', 'document'])
            ->orderBy('id', 'desc');

        $status = $filters['status'] ?? 'active';
        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('carrier_method', $filters['category']);
        }
        if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        $orders = $query->get();
        $filename = 'Ongoing_Orders_Progress_'.now()->format('Ymd_His');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.ongoing-orders', [
                'orders' => $orders,
                'filters' => $filters,
                'generatedAt' => now()->format('M d, Y h:i A'),
            ])->setPaper('a4', 'landscape');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]);
        }

        return $this->generateOngoingOrdersExcel($orders, $filename);
    }

    /**
     * 3A. Consolidated Master Short Parts / Shortage Report across all orders/reservations (Excel or PDF)
     */
    public function exportMasterShortage(array $filters, string $format): Response|StreamedResponse
    {
        $itemsQuery = OrderReservationItem::with('reservation')
            ->where(function ($q) {
                $q->where('short_qty', '>', 0)
                    ->orWhereIn('status', [OrderReservationItem::STATUS_SHORT, OrderReservationItem::STATUS_MISSING]);
            });

        if (! empty($filters['start_date'])) {
            $itemsQuery->whereHas('reservation', function ($rq) use ($filters) {
                $rq->whereDate('reservation_date', '>=', $filters['start_date']);
            });
        }
        if (! empty($filters['end_date'])) {
            $itemsQuery->whereHas('reservation', function ($rq) use ($filters) {
                $rq->whereDate('reservation_date', '<=', $filters['end_date']);
            });
        }

        $items = $itemsQuery->get();

        // Group by item_code for consolidated overview
        $grouped = $items->groupBy('item_code')->map(function (Collection $group, string $code) {
            $first = $group->first();
            $totalReq = (float) $group->sum('requested_qty');
            $totalAvail = (float) $group->sum('available_qty');
            $totalShort = (float) $group->sum('short_qty');
            $resNumbers = $group->pluck('reservation.reserve_document_number')->filter()->unique()->values()->all();
            $clients = $group->pluck('reservation.company_name')->filter()->unique()->values()->all();
            $bins = $group->pluck('bin_location')->filter()->unique()->values()->all();
            $reasons = $group->pluck('shortage_reason')->filter()->unique()->values()->all();

            return (object) [
                'item_code' => $code,
                'description' => $first->description,
                'total_requested' => $totalReq,
                'total_available' => $totalAvail,
                'total_short' => $totalShort,
                'orders_count' => count($resNumbers),
                'reservations' => implode(', ', $resNumbers),
                'clients' => implode(', ', $clients),
                'bins' => implode(', ', $bins) ?: 'N/A',
                'reasons' => implode(' | ', $reasons) ?: 'Stockout',
            ];
        })->values();

        $filename = 'Master_Short_Parts_Report_'.now()->format('Ymd_His');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.master-shortage', [
                'grouped' => $grouped,
                'rawItems' => $items,
                'filters' => $filters,
                'generatedAt' => now()->format('M d, Y h:i A'),
            ])->setPaper('a4', 'landscape');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]);
        }

        return $this->generateMasterShortageExcel($grouped, $items, $filename);
    }

    /**
     * 3B. Individual Reservation Shortage Report (Excel or PDF)
     */
    public function exportReservationShortage(OrderReservation $reservation, string $format): Response|StreamedResponse
    {
        $reservation->load(['items', 'confirmedBy']);

        $shortItems = $reservation->items->filter(function ($it) {
            return (float) $it->short_qty > 0 || $it->status === 'short' || $it->status === 'missing';
        })->values();

        $filename = 'Shortage_'.str_replace(['/', '\\', ' '], '_', $reservation->reserve_document_number).'_'.now()->format('Ymd');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.reservation-shortage', [
                'orderReservation' => $reservation,
                'shortItems' => $shortItems,
                'generatedAt' => now()->format('M d, Y h:i A'),
            ])->setPaper('a4', 'portrait');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]);
        }

        return $this->generateReservationShortageExcel($reservation, $shortItems, $filename);
    }

    // ==========================================
    // EXCEL SPREADSHEET GENERATORS
    // ==========================================

    /**
     * Generate Freight & Weights Excel Workbook (.xlsx)
     */
    protected function generateFreightWeightsExcel(Collection $documents, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Freight & Weights Log');

        // Headers
        $headers = [
            'A1' => 'Doc Number',
            'B1' => 'Doc Type',
            'C1' => 'Date',
            'D1' => 'Client / Company',
            'E1' => 'Country',
            'F1' => 'Net Weight (KG)',
            'G1' => 'Gross Weight (KG)',
            'H1' => 'Packages',
            'I1' => 'Total CBM',
            'J1' => 'Carrier Method',
            'K1' => 'Rate / KG',
            'L1' => 'System Amount',
            'M1' => 'Added Amount',
            'N1' => 'Given Freight Amount',
            'O1' => 'Currency',
            'P1' => 'Order Total',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $this->applyHeaderStyle($sheet, 'A1:P1');

        $row = 2;
        foreach ($documents as $doc) {
            $pkgCount = $doc->packages->sum('quantity') ?: 0;
            $totCbm = $doc->packages->sum('cbm') ?: 0;

            if ($doc->shipmentCosts->isNotEmpty()) {
                foreach ($doc->shipmentCosts as $ship) {
                    $sheet->setCellValue("A{$row}", $doc->document_number);
                    $sheet->setCellValue("B{$row}", $doc->formatted_type);
                    $sheet->setCellValue("C{$row}", $doc->document_date ? $doc->document_date->format('Y-m-d') : '');
                    $sheet->setCellValue("D{$row}", $doc->company_name);
                    $sheet->setCellValue("E{$row}", $doc->country);
                    $sheet->setCellValue("F{$row}", (float) $doc->total_net_weight);
                    $sheet->setCellValue("G{$row}", (float) $doc->total_gross_weight);
                    $sheet->setCellValue("H{$row}", (int) $pkgCount);
                    $sheet->setCellValue("I{$row}", (float) $totCbm);
                    $sheet->setCellValue("J{$row}", $ship->method_label ?? $ship->method);
                    $sheet->setCellValue("K{$row}", (float) $ship->rate_per_kg);
                    $sheet->setCellValue("L{$row}", (float) $ship->system_amount);
                    $sheet->setCellValue("M{$row}", (float) $ship->added_amount);
                    $sheet->setCellValue("N{$row}", (float) $ship->given_amount);
                    $sheet->setCellValue("O{$row}", $doc->currency);
                    $sheet->setCellValue("P{$row}", (float) $doc->final_total);
                    $row++;
                }
            } else {
                $sheet->setCellValue("A{$row}", $doc->document_number);
                $sheet->setCellValue("B{$row}", $doc->formatted_type);
                $sheet->setCellValue("C{$row}", $doc->document_date ? $doc->document_date->format('Y-m-d') : '');
                $sheet->setCellValue("D{$row}", $doc->company_name);
                $sheet->setCellValue("E{$row}", $doc->country);
                $sheet->setCellValue("F{$row}", (float) $doc->total_net_weight);
                $sheet->setCellValue("G{$row}", (float) $doc->total_gross_weight);
                $sheet->setCellValue("H{$row}", (int) $pkgCount);
                $sheet->setCellValue("I{$row}", (float) $totCbm);
                $sheet->setCellValue("J{$row}", 'None specified');
                $sheet->setCellValue("K{$row}", 0);
                $sheet->setCellValue("L{$row}", 0);
                $sheet->setCellValue("M{$row}", 0);
                $sheet->setCellValue("N{$row}", 0);
                $sheet->setCellValue("O{$row}", $doc->currency);
                $sheet->setCellValue("P{$row}", (float) $doc->final_total);
                $row++;
            }
        }

        $this->autoFitColumns($sheet, range('A', 'P'));

        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    /**
     * Generate Ongoing Orders Progress Excel Workbook (.xlsx)
     */
    protected function generateOngoingOrdersExcel(Collection $orders, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ongoing Orders Progress');

        $headers = [
            'A1' => 'Order #',
            'B1' => 'Client / Company',
            'C1' => 'Country',
            'D1' => 'Linked PI #',
            'E1' => 'Customer PO #',
            'F1' => 'PO Date',
            'G1' => 'Current Stage / Milestone',
            'H1' => 'Progress %',
            'I1' => 'Payment Status',
            'J1' => 'Carrier Method',
            'K1' => 'Tracking / AWB #',
            'L1' => 'Dispatch Date',
            'M1' => 'Delivery Date',
            'N1' => 'Status',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $this->applyHeaderStyle($sheet, 'A1:N1');

        $row = 2;
        foreach ($orders as $ord) {
            $latestMilestone = $ord->milestones->where('is_completed', true)->last()?->stage_name ?? 'PI Initialized';

            $sheet->setCellValue("A{$row}", $ord->order_number);
            $sheet->setCellValue("B{$row}", $ord->company_name);
            $sheet->setCellValue("C{$row}", $ord->country);
            $sheet->setCellValue("D{$row}", $ord->proforma_invoice_no ?: $ord->document_reference);
            $sheet->setCellValue("E{$row}", $ord->customer_po_number ?: '-');
            $sheet->setCellValue("F{$row}", $ord->customer_po_date ? $ord->customer_po_date->format('Y-m-d') : '');
            $sheet->setCellValue("G{$row}", $latestMilestone);
            $sheet->setCellValue("H{$row}", (int) $ord->progress_percent.'%');
            $sheet->setCellValue("I{$row}", ucwords(str_replace('_', ' ', $ord->payment_status)));
            $sheet->setCellValue("J{$row}", $ord->carrier_method ?: '-');
            $sheet->setCellValue("K{$row}", $ord->tracking_awb_no ?: '-');
            $sheet->setCellValue("L{$row}", $ord->dispatch_date ? $ord->dispatch_date->format('Y-m-d') : '');
            $sheet->setCellValue("M{$row}", $ord->delivery_date ? $ord->delivery_date->format('Y-m-d') : '');
            $sheet->setCellValue("N{$row}", ucfirst($ord->status));
            $row++;
        }

        $this->autoFitColumns($sheet, range('A', 'N'));

        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    /**
     * Generate Consolidated Master Shortage Excel Workbook (.xlsx)
     */
    protected function generateMasterShortageExcel(Collection $grouped, Collection $rawItems, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;

        // Sheet 1: Consolidated by Item
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Consolidated Shortages');

        $headers1 = [
            'A1' => 'Item Code',
            'B1' => 'Description',
            'C1' => 'Total Req Qty',
            'D1' => 'Total Avail Qty',
            'E1' => 'Total Short Qty',
            'F1' => 'Affected Orders Count',
            'G1' => 'Affected Reserve / Order Numbers',
            'H1' => 'Clients',
            'I1' => 'Bin Locations',
            'J1' => 'Shortage Reasons',
        ];

        foreach ($headers1 as $cell => $text) {
            $sheet1->setCellValue($cell, $text);
        }
        $this->applyHeaderStyle($sheet1, 'A1:J1');

        $row = 2;
        foreach ($grouped as $g) {
            $sheet1->setCellValue("A{$row}", $g->item_code);
            $sheet1->setCellValue("B{$row}", $g->description ?: '-');
            $sheet1->setCellValue("C{$row}", (float) $g->total_requested);
            $sheet1->setCellValue("D{$row}", (float) $g->total_available);
            $sheet1->setCellValue("E{$row}", (float) $g->total_short);
            $sheet1->setCellValue("F{$row}", (int) $g->orders_count);
            $sheet1->setCellValue("G{$row}", $g->reservations);
            $sheet1->setCellValue("H{$row}", $g->clients);
            $sheet1->setCellValue("I{$row}", $g->bins);
            $sheet1->setCellValue("J{$row}", $g->reasons);
            $row++;
        }
        $this->autoFitColumns($sheet1, range('A', 'J'));

        // Sheet 2: Detailed Line Items
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Order Shortage Details');

        $headers2 = [
            'A1' => 'Reserve Doc #',
            'B1' => 'Client / Company',
            'C1' => 'Reservation Date',
            'D1' => 'Item Code',
            'E1' => 'Description',
            'F1' => 'Req Qty',
            'G1' => 'Avail Qty',
            'H1' => 'Short Qty',
            'I1' => 'Bin Location',
            'J1' => 'Shortage Reason',
            'K1' => 'Status',
        ];

        foreach ($headers2 as $cell => $text) {
            $sheet2->setCellValue($cell, $text);
        }
        $this->applyHeaderStyle($sheet2, 'A1:K1');

        $r2 = 2;
        foreach ($rawItems as $it) {
            $res = $it->reservation;
            $sheet2->setCellValue("A{$r2}", $res?->reserve_document_number ?: '-');
            $sheet2->setCellValue("B{$r2}", $res?->company_name ?: '-');
            $sheet2->setCellValue("C{$r2}", $res?->reservation_date ? $res->reservation_date->format('Y-m-d') : '');
            $sheet2->setCellValue("D{$r2}", $it->item_code);
            $sheet2->setCellValue("E{$r2}", $it->description ?: '-');
            $sheet2->setCellValue("F{$r2}", (float) $it->requested_qty);
            $sheet2->setCellValue("G{$r2}", (float) $it->available_qty);
            $sheet2->setCellValue("H{$r2}", (float) $it->short_qty);
            $sheet2->setCellValue("I{$r2}", $it->bin_location ?: '-');
            $sheet2->setCellValue("J{$r2}", $it->shortage_reason ?: 'Out of stock');
            $sheet2->setCellValue("K{$r2}", $it->status_label);
            $r2++;
        }
        $this->autoFitColumns($sheet2, range('A', 'K'));

        $spreadsheet->setActiveSheetIndex(0);

        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    /**
     * Generate Individual Reservation Shortage Excel Workbook (.xlsx)
     */
    protected function generateReservationShortageExcel(OrderReservation $reservation, Collection $shortItems, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Shortage Report');

        // Document Info Block
        $sheet->setCellValue('A1', 'ORDER RESERVATION SHORTAGE REPORT');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('991B1B');

        $sheet->setCellValue('A2', 'Reserve Document: '.$reservation->reserve_document_number);
        $sheet->setCellValue('D2', 'Report Date: '.now()->format('Y-m-d H:i'));
        $sheet->setCellValue('A3', 'Client: '.($reservation->company_name ?: 'N/A'));
        $sheet->setCellValue('D3', 'Warehouse Location: '.($reservation->warehouse_location ?: 'N/A'));
        $sheet->setCellValue('A4', 'Status: '.$reservation->status_label);
        $sheet->setCellValue('D4', 'Verified By: '.($reservation->confirmedBy?->name ?: 'Pending'));

        // Table Headers at row 6
        $headers = [
            'A6' => '#',
            'B6' => 'Item Code',
            'C6' => 'Description',
            'D6' => 'Req Qty',
            'E6' => 'Avail Qty',
            'F6' => 'Short Qty',
            'G6' => 'Bin Location',
            'H6' => 'Shortage Reason',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }
        $this->applyHeaderStyle($sheet, 'A6:H6');

        $row = 7;
        foreach ($shortItems as $idx => $it) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $it->item_code);
            $sheet->setCellValue("C{$row}", $it->description ?: '-');
            $sheet->setCellValue("D{$row}", (float) $it->requested_qty);
            $sheet->setCellValue("E{$row}", (float) $it->available_qty);
            $sheet->setCellValue("F{$row}", (float) $it->short_qty);
            $sheet->setCellValue("G{$row}", $it->bin_location ?: '-');
            $sheet->setCellValue("H{$row}", $it->shortage_reason ?: 'Out of stock');
            $row++;
        }

        $this->autoFitColumns($sheet, range('A', 'H'));

        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    // ==========================================
    // SPREADSHEET HELPER UTILITIES
    // ==========================================

    protected function applyHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate 800
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '0F172A'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
    }

    protected function autoFitColumns($sheet, array $columns): void
    {
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function streamSpreadsheet(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "{$filename}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
