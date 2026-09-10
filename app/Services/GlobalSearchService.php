<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Item;
use App\Models\OrderReservation;
use App\Models\ShipmentOrder;
use App\Models\User;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    /**
     * Perform multi-entity indexed relevance search across Documents, Shipments, Reservations, and Price SKUs.
     */
    public function search(string $rawQuery, User $user, ?string $category = null, int $limit = 8): array
    {
        $startTime = microtime(true);
        $term = trim($rawQuery);

        if (empty($term)) {
            return [
                'query' => '',
                'results' => [],
                'categories' => [
                    'documents' => [],
                    'shipment_orders' => [],
                    'reservations' => [],
                    'items' => [],
                ],
                'counts' => [
                    'all' => 0,
                    'documents' => 0,
                    'shipment_orders' => 0,
                    'reservations' => 0,
                    'items' => 0,
                ],
                'took_ms' => 0,
            ];
        }

        $allResults = collect();
        $categories = [
            'documents' => [],
            'shipment_orders' => [],
            'reservations' => [],
            'items' => [],
        ];

        // 1. Search Documents
        if (! $category || $category === 'all' || $category === 'documents') {
            $docResults = $this->searchDocuments($term, $limit);
            $categories['documents'] = $docResults->values()->all();
            $allResults = $allResults->merge($docResults);
        }

        // 2. Search Shipment Orders
        if ((! $category || $category === 'all' || $category === 'shipment_orders') && $user->canManageShipments()) {
            $shipmentResults = $this->searchShipmentOrders($term, $limit);
            $categories['shipment_orders'] = $shipmentResults->values()->all();
            $allResults = $allResults->merge($shipmentResults);
        }

        // 3. Search Order Reservations
        if ((! $category || $category === 'all' || $category === 'reservations') && $user->canManageReservations()) {
            $reservationResults = $this->searchOrderReservations($term, $limit);
            $categories['reservations'] = $reservationResults->values()->all();
            $allResults = $allResults->merge($reservationResults);
        }

        // 4. Search Price Items / SKUs
        if ((! $category || $category === 'all' || $category === 'items') && $user->canManagePriceTracker()) {
            $itemResults = $this->searchItems($term, $limit);
            $categories['items'] = $itemResults->values()->all();
            $allResults = $allResults->merge($itemResults);
        }

        // Sort overall results by relevance score descending
        $sortedResults = $allResults->sortByDesc('score')->values()->all();

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'query' => $term,
            'results' => $sortedResults,
            'categories' => $categories,
            'counts' => [
                'all' => count($sortedResults),
                'documents' => count($categories['documents']),
                'shipment_orders' => count($categories['shipment_orders']),
                'reservations' => count($categories['reservations']),
                'items' => count($categories['items']),
            ],
            'took_ms' => $executionTime,
        ];
    }

    /**
     * Search Documents with relevance weighting.
     */
    protected function searchDocuments(string $term, int $limit): Collection
    {
        $termUpper = strtoupper($term);

        $documents = Document::query()
            ->select([
                'id',
                'uuid',
                'document_number',
                'document_type',
                'company_name',
                'country',
                'document_date',
                'currency',
                'subtotal',
                'final_total',
                'total_net_weight',
                'total_gross_weight',
                'status',
                'source_document_number',
            ])
            ->where(function ($q) use ($term) {
                $q->where('document_number', 'LIKE', "{$term}%")
                    ->orWhere('document_number', 'LIKE', "%{$term}%")
                    ->orWhere('company_name', 'LIKE', "%{$term}%")
                    ->orWhere('source_document_number', 'LIKE', "%{$term}%")
                    ->orWhere('country', 'LIKE', "%{$term}%")
                    ->orWhere('contact_details', 'LIKE', "%{$term}%")
                    ->orWhereHas('items', function ($itemQ) use ($term) {
                        $itemQ->where('item_code', 'LIKE', "{$term}%")
                            ->orWhere('item_code', 'LIKE', "%{$term}%")
                            ->orWhere('description', 'LIKE', "%{$term}%");
                    });
            })
            ->limit($limit * 2)
            ->get();

        return $documents->map(function (Document $doc) use ($term, $termUpper) {
            $docNumUpper = strtoupper($doc->document_number);
            $score = 40;

            if ($docNumUpper === $termUpper) {
                $score = 100;
            } elseif (str_starts_with($docNumUpper, $termUpper)) {
                $score = 85;
            } elseif (str_contains($docNumUpper, $termUpper)) {
                $score = 75;
            } elseif ($doc->source_document_number && strtoupper($doc->source_document_number) === $termUpper) {
                $score = 72;
            } elseif (stripos($doc->company_name, $term) !== false) {
                $score = 65;
            } elseif ($doc->country && stripos($doc->country, $term) !== false) {
                $score = 55;
            }

            $badgeColor = match ($doc->document_type) {
                Document::TYPE_PROFORMA_INVOICE => 'blue',
                Document::TYPE_INVOICE => 'emerald',
                Document::TYPE_PACKING_LIST => 'amber',
                Document::TYPE_RESERVE => 'purple',
                Document::TYPE_DELIVERY_NOTE => 'teal',
                default => 'indigo',
            };

            $amountOrWeight = $doc->isWeightOnly()
                ? ($doc->total_net_weight ? number_format($doc->total_net_weight, 3).' kg Net' : 'Weight Document')
                : $doc->currency.' '.number_format($doc->final_total, 2);

            return [
                'id' => $doc->id,
                'category' => 'documents',
                'category_label' => 'Document',
                'icon' => '📄',
                'title' => $doc->document_number,
                'subtitle' => $doc->company_name.($doc->country ? ' • '.$doc->country : ''),
                'badge' => $doc->formatted_type,
                'badge_color' => $badgeColor,
                'meta_primary' => $amountOrWeight,
                'meta_secondary' => $doc->document_date ? $doc->document_date->format('M d, Y') : null,
                'status' => $doc->status,
                'url' => route('documents.show', $doc),
                'score' => $score,
            ];
        })->sortByDesc('score')->take($limit);
    }

    /**
     * Search Shipment Orders with tracking and carrier relevance.
     */
    protected function searchShipmentOrders(string $term, int $limit): Collection
    {
        $termUpper = strtoupper($term);

        $shipments = ShipmentOrder::query()
            ->select([
                'id',
                'order_number',
                'company_name',
                'country',
                'customer_po_number',
                'carrier_method',
                'tracking_awb_no',
                'status',
                'shipment_category',
                'dispatch_date',
                'delivery_date',
            ])
            ->where(function ($q) use ($term) {
                $q->where('order_number', 'LIKE', "{$term}%")
                    ->orWhere('order_number', 'LIKE', "%{$term}%")
                    ->orWhere('tracking_awb_no', 'LIKE', "{$term}%")
                    ->orWhere('tracking_awb_no', 'LIKE', "%{$term}%")
                    ->orWhere('customer_po_number', 'LIKE', "%{$term}%")
                    ->orWhere('company_name', 'LIKE', "%{$term}%")
                    ->orWhere('country', 'LIKE', "%{$term}%")
                    ->orWhere('carrier_method', 'LIKE', "%{$term}%");
            })
            ->limit($limit * 2)
            ->get();

        return $shipments->map(function (ShipmentOrder $shipment) use ($term, $termUpper) {
            $orderNumUpper = strtoupper($shipment->order_number);
            $awbUpper = strtoupper($shipment->tracking_awb_no ?? '');
            $score = 40;

            if ($orderNumUpper === $termUpper || ($awbUpper && $awbUpper === $termUpper)) {
                $score = 100;
            } elseif (str_starts_with($orderNumUpper, $termUpper) || ($awbUpper && str_starts_with($awbUpper, $termUpper))) {
                $score = 85;
            } elseif (stripos($shipment->customer_po_number ?? '', $term) !== false) {
                $score = 75;
            } elseif (stripos($shipment->company_name, $term) !== false) {
                $score = 65;
            }

            return [
                'id' => $shipment->id,
                'category' => 'shipment_orders',
                'category_label' => 'Shipment Order',
                'icon' => '🚢',
                'title' => $shipment->order_number,
                'subtitle' => $shipment->company_name.($shipment->country ? ' • '.$shipment->country : ''),
                'badge' => $shipment->carrier_method ?: ($shipment->shipment_category ?: 'Shipment'),
                'badge_color' => 'indigo',
                'meta_primary' => $shipment->tracking_awb_no ? 'AWB: '.$shipment->tracking_awb_no : ($shipment->customer_po_number ? 'PO: '.$shipment->customer_po_number : null),
                'meta_secondary' => $shipment->dispatch_date ? 'Dispatch: '.$shipment->dispatch_date->format('M d') : null,
                'status' => $shipment->status,
                'url' => route('shipment-orders.show', $shipment),
                'score' => $score,
            ];
        })->sortByDesc('score')->take($limit);
    }

    /**
     * Search Order Reservations / Warehouse Audits.
     */
    protected function searchOrderReservations(string $term, int $limit): Collection
    {
        $termUpper = strtoupper($term);

        $reservations = OrderReservation::query()
            ->select([
                'id',
                'reservation_number',
                'reserve_document_number',
                'company_name',
                'country',
                'status',
                'short_items_count',
                'total_short_qty',
                'reservation_date',
            ])
            ->where(function ($q) use ($term) {
                $q->where('reservation_number', 'LIKE', "{$term}%")
                    ->orWhere('reservation_number', 'LIKE', "%{$term}%")
                    ->orWhere('reserve_document_number', 'LIKE', "%{$term}%")
                    ->orWhere('company_name', 'LIKE', "%{$term}%")
                    ->orWhere('country', 'LIKE', "%{$term}%")
                    ->orWhereHas('items', function ($itemQ) use ($term) {
                        $itemQ->where('item_code', 'LIKE', "{$term}%")
                            ->orWhere('description', 'LIKE', "%{$term}%");
                    });
            })
            ->limit($limit * 2)
            ->get();

        return $reservations->map(function (OrderReservation $res) use ($term, $termUpper) {
            $resNumUpper = strtoupper($res->reservation_number);
            $docNumUpper = strtoupper($res->reserve_document_number ?? '');
            $score = 40;

            if ($resNumUpper === $termUpper || ($docNumUpper && $docNumUpper === $termUpper)) {
                $score = 100;
            } elseif (str_starts_with($resNumUpper, $termUpper)) {
                $score = 85;
            } elseif (stripos($res->company_name, $term) !== false) {
                $score = 65;
            }

            $badgeColor = match ($res->status) {
                OrderReservation::STATUS_ALL_AVAILABLE => 'emerald',
                OrderReservation::STATUS_HAS_SHORTAGE => 'rose',
                default => 'amber',
            };

            $badgeLabel = match ($res->status) {
                OrderReservation::STATUS_ALL_AVAILABLE => 'All Stock Available',
                OrderReservation::STATUS_HAS_SHORTAGE => 'Shortage ('.$res->short_items_count.' items)',
                default => 'Audit In Progress',
            };

            return [
                'id' => $res->id,
                'category' => 'reservations',
                'category_label' => 'Order Reservation',
                'icon' => '📦',
                'title' => $res->reservation_number,
                'subtitle' => $res->company_name.($res->reserve_document_number ? ' • Ref: '.$res->reserve_document_number : ''),
                'badge' => $badgeLabel,
                'badge_color' => $badgeColor,
                'meta_primary' => $res->short_items_count > 0 ? number_format($res->total_short_qty).' units short' : '100% physically available',
                'meta_secondary' => $res->reservation_date ? $res->reservation_date->format('M d, Y') : null,
                'status' => $res->status,
                'url' => route('order-reservations.show', $res),
                'score' => $score,
            ];
        })->sortByDesc('score')->take($limit);
    }

    /**
     * Search Price Items / SKUs.
     */
    protected function searchItems(string $term, int $limit): Collection
    {
        $termUpper = strtoupper($term);

        $items = Item::query()
            ->with(['prices' => function ($q) {
                $q->select('id', 'item_id', 'price_list', 'price_label', 'price')->limit(4);
            }])
            ->where(function ($q) use ($term) {
                $q->where('item_code', 'LIKE', "{$term}%")
                    ->orWhere('item_code', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            })
            ->limit($limit * 2)
            ->get();

        return $items->map(function (Item $item) use ($term, $termUpper) {
            $codeUpper = strtoupper($item->item_code);
            $score = 40;

            if ($codeUpper === $termUpper) {
                $score = 100;
            } elseif (str_starts_with($codeUpper, $termUpper)) {
                $score = 85;
            } elseif (str_contains($codeUpper, $termUpper)) {
                $score = 70;
            } elseif (stripos($item->description ?? '', $term) !== false) {
                $score = 60;
            }

            $firstPrice = $item->prices->first();
            $priceSummary = $firstPrice ? ($firstPrice->price_label.': '.number_format($firstPrice->price, 2)) : 'Price Tracked';

            return [
                'id' => $item->id,
                'category' => 'items',
                'category_label' => 'Price SKU',
                'icon' => '🏷️',
                'title' => $item->item_code,
                'subtitle' => $item->description ?: 'No description',
                'badge' => 'Price Item',
                'badge_color' => 'teal',
                'meta_primary' => $priceSummary,
                'meta_secondary' => $item->prices->count().' price tier(s)',
                'status' => 'active',
                'url' => route('price-tracker.index').'?search='.urlencode($item->item_code),
                'score' => $score,
            ];
        })->sortByDesc('score')->take($limit);
    }
}
