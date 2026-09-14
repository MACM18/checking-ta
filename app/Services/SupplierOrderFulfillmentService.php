<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;

class SupplierOrderFulfillmentService
{
    /**
     * Get detailed fulfillment and reconciliation summary for a single Supplier Order (B-Order).
     */
    public function getOrderSheetSummary(Document $supplierOrder): array
    {
        if (! $supplierOrder->relationLoaded('items')) {
            $supplierOrder->load('items');
        }

        // Fetch all Factory Invoices linked to this Supplier Order
        $factoryInvoices = Document::with(['items'])
            ->where('document_type', Document::TYPE_FACTORY_INVOICE)
            ->where(function ($q) use ($supplierOrder) {
                $q->where('source_document_id', $supplierOrder->id)
                    ->orWhere('source_document_number', $supplierOrder->document_number);
            })
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        // Aggregate received quantities by normalized item code
        $receivedByCode = [];
        $receiptsByCode = [];

        foreach ($factoryInvoices as $fi) {
            foreach ($fi->items as $fiItem) {
                $code = strtoupper(trim($fiItem->item_code ?? ''));
                if (empty($code)) {
                    continue;
                }
                $qty = (float) $fiItem->unit_amount;
                $receivedByCode[$code] = ($receivedByCode[$code] ?? 0.0) + $qty;
                $receiptsByCode[$code][] = [
                    'factory_invoice_id' => $fi->id,
                    'factory_invoice_uuid' => $fi->uuid,
                    'factory_invoice_number' => $fi->document_number,
                    'date' => $fi->document_date ? $fi->document_date->format('Y-m-d') : null,
                    'formatted_date' => $fi->document_date ? $fi->document_date->format('d M Y') : null,
                    'quantity' => $qty,
                ];
            }
        }

        // Reconcile each line item on the Supplier Order
        $itemsSummary = [];
        $totalOrderedQty = 0.0;
        $totalFulfilledPortion = 0.0;
        $totalReceivedActual = 0.0;
        $fulfilledLines = 0;
        $partialLines = 0;
        $pendingLines = 0;

        foreach ($supplierOrder->items as $item) {
            $code = strtoupper(trim($item->item_code ?? ''));
            $orderedQty = (float) $item->unit_amount;
            $receivedQty = (float) ($receivedByCode[$code] ?? 0.0);
            $remainingQty = max(0.0, round($orderedQty - $receivedQty, 3));
            $surplusQty = max(0.0, round($receivedQty - $orderedQty, 3));

            if ($receivedQty >= $orderedQty && $orderedQty > 0) {
                $status = 'fulfilled';
                $fulfilledLines++;
            } elseif ($receivedQty > 0) {
                $status = 'partially_received';
                $partialLines++;
            } else {
                $status = 'pending';
                $pendingLines++;
            }

            $totalOrderedQty += $orderedQty;
            $totalFulfilledPortion += min($receivedQty, $orderedQty);
            $totalReceivedActual += $receivedQty;

            $itemsSummary[] = [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'description' => $item->description,
                'ordered_qty' => $orderedQty,
                'received_qty' => $receivedQty,
                'remaining_qty' => $remainingQty,
                'surplus_qty' => $surplusQty,
                'status' => $status,
                'percentage' => $orderedQty > 0 ? min(100, round(($receivedQty / $orderedQty) * 100)) : 100,
                'receipts' => $receiptsByCode[$code] ?? [],
            ];
        }

        // Identify any unexpected items sent on factory invoices not in the order
        $orderedCodes = $supplierOrder->items->map(fn ($it) => strtoupper(trim($it->item_code ?? '')))->toArray();
        $unexpectedItems = [];
        foreach ($receivedByCode as $code => $qty) {
            if (! in_array($code, $orderedCodes) && $qty > 0) {
                $unexpectedItems[] = [
                    'item_code' => $code,
                    'received_qty' => $qty,
                    'receipts' => $receiptsByCode[$code] ?? [],
                ];
            }
        }

        $totalRemainingQty = max(0.0, round($totalOrderedQty - $totalFulfilledPortion, 3));
        $fulfillmentPct = $totalOrderedQty > 0 ? min(100, round(($totalFulfilledPortion / $totalOrderedQty) * 100, 1)) : 100;

        $overallStatus = 'pending';
        if ($totalFulfilledPortion >= $totalOrderedQty && $totalOrderedQty > 0) {
            $overallStatus = 'completed';
        } elseif ($totalReceivedActual > 0) {
            $overallStatus = 'partially_received';
        }

        $invoicesList = $factoryInvoices->map(function ($fi) {
            return [
                'id' => $fi->id,
                'uuid' => $fi->uuid,
                'document_number' => $fi->document_number,
                'document_date' => $fi->document_date ? $fi->document_date->format('Y-m-d') : null,
                'formatted_date' => $fi->document_date ? $fi->document_date->format('d M Y') : null,
                'company_name' => $fi->company_name,
                'total_qty' => (float) $fi->items->sum('unit_amount'),
                'items_count' => $fi->items->count(),
                'url' => route('documents.show', $fi),
            ];
        });

        return [
            'document' => $supplierOrder,
            'supplier_order' => $supplierOrder,
            'document_id' => $supplierOrder->id,
            'uuid' => $supplierOrder->uuid,
            'document_number' => $supplierOrder->document_number,
            'company_name' => $supplierOrder->company_name,
            'document_date' => $supplierOrder->document_date ? $supplierOrder->document_date->format('Y-m-d') : null,
            'formatted_date' => $supplierOrder->document_date ? $supplierOrder->document_date->format('d M Y') : null,
            'total_items_count' => count($itemsSummary),
            'items_count' => count($itemsSummary),
            'total_ordered_qty' => $totalOrderedQty,
            'total_received_qty' => $totalReceivedActual,
            'total_remaining_qty' => $totalRemainingQty,
            'fulfillment_percentage' => $fulfillmentPct,
            'completion_percentage' => $fulfillmentPct,
            'overall_status' => $overallStatus,
            'fulfilled_lines_count' => $fulfilledLines,
            'partial_lines_count' => $partialLines,
            'pending_lines_count' => $pendingLines,
            'items' => $itemsSummary,
            'unexpected_items' => $unexpectedItems,
            'factory_invoices' => $invoicesList,
            'factory_invoices_count' => $factoryInvoices->count(),
            'url' => route('documents.show', $supplierOrder),
            'sheet_url' => route('supplier-orders.show', $supplierOrder),
        ];
    }

    /**
     * Get all Order Sheets with reconciliation summaries, with optional filtering.
     */
    public function getAllOrderSheets(array $filters = []): Collection
    {
        $query = Document::with(['items'])
            ->where(function ($q) {
                $q->where('document_type', Document::TYPE_SUPPLIER_ORDER)
                    ->orWhere('document_number', 'LIKE', 'B%');
            })
            ->orderByDesc('document_date')
            ->orderByDesc('id');

        if (! empty($filters['start_date'])) {
            $query->whereDate('document_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('document_date', '<=', $filters['end_date']);
        }

        $orders = $query->get();

        $summaries = $orders->map(function ($order) {
            return $this->getOrderSheetSummary($order);
        });

        // Filter by status if requested
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $summaries = $summaries->filter(function ($s) use ($filters) {
                return $s['overall_status'] === $filters['status'];
            });
        }

        // Filter by search query (B-number, supplier, or item code)
        if (! empty($filters['search'])) {
            $search = strtolower(trim($filters['search']));
            $summaries = $summaries->filter(function ($s) use ($search) {
                if (str_contains(strtolower($s['document_number']), $search)) {
                    return true;
                }
                if (str_contains(strtolower($s['company_name']), $search)) {
                    return true;
                }
                // Check items
                foreach ($s['items'] as $it) {
                    if (str_contains(strtolower($it['item_code']), $search) || str_contains(strtolower($it['description']), $search)) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $summaries->values();
    }

    /**
     * Aggregated matrix of all pending items across incomplete order sheets.
     */
    public function getPendingItemsMatrix(): Collection
    {
        $allSheets = $this->getAllOrderSheets(['status' => 'all']);
        $pendingByCode = [];

        foreach ($allSheets as $sheet) {
            foreach ($sheet['items'] as $item) {
                if ($item['remaining_qty'] > 0) {
                    $code = strtoupper(trim($item['item_code']));
                    if (! isset($pendingByCode[$code])) {
                        $pendingByCode[$code] = [
                            'item_code' => $item['item_code'],
                            'description' => $item['description'],
                            'total_ordered_qty' => 0.0,
                            'total_received_qty' => 0.0,
                            'total_remaining_qty' => 0.0,
                            'order_sheets' => [],
                        ];
                    }

                    $pendingByCode[$code]['total_ordered_qty'] += $item['ordered_qty'];
                    $pendingByCode[$code]['total_received_qty'] += $item['received_qty'];
                    $pendingByCode[$code]['total_remaining_qty'] += $item['remaining_qty'];
                    $pendingByCode[$code]['order_sheets'][] = [
                        'document_number' => $sheet['document_number'],
                        'document_id' => $sheet['document_id'],
                        'uuid' => $sheet['uuid'],
                        'company_name' => $sheet['company_name'],
                        'document_date' => $sheet['formatted_date'],
                        'ordered_qty' => $item['ordered_qty'],
                        'received_qty' => $item['received_qty'],
                        'remaining_qty' => $item['remaining_qty'],
                        'url' => $sheet['sheet_url'],
                    ];
                }
            }
        }

        // Sort by total remaining quantity descending
        return collect($pendingByCode)->sortByDesc('total_remaining_qty')->values();
    }
}
