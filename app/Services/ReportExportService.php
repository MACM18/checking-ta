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
    /**
     * 1. Freight & Weights Orders Log Report (Excel or PDF)
     * Consolidated: Exactly 1 record per order, combining PI, Invoice, PKL, Reserve, and other docs.
     * Weights resolve: PKL weight -> Reserve net weight -> Invoice/PI weight.
     */
    public function exportFreightWeightsLog(array $filters, string $format): Response|StreamedResponse
    {
        $orders = $this->buildConsolidatedFreightWeightsRecords($filters);

        $filename = 'Freight_Weights_Log_'.now()->format('Ymd_His');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.freight-weights', [
                'orders' => $orders,
                'documents' => $orders,
                'filters' => $filters,
                'generatedAt' => now()->format('M d, Y h:i A'),
            ])->setPaper('a4', 'landscape');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]);
        }

        return $this->generateFreightWeightsExcel($orders, $filename);
    }

    /**
     * Build consolidated order records for Freight & Weights log (1 record per order).
     * Combines PI No, Invoice No, PKL No, and Other Docs/Refs.
     * Weight priority: PKL weight -> Reserve net weight -> Invoice/PI weight.
     * Includes shipping cost details.
     */
    public function buildConsolidatedFreightWeightsRecords(array $filters): Collection
    {
        $allDocuments = Document::with(['packages', 'shipmentCosts'])
            ->orderByDesc('document_date')
            ->orderByDesc('id')
            ->get();

        $allReservations = OrderReservation::all();

        $shipmentOrders = ShipmentOrder::with([
            'document.packages',
            'document.shipmentCosts',
            'invoiceDocument.packages',
            'invoiceDocument.shipmentCosts',
            'packingListDocument.packages',
            'packingListDocument.shipmentCosts',
        ])->orderByDesc('id')->get();

        $usedDocIds = [];
        $consolidatedOrders = collect();

        $cleanStr = fn (?string $val) => $val ? strtoupper(trim($val)) : '';

        $normalizeCoreNumber = function (?string $num) {
            if (empty($num)) {
                return '';
            }
            $clean = strtoupper(trim($num));
            if (str_ends_with($clean, 'R')) {
                $clean = substr($clean, 0, -1);
            }
            if (preg_match('/^(?:EL|CR|[ENWDC])(\d+)$/i', $clean, $m)) {
                return $m[1];
            }

            return $clean;
        };

        // 1. Process ShipmentOrders
        foreach ($shipmentOrders as $so) {
            $piDoc = null;
            if ($so->document_id) {
                $piDoc = $allDocuments->firstWhere('id', $so->document_id);
            }
            if (! $piDoc && ! empty($so->proforma_invoice_no)) {
                $piDoc = $allDocuments->first(fn ($d) => $cleanStr($d->document_number) === $cleanStr($so->proforma_invoice_no));
            }

            $invDoc = null;
            if ($so->invoice_document_id) {
                $invDoc = $allDocuments->firstWhere('id', $so->invoice_document_id);
            }
            if (! $invDoc && ! empty($so->linked_invoice_no)) {
                $invDoc = $allDocuments->first(fn ($d) => $cleanStr($d->document_number) === $cleanStr($so->linked_invoice_no));
            }

            $pklDoc = null;
            if ($so->packing_list_document_id) {
                $pklDoc = $allDocuments->firstWhere('id', $so->packing_list_document_id);
            }
            if (! $pklDoc && ! empty($so->linked_packing_list_no)) {
                $pklDoc = $allDocuments->first(fn ($d) => $cleanStr($d->document_number) === $cleanStr($so->linked_packing_list_no));
            }

            foreach ([$piDoc, $invDoc, $pklDoc] as $d) {
                if ($d) {
                    $usedDocIds[$d->id] = true;
                }
            }

            $basePi = $cleanStr($so->proforma_invoice_no ?: $piDoc?->document_number);
            $baseInv = $cleanStr($so->linked_invoice_no ?: $invDoc?->document_number);
            $basePkl = $cleanStr($so->linked_packing_list_no ?: $pklDoc?->document_number);
            $soCore = $normalizeCoreNumber($basePi ?: ($baseInv ?: $basePkl));

            $relatedDocs = $allDocuments->filter(function ($doc) use ($piDoc, $invDoc, $pklDoc, $cleanStr, $basePi, $baseInv, $basePkl, $usedDocIds, $soCore, $normalizeCoreNumber) {
                if (isset($usedDocIds[$doc->id])) {
                    return false;
                }
                if ($piDoc && $doc->source_document_id === $piDoc->id) {
                    return true;
                }
                if ($invDoc && $doc->source_document_id === $invDoc->id) {
                    return true;
                }
                if ($pklDoc && $doc->source_document_id === $pklDoc->id) {
                    return true;
                }

                $srcNum = $cleanStr($doc->source_document_number);
                if ($srcNum && (
                    ($basePi && $srcNum === $basePi) ||
                    ($baseInv && $srcNum === $baseInv) ||
                    ($basePkl && $srcNum === $basePkl)
                )) {
                    return true;
                }

                if ($soCore !== '' && $normalizeCoreNumber($doc->document_number) === $soCore) {
                    return true;
                }

                return false;
            });

            $reserveDoc = $relatedDocs->first(fn ($d) => $d->isReserve());
            $otherRelated = $relatedDocs->filter(fn ($d) => $d !== $reserveDoc);

            foreach ($relatedDocs as $rd) {
                $usedDocIds[$rd->id] = true;
            }

            $reservation = null;
            if ($reserveDoc) {
                $reservation = $allReservations->firstWhere('document_id', $reserveDoc->id)
                    ?: $allReservations->first(fn ($r) => $cleanStr($r->reserve_document_number) === $cleanStr($reserveDoc->document_number));
            }
            if (! $reservation && $piDoc) {
                $reservation = $allReservations->firstWhere('document_id', $piDoc->id)
                    ?: $allReservations->first(fn ($r) => $cleanStr($r->reserve_document_number) === $cleanStr($piDoc->document_number).'R');
            }

            $consolidatedOrders->push($this->formatConsolidatedRecord(
                shipmentOrder: $so,
                piDoc: $piDoc,
                invDoc: $invDoc,
                pklDoc: $pklDoc,
                reserveDoc: $reserveDoc,
                reservation: $reservation,
                otherDocs: $otherRelated,
                fallbackDoc: $piDoc ?: ($invDoc ?: ($pklDoc ?: $reserveDoc))
            ));
        }

        // 2. Process remaining documents grouped by document family
        $remainingDocs = $allDocuments->reject(fn ($d) => isset($usedDocIds[$d->id]))->values();
        $clusters = [];

        foreach ($remainingDocs as $doc) {
            if (isset($usedDocIds[$doc->id])) {
                continue;
            }

            $clusterKey = null;
            if (! empty($doc->source_document_number)) {
                $clusterKey = $normalizeCoreNumber($doc->source_document_number);
            } elseif ($doc->source_document_id) {
                $src = $allDocuments->firstWhere('id', $doc->source_document_id);
                if ($src) {
                    $clusterKey = $normalizeCoreNumber($src->source_document_number ?: $src->document_number);
                }
            }

            if (! $clusterKey) {
                $clusterKey = $normalizeCoreNumber($doc->document_number);
            }

            $clusters[$clusterKey][] = $doc;
        }

        foreach ($clusters as $clusterDocs) {
            $clusterColl = collect($clusterDocs);

            $reserveDoc = $clusterColl->first(fn ($d) => $d->isReserve() || str_ends_with($cleanStr($d->document_number), 'R'));
            $piDoc = $clusterColl->first(fn ($d) => $d->isProformaInvoice())
                ?: $clusterColl->first(fn ($d) => ! $d->isReserve() && ! str_ends_with($cleanStr($d->document_number), 'R') && (str_starts_with($cleanStr($d->document_number), 'E') || str_starts_with($cleanStr($d->document_number), 'EL')));
            $invDoc = $clusterColl->first(fn ($d) => $d->isCommercialInvoice())
                ?: $clusterColl->first(fn ($d) => str_starts_with($cleanStr($d->document_number), 'N'));
            $pklDoc = $clusterColl->first(fn ($d) => $d->isPackingList())
                ?: $clusterColl->first(fn ($d) => str_starts_with($cleanStr($d->document_number), 'W'));

            $matchedDocs = array_filter([$piDoc, $invDoc, $pklDoc, $reserveDoc]);
            $otherDocs = $clusterColl->reject(fn ($d) => in_array($d, $matchedDocs, true));

            $reservation = null;
            foreach ($clusterColl as $cd) {
                $reservation = $allReservations->firstWhere('document_id', $cd->id)
                    ?: $allReservations->first(fn ($r) => $cleanStr($r->reserve_document_number) === $cleanStr($cd->document_number));
                if ($reservation) {
                    break;
                }
            }

            $primaryDoc = $piDoc ?: ($invDoc ?: ($pklDoc ?: ($reserveDoc ?: $clusterColl->first())));

            $consolidatedOrders->push($this->formatConsolidatedRecord(
                shipmentOrder: null,
                piDoc: $piDoc,
                invDoc: $invDoc,
                pklDoc: $pklDoc,
                reserveDoc: $reserveDoc,
                reservation: $reservation,
                otherDocs: $otherDocs,
                fallbackDoc: $primaryDoc
            ));
        }

        // Apply filters
        if (! empty($filters['start_date'])) {
            $consolidatedOrders = $consolidatedOrders->filter(function ($ord) use ($filters) {
                return $ord->order_date && $ord->order_date->format('Y-m-d') >= $filters['start_date'];
            });
        }
        if (! empty($filters['end_date'])) {
            $consolidatedOrders = $consolidatedOrders->filter(function ($ord) use ($filters) {
                return $ord->order_date && $ord->order_date->format('Y-m-d') <= $filters['end_date'];
            });
        }

        if (! empty($filters['document_type']) && $filters['document_type'] !== 'all') {
            $consolidatedOrders = $consolidatedOrders->filter(function ($ord) use ($filters) {
                return in_array($filters['document_type'], $ord->document_types);
            });
        }

        if (! empty($filters['carrier_method']) && $filters['carrier_method'] !== 'all') {
            $normFilter = strtolower(str_replace(['_', ' ', '/'], '', $filters['carrier_method']));
            $consolidatedOrders = $consolidatedOrders->filter(function ($ord) use ($normFilter) {
                $normOrdCarrier = strtolower(str_replace(['_', ' ', '/'], '', $ord->carrier_method));
                if (str_contains($normOrdCarrier, $normFilter) || str_contains($normFilter, $normOrdCarrier)) {
                    return true;
                }

                return $ord->all_shipment_costs->contains(function ($sc) use ($normFilter) {
                    $m = strtolower(str_replace(['_', ' ', '/'], '', $sc->method));
                    $ml = strtolower(str_replace(['_', ' ', '/'], '', $sc->method_label ?? ''));

                    return str_contains($m, $normFilter) || str_contains($ml, $normFilter);
                });
            });
        }

        return $consolidatedOrders->sort(function ($a, $b) {
            $dateA = $a->order_date ? $a->order_date->timestamp : 0;
            $dateB = $b->order_date ? $b->order_date->timestamp : 0;
            if ($dateA !== $dateB) {
                return $dateB <=> $dateA;
            }

            return strcmp($b->order_number, $a->order_number);
        })->values();
    }

    /**
     * Format a single consolidated order record from its linked parts.
     */
    protected function formatConsolidatedRecord(
        ?ShipmentOrder $shipmentOrder,
        ?Document $piDoc,
        ?Document $invDoc,
        ?Document $pklDoc,
        ?Document $reserveDoc,
        ?OrderReservation $reservation,
        Collection $otherDocs,
        ?Document $fallbackDoc
    ): object {
        $primaryDoc = $piDoc ?: ($invDoc ?: ($pklDoc ?: ($reserveDoc ?: $fallbackDoc)));

        $orderNumber = $shipmentOrder?->order_number
            ?: ($piDoc?->document_number ?: ($invDoc?->document_number ?: ($pklDoc?->document_number ?: ($primaryDoc?->document_number ?: 'ORDER'))));

        $orderDate = $piDoc?->document_date
            ?: ($invDoc?->document_date ?: ($pklDoc?->document_date ?: ($primaryDoc?->document_date ?: ($shipmentOrder?->customer_po_date ?: null))));

        $companyName = $shipmentOrder?->company_name
            ?: ($primaryDoc?->company_name ?: ($reservation?->company_name ?: 'N/A'));
        $country = $shipmentOrder?->country
            ?: ($primaryDoc?->country ?: ($reservation?->country ?: ''));

        $piNumber = $shipmentOrder?->proforma_invoice_no ?: ($piDoc?->document_number ?: '-');
        $invoiceNumber = $shipmentOrder?->linked_invoice_no ?: ($invDoc?->document_number ?: '-');
        $pklNumber = $shipmentOrder?->linked_packing_list_no ?: ($pklDoc?->document_number ?: '-');

        $otherRefs = [];
        if (! empty($shipmentOrder?->customer_po_number)) {
            $otherRefs[] = 'PO: '.$shipmentOrder->customer_po_number;
        }
        if ($reserveDoc) {
            $otherRefs[] = 'Reserve: '.$reserveDoc->document_number;
        } elseif ($reservation) {
            $resNum = $reservation->reserve_document_number ?: $reservation->reservation_number;
            if ($resNum) {
                $otherRefs[] = 'Reserve: '.$resNum;
            }
        }
        if (! empty($shipmentOrder?->document_reference)) {
            $otherRefs[] = 'Ref: '.$shipmentOrder->document_reference;
        }
        foreach ($otherDocs as $od) {
            $otherRefs[] = $od->formatted_type.': '.$od->document_number;
        }
        $otherDocsRefs = ! empty($otherRefs) ? implode(', ', array_unique($otherRefs)) : '-';

        // Weight resolution priority: PKL -> Reserve -> Invoice -> PI -> Fallback
        $netWeight = 0.0;
        $grossWeight = 0.0;
        $weightSource = '-';
        $pkgCount = 0;
        $totCbm = 0.0;

        if ($pklDoc && ((float) $pklDoc->total_net_weight > 0 || (float) $pklDoc->total_gross_weight > 0)) {
            $netWeight = (float) $pklDoc->total_net_weight;
            $grossWeight = (float) $pklDoc->total_gross_weight;
            $weightSource = 'PKL';
            $pkgCount = (int) $pklDoc->packages->sum('quantity');
            $totCbm = (float) $pklDoc->packages->sum('cbm');
        } elseif ($reserveDoc && (float) $reserveDoc->total_net_weight > 0) {
            $netWeight = (float) $reserveDoc->total_net_weight;
            $grossWeight = (float) ($reserveDoc->total_gross_weight > 0 ? $reserveDoc->total_gross_weight : $reserveDoc->total_net_weight);
            $weightSource = 'Reserve';
            $pkgCount = (int) $reserveDoc->packages->sum('quantity');
            $totCbm = (float) $reserveDoc->packages->sum('cbm');
        } elseif ($reservation && (float) $reservation->total_requested_qty > 0) {
            $netWeight = (float) $reservation->total_requested_qty;
            $grossWeight = (float) $reservation->total_requested_qty;
            $weightSource = 'Reserve';
        } elseif ($invDoc && ((float) $invDoc->total_net_weight > 0 || (float) $invDoc->total_gross_weight > 0)) {
            $netWeight = (float) $invDoc->total_net_weight;
            $grossWeight = (float) $invDoc->total_gross_weight;
            $weightSource = 'Invoice';
            $pkgCount = (int) $invDoc->packages->sum('quantity');
            $totCbm = (float) $invDoc->packages->sum('cbm');
        } elseif ($piDoc && ((float) $piDoc->total_net_weight > 0 || (float) $piDoc->total_gross_weight > 0)) {
            $netWeight = (float) $piDoc->total_net_weight;
            $grossWeight = (float) $piDoc->total_gross_weight;
            $weightSource = 'PI';
            $pkgCount = (int) $piDoc->packages->sum('quantity');
            $totCbm = (float) $piDoc->packages->sum('cbm');
        } elseif ($primaryDoc && ((float) $primaryDoc->total_net_weight > 0 || (float) $primaryDoc->total_gross_weight > 0)) {
            $netWeight = (float) $primaryDoc->total_net_weight;
            $grossWeight = (float) $primaryDoc->total_gross_weight;
            $weightSource = $primaryDoc->formatted_type;
            $pkgCount = (int) $primaryDoc->packages->sum('quantity');
            $totCbm = (float) $primaryDoc->packages->sum('cbm');
        }

        if ($pkgCount === 0) {
            foreach ([$pklDoc, $invDoc, $piDoc, $primaryDoc] as $d) {
                if ($d && $d->packages->isNotEmpty()) {
                    $pkgCount = (int) $d->packages->sum('quantity');
                    $totCbm = (float) $d->packages->sum('cbm');
                    break;
                }
            }
        }

        // Shipping costs resolution
        $allShipmentCosts = collect();
        foreach (array_filter([$pklDoc, $invDoc, $piDoc, $primaryDoc, $reserveDoc]) as $d) {
            $allShipmentCosts = $allShipmentCosts->merge($d->shipmentCosts);
        }

        $matchedCost = null;
        if (! empty($shipmentOrder?->carrier_method)) {
            $normSoMethod = strtolower(str_replace(['_', ' ', '/'], '', $shipmentOrder->carrier_method));
            $matchedCost = $allShipmentCosts->first(function ($sc) use ($normSoMethod) {
                $normMethod = strtolower(str_replace(['_', ' ', '/'], '', $sc->method));
                $normLabel = strtolower(str_replace(['_', ' ', '/'], '', $sc->method_label ?? ''));

                return $normMethod === $normSoMethod || $normLabel === $normSoMethod || str_contains($normSoMethod, $normMethod) || str_contains($normMethod, $normSoMethod);
            });
        }

        if (! $matchedCost) {
            $matchedCost = $allShipmentCosts->first(fn ($sc) => (float) $sc->given_amount > 0)
                ?: $allShipmentCosts->first(fn ($sc) => (float) $sc->system_amount > 0)
                ?: $allShipmentCosts->first();
        }

        if ($matchedCost) {
            $carrierMethod = $matchedCost->method_label ?? $matchedCost->method;
            $ratePerKg = (float) $matchedCost->rate_per_kg;
            $shippingCost = (float) ($matchedCost->given_amount ?: $matchedCost->system_amount);
        } elseif (! empty($shipmentOrder?->carrier_method)) {
            $carrierMethod = $shipmentOrder->carrier_method;
            $ratePerKg = 0.0;
            $shippingCost = 0.0;
        } else {
            $carrierMethod = '-';
            $ratePerKg = 0.0;
            $shippingCost = 0.0;
        }

        $trackingAwb = $shipmentOrder?->tracking_awb_no ?: '-';

        $currency = $invDoc?->currency ?: ($piDoc?->currency ?: ($primaryDoc?->currency ?: ($shipmentOrder?->currency ?: 'USD')));
        $orderTotal = (float) ($invDoc?->final_total ?: ($piDoc?->final_total ?: ($primaryDoc?->final_total ?: 0)));

        $allDocsInCluster = array_filter([$piDoc, $invDoc, $pklDoc, $reserveDoc, $primaryDoc]);
        $documentTypes = collect($allDocsInCluster)->pluck('document_type')->unique()->values()->all();

        return (object) [
            'order_number' => $orderNumber,
            'order_date' => $orderDate,
            'formatted_date' => $orderDate ? $orderDate->format('Y-m-d') : '-',
            'company_name' => $companyName,
            'country' => $country,
            'pi_number' => $piNumber,
            'invoice_number' => $invoiceNumber,
            'pkl_number' => $pklNumber,
            'other_docs_refs' => $otherDocsRefs,
            'net_weight' => $netWeight,
            'gross_weight' => $grossWeight,
            'weight_source' => $weightSource,
            'packages_count' => $pkgCount,
            'total_cbm' => $totCbm,
            'carrier_method' => $carrierMethod,
            'tracking_awb' => $trackingAwb,
            'rate_per_kg' => $ratePerKg,
            'shipping_cost' => $shippingCost,
            'currency' => $currency,
            'order_total' => $orderTotal,
            'document_types' => $documentTypes,
            'all_shipment_costs' => $allShipmentCosts,
        ];
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

        $items = $itemsQuery->get()->sort(function ($a, $b) {
            $dateA = $a->reservation?->reservation_date?->timestamp ?? 0;
            $dateB = $b->reservation?->reservation_date?->timestamp ?? 0;
            if ($dateA !== $dateB) {
                return $dateA <=> $dateB;
            }
            $resIdA = $a->order_reservation_id ?? 0;
            $resIdB = $b->order_reservation_id ?? 0;
            if ($resIdA !== $resIdB) {
                return $resIdA <=> $resIdB;
            }

            return ($a->id ?? 0) <=> ($b->id ?? 0);
        })->values();

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

        return $this->generateMasterShortageExcel($grouped, $items, $filename, $filters);
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
     * Exactly 1 row per order with combined PI, Invoice, PKL, Other Docs, Weights, and Shipping Costs.
     */
    protected function generateFreightWeightsExcel(Collection $orders, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Freight & Weights Log');
        $sheet->setShowGridLines(true);

        // Headers
        $headers = [
            'A1' => 'Order #',
            'B1' => 'Date',
            'C1' => 'Client / Company',
            'D1' => 'Country',
            'E1' => 'PI No.',
            'F1' => 'Invoice No.',
            'G1' => 'PKL No.',
            'H1' => 'Other Docs & Refs',
            'I1' => 'Net Weight (KG)',
            'J1' => 'Gross Weight (KG)',
            'K1' => 'Weight Source',
            'L1' => 'Packages',
            'M1' => 'Total CBM',
            'N1' => 'Carrier Method',
            'O1' => 'Tracking / AWB #',
            'P1' => 'Rate / KG',
            'Q1' => 'Shipping Cost',
            'R1' => 'Currency',
            'S1' => 'Order Total',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $this->applyHeaderStyle($sheet, 'A1:S1');

        $row = 2;
        foreach ($orders as $ord) {
            $sheet->setCellValue("A{$row}", $ord->order_number);
            $sheet->setCellValue("B{$row}", $ord->formatted_date !== '-' ? $ord->formatted_date : '');
            $sheet->setCellValue("C{$row}", $ord->company_name);
            $sheet->setCellValue("D{$row}", $ord->country);
            $sheet->setCellValue("E{$row}", $ord->pi_number);
            $sheet->setCellValue("F{$row}", $ord->invoice_number);
            $sheet->setCellValue("G{$row}", $ord->pkl_number);
            $sheet->setCellValue("H{$row}", $ord->other_docs_refs);
            $sheet->setCellValue("I{$row}", (float) $ord->net_weight);
            $sheet->setCellValue("J{$row}", (float) $ord->gross_weight);
            $sheet->setCellValue("K{$row}", $ord->weight_source);
            $sheet->setCellValue("L{$row}", (int) $ord->packages_count);
            $sheet->setCellValue("M{$row}", (float) $ord->total_cbm);
            $sheet->setCellValue("N{$row}", $ord->carrier_method);
            $sheet->setCellValue("O{$row}", $ord->tracking_awb);
            $sheet->setCellValue("P{$row}", (float) $ord->rate_per_kg);
            $sheet->setCellValue("Q{$row}", (float) $ord->shipping_cost);
            $sheet->setCellValue("R{$row}", $ord->currency);
            $sheet->setCellValue("S{$row}", (float) $ord->order_total);
            $row++;
        }

        // Summary totals row if orders exist
        if ($orders->isNotEmpty()) {
            $lastDataRow = $row - 1;
            $sheet->setCellValue("A{$row}", 'TOTALS');
            $sheet->setCellValue("I{$row}", "=SUM(I2:I{$lastDataRow})");
            $sheet->setCellValue("J{$row}", "=SUM(J2:J{$lastDataRow})");
            $sheet->setCellValue("L{$row}", "=SUM(L2:L{$lastDataRow})");
            $sheet->setCellValue("M{$row}", "=SUM(M2:M{$lastDataRow})");
            $sheet->setCellValue("Q{$row}", "=SUM(Q2:Q{$lastDataRow})");
            $sheet->setCellValue("S{$row}", "=SUM(S2:S{$lastDataRow})");

            $sheet->getStyle("A{$row}:S{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '0F172A']],
                ],
            ]);
            $row++;
        }

        // Format numerical columns
        $maxRow = max($row, 2);
        $sheet->getStyle("I2:J{$maxRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("L2:L{$maxRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("M2:M{$maxRow}")->getNumberFormat()->setFormatCode('#,##0.000');
        $sheet->getStyle("P2:Q{$maxRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("S2:S{$maxRow}")->getNumberFormat()->setFormatCode('#,##0.00');

        $this->autoFitColumns($sheet, range('A', 'S'));

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
     * Generate Consolidated Master Shortage Excel Workbook (.xlsx) matching client template.
     */
    protected function generateMasterShortageExcel(Collection $grouped, Collection $rawItems, string $filename, array $filters = []): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;

        // =========================================================================
        // SHEET 1: SHORT PARTS LIST (Grouped by Order Reservation with Separator Rows)
        // =========================================================================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Short Parts List');
        $sheet1->setShowGridLines(true);

        $year = ! empty($filters['start_date']) ? date('Y', strtotime($filters['start_date'])) : date('Y');
        $titleText = "SHORT PARTS {$year}";

        // Row 1: Title Banner
        $sheet1->setCellValue('A1', $titleText);
        $sheet1->mergeCells('A1:G1');
        $sheet1->getRowDimension(1)->setRowHeight(28);
        $sheet1->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 13,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4474A0'], // Steel Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Row 2: Header Columns
        $headers = [
            'A2' => 'RESERVED DATE',
            'B2' => 'PROFORMA / RESERVE NO.',
            'C2' => 'COMPANY NAME',
            'D2' => 'PART NO.',
            'E2' => 'QTY',
            'F2' => 'SUPPLIER / INVOICE NO.',
            'G2' => 'REMARKS',
        ];

        foreach ($headers as $cell => $text) {
            $sheet1->setCellValue($cell, $text);
        }
        $sheet1->getRowDimension(2)->setRowHeight(24);
        $sheet1->getStyle('A2:G2')->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFDA66'], // Warm Gold / Yellow
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $thinGridBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0'],
                ],
            ],
        ];

        $groupedByReservation = $rawItems->groupBy('order_reservation_id');
        $groupCount = $groupedByReservation->count();
        $groupIndex = 0;
        $row = 3;

        foreach ($groupedByReservation as $reservationId => $resItems) {
            $groupIndex++;
            $firstItem = $resItems->first();
            $res = $firstItem->reservation;

            $dateStr = $res?->reservation_date ? $res->reservation_date->format('n/j/y') : '';
            $docNo = $res?->reserve_document_number ?: ($res?->reservation_number ?: '');
            $company = $res?->company_name ?: '';

            foreach ($resItems as $itemIndex => $it) {
                $sheet1->getRowDimension($row)->setRowHeight(20);

                if ($itemIndex === 0) {
                    $sheet1->setCellValue("A{$row}", $dateStr);
                    $sheet1->setCellValue("B{$row}", $docNo);
                    $sheet1->setCellValue("C{$row}", $company);
                } else {
                    $sheet1->setCellValue("A{$row}", '');
                    $sheet1->setCellValue("B{$row}", '');
                    $sheet1->setCellValue("C{$row}", '');
                }

                $qty = (float) ($it->short_qty > 0 ? $it->short_qty : $it->requested_qty);
                $supplierInvoice = $it->supplier_invoice_no ?: '';
                $remarks = $it->remarks ?: ($it->shortage_reason ?: '');

                $sheet1->setCellValue("D{$row}", $it->item_code);
                $sheet1->setCellValue("E{$row}", $qty);
                $sheet1->setCellValue("F{$row}", $supplierInvoice);
                $sheet1->setCellValue("G{$row}", $remarks);

                // Cell Alignments
                $sheet1->getStyle("A{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet1->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

                // Number formatting for Qty
                $sheet1->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.##');

                // Borders
                $sheet1->getStyle("A{$row}:G{$row}")->applyFromArray($thinGridBorder);

                $row++;
            }

            // Separator block between distinct orders
            if ($groupIndex < $groupCount) {
                $sheet1->getRowDimension($row)->setRowHeight(14);
                $sheet1->getStyle("A{$row}:G{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'B15E26'], // Solid Copper / Rust bar
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '8C4318'],
                        ],
                    ],
                ]);
                $row++;
            }
        }

        if ($rawItems->isEmpty()) {
            $sheet1->getRowDimension(3)->setRowHeight(25);
            $sheet1->setCellValue('A3', 'Zero shortages recorded across active reservations.');
            $sheet1->mergeCells('A3:G3');
            $sheet1->getStyle('A3:G3')->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '059669'], 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => $thinGridBorder,
            ]);
        }

        $sheet1->getColumnDimension('A')->setWidth(16);
        $sheet1->getColumnDimension('B')->setWidth(22);
        $sheet1->getColumnDimension('C')->setWidth(36);
        $sheet1->getColumnDimension('D')->setWidth(18);
        $sheet1->getColumnDimension('E')->setWidth(12);
        $sheet1->getColumnDimension('F')->setWidth(26);
        $sheet1->getColumnDimension('G')->setWidth(26);

        // =========================================================================
        // SHEET 2: CONSOLIDATED SUMMARY (Item Code Aggregates)
        // =========================================================================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Consolidated Summary');

        $headers2 = [
            'A1' => 'Item Code',
            'B1' => 'Description',
            'C1' => 'Total Short Qty',
            'D1' => 'Total Req Qty',
            'E1' => 'Total Avail Qty',
            'F1' => 'Affected Orders Count',
            'G1' => 'Affected Reserve Numbers',
            'H1' => 'Clients',
            'I1' => 'Bin Locations',
            'J1' => 'Shortage Reasons',
        ];

        foreach ($headers2 as $cell => $text) {
            $sheet2->setCellValue($cell, $text);
        }
        $this->applyHeaderStyle($sheet2, 'A1:J1');

        $r2 = 2;
        foreach ($grouped as $g) {
            $sheet2->setCellValue("A{$r2}", $g->item_code);
            $sheet2->setCellValue("B{$r2}", $g->description ?: '-');
            $sheet2->setCellValue("C{$r2}", (float) $g->total_short);
            $sheet2->setCellValue("D{$r2}", (float) $g->total_requested);
            $sheet2->setCellValue("E{$r2}", (float) $g->total_available);
            $sheet2->setCellValue("F{$r2}", (int) $g->orders_count);
            $sheet2->setCellValue("G{$r2}", $g->reservations);
            $sheet2->setCellValue("H{$r2}", $g->clients);
            $sheet2->setCellValue("I{$r2}", $g->bins);
            $sheet2->setCellValue("J{$r2}", $g->reasons);
            $r2++;
        }
        $this->autoFitColumns($sheet2, range('A', 'J'));

        $spreadsheet->setActiveSheetIndex(0);

        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    /**
     * Generate Individual Reservation Shortage Excel Workbook (.xlsx) matching template.
     */
    protected function generateReservationShortageExcel(OrderReservation $reservation, Collection $shortItems, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Short Parts');
        $sheet->setShowGridLines(true);

        // Row 1: Title Banner
        $docNo = $reservation->reserve_document_number ?: ($reservation->reservation_number ?: 'RESERVATION');
        $sheet->setCellValue('A1', "SHORT PARTS - {$docNo}");
        $sheet->mergeCells('A1:G1');
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 13,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4474A0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Row 2: Headers
        $headers = [
            'A2' => 'RESERVED DATE',
            'B2' => 'PROFORMA / RESERVE NO.',
            'C2' => 'COMPANY NAME',
            'D2' => 'PART NO.',
            'E2' => 'QTY',
            'F2' => 'SUPPLIER / INVOICE NO.',
            'G2' => 'REMARKS',
        ];
        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFDA66'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $dateStr = $reservation->reservation_date ? $reservation->reservation_date->format('n/j/y') : '';
        $company = $reservation->company_name ?: '';

        $row = 3;
        $thinGridBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0'],
                ],
            ],
        ];

        foreach ($shortItems as $itemIndex => $it) {
            $sheet->getRowDimension($row)->setRowHeight(20);

            if ($itemIndex === 0) {
                $sheet->setCellValue("A{$row}", $dateStr);
                $sheet->setCellValue("B{$row}", $docNo);
                $sheet->setCellValue("C{$row}", $company);
            } else {
                $sheet->setCellValue("A{$row}", '');
                $sheet->setCellValue("B{$row}", '');
                $sheet->setCellValue("C{$row}", '');
            }

            $qty = (float) ($it->short_qty > 0 ? $it->short_qty : $it->requested_qty);
            $sheet->setCellValue("D{$row}", $it->item_code);
            $sheet->setCellValue("E{$row}", $qty);
            $sheet->setCellValue("F{$row}", $it->supplier_invoice_no ?: '');
            $sheet->setCellValue("G{$row}", $it->remarks ?: ($it->shortage_reason ?: ''));

            $sheet->getStyle("A{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.##');
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($thinGridBorder);

            $row++;
        }

        if ($shortItems->isEmpty()) {
            $sheet->getRowDimension(3)->setRowHeight(25);
            $sheet->setCellValue('A3', 'Zero shortages recorded for this reservation.');
            $sheet->mergeCells('A3:G3');
            $sheet->getStyle('A3:G3')->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '059669'], 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => $thinGridBorder,
            ]);
        }

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(36);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(26);
        $sheet->getColumnDimension('G')->setWidth(26);

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
