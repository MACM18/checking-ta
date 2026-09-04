<?php

namespace App\Services;

use App\Models\Document;
use App\Models\OrderReservation;
use App\Models\OrderReservationItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderReservationService
{
    /**
     * Create or synchronize an OrderReservation from a Reserve Document.
     */
    public function syncFromDocument(Document $document, ?User $user = null): OrderReservation
    {
        return DB::transaction(function () use ($document, $user) {
            $reservation = OrderReservation::firstOrNew(['document_id' => $document->id]);

            $isNew = ! $reservation->exists;

            $reservation->reservation_number = 'RES-'.$document->document_number;
            $reservation->reserve_document_number = $document->document_number;
            $reservation->company_name = $document->company_name;
            $reservation->country = $document->country;
            $reservation->reservation_date = $document->document_date;
            $reservation->is_legacy_record = false;

            if ($isNew) {
                $reservation->status = OrderReservation::STATUS_PENDING_CHECK;
                $reservation->created_by = $user?->id ?? $document->created_by;
            }
            $reservation->updated_by = $user?->id ?? $document->updated_by;
            $reservation->save();

            // Sync items
            $document->loadMissing('items');
            $existingItemIds = [];

            foreach ($document->items as $docItem) {
                $item = OrderReservationItem::firstOrNew([
                    'order_reservation_id' => $reservation->id,
                    'document_item_id' => $docItem->id,
                ]);

                $isItemNew = ! $item->exists;
                $item->item_code = $docItem->item_code;
                $item->description = $docItem->description;
                $item->requested_qty = $docItem->unit_amount;
                $item->sort_order = $docItem->sort_order ?? 0;

                if ($isItemNew) {
                    $item->available_qty = 0;
                    $item->short_qty = 0;
                    $item->status = OrderReservationItem::STATUS_PENDING;
                }

                $item->save();
                $existingItemIds[] = $item->id;
            }

            $reservation->recalculateTotals();

            return $reservation->fresh(['items', 'document', 'confirmedBy']);
        });
    }

    /**
     * Create a standalone reservation record for an old / external R document.
     */
    public function createLegacyReservation(array $data, User $user): OrderReservation
    {
        return DB::transaction(function () use ($data, $user) {
            $reserveDocNo = trim($data['reserve_document_number']);

            $reservation = OrderReservation::create([
                'document_id' => null,
                'reservation_number' => 'RES-'.$reserveDocNo,
                'reserve_document_number' => $reserveDocNo,
                'company_name' => $data['company_name'] ?? null,
                'country' => $data['country'] ?? null,
                'reservation_date' => $data['reservation_date'] ?? now()->toDateString(),
                'status' => OrderReservation::STATUS_PENDING_CHECK,
                'warehouse_location' => $data['warehouse_location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_legacy_record' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // If initial items were provided
            if (! empty($data['items']) && is_array($data['items'])) {
                $sort = 0;
                foreach ($data['items'] as $itemData) {
                    if (empty($itemData['item_code'])) {
                        continue;
                    }

                    $reqQty = (float) ($itemData['requested_qty'] ?? 1);
                    $availQty = (float) ($itemData['available_qty'] ?? 0);
                    $shortQty = max(0, $reqQty - $availQty);

                    $status = OrderReservationItem::STATUS_PENDING;
                    if ($availQty >= $reqQty && $reqQty > 0) {
                        $status = OrderReservationItem::STATUS_AVAILABLE;
                    } elseif ($shortQty > 0) {
                        $status = $availQty > 0 ? OrderReservationItem::STATUS_SHORT : OrderReservationItem::STATUS_MISSING;
                    }

                    $reservation->items()->create([
                        'item_code' => trim($itemData['item_code']),
                        'description' => $itemData['description'] ?? null,
                        'requested_qty' => $reqQty,
                        'available_qty' => $availQty,
                        'short_qty' => $shortQty,
                        'bin_location' => $itemData['bin_location'] ?? null,
                        'status' => $status,
                        'shortage_reason' => $itemData['shortage_reason'] ?? null,
                        'sort_order' => $sort++,
                    ]);
                }
            }

            $reservation->recalculateTotals();

            return $reservation->fresh(['items']);
        });
    }

    /**
     * Mark all items in the reservation 100% available in warehouse.
     */
    public function confirmAllAvailable(OrderReservation $reservation, User $user, ?string $notes = null, ?string $location = null): OrderReservation
    {
        return DB::transaction(function () use ($reservation, $user, $notes, $location) {
            foreach ($reservation->items as $item) {
                $item->available_qty = $item->requested_qty;
                $item->short_qty = 0;
                $item->status = OrderReservationItem::STATUS_AVAILABLE;
                $item->shortage_reason = null;
                $item->save();
            }

            $reservation->warehouse_confirmed_at = now();
            $reservation->warehouse_confirmed_by = $user->id;
            if ($location) {
                $reservation->warehouse_location = $location;
            }
            if ($notes) {
                $reservation->warehouse_notes = $notes;
            }
            $reservation->status = OrderReservation::STATUS_ALL_AVAILABLE;
            $reservation->updated_by = $user->id;

            $reservation->recalculateTotals();

            return $reservation->fresh(['items', 'confirmedBy']);
        });
    }

    /**
     * Batch update reservation item quantities and record shortages / missing parts.
     */
    public function updateItemQuantities(OrderReservation $reservation, array $itemsData, User $user, ?string $notes = null, ?string $location = null): OrderReservation
    {
        return DB::transaction(function () use ($reservation, $itemsData, $user, $notes, $location) {
            foreach ($itemsData as $itemId => $data) {
                $item = $reservation->items()->find($itemId);
                if (! $item) {
                    continue;
                }

                $reqQty = isset($data['requested_qty']) ? (float) $data['requested_qty'] : (float) $item->requested_qty;
                $availQty = (float) ($data['available_qty'] ?? 0);
                $shortQty = max(0, $reqQty - $availQty);

                if ($availQty >= $reqQty && $reqQty > 0) {
                    $status = OrderReservationItem::STATUS_AVAILABLE;
                    $shortageReason = null;
                } elseif ($availQty > 0 && $shortQty > 0) {
                    $status = OrderReservationItem::STATUS_SHORT;
                    $shortageReason = $data['shortage_reason'] ?? $item->shortage_reason;
                } elseif ($availQty == 0 && $reqQty > 0 && isset($data['is_checked'])) {
                    $status = OrderReservationItem::STATUS_MISSING;
                    $shortageReason = $data['shortage_reason'] ?? $item->shortage_reason ?? 'Nil stock in warehouse';
                } else {
                    $status = OrderReservationItem::STATUS_PENDING;
                    $shortageReason = $data['shortage_reason'] ?? $item->shortage_reason;
                }

                $item->requested_qty = $reqQty;
                $item->available_qty = $availQty;
                $item->short_qty = $shortQty;
                $item->bin_location = $data['bin_location'] ?? $item->bin_location;
                $item->status = $status;
                $item->shortage_reason = $shortageReason;
                $item->save();
            }

            if ($location) {
                $reservation->warehouse_location = $location;
            }
            if ($notes) {
                $reservation->warehouse_notes = $notes;
            }
            $reservation->updated_by = $user->id;

            $reservation->recalculateTotals();

            return $reservation->fresh(['items', 'confirmedBy']);
        });
    }

    /**
     * Add a custom missing item or shortage record to this reservation.
     */
    public function addShortItem(OrderReservation $reservation, array $data, User $user): OrderReservationItem
    {
        return DB::transaction(function () use ($reservation, $data, $user) {
            $reqQty = (float) ($data['requested_qty'] ?? 1);
            $availQty = (float) ($data['available_qty'] ?? 0);
            $shortQty = max(0, $reqQty - $availQty);

            $status = $availQty >= $reqQty ? OrderReservationItem::STATUS_AVAILABLE : ($availQty > 0 ? OrderReservationItem::STATUS_SHORT : OrderReservationItem::STATUS_MISSING);

            $maxSort = $reservation->items()->max('sort_order') ?? 0;

            $item = $reservation->items()->create([
                'document_item_id' => null,
                'item_code' => trim($data['item_code']),
                'description' => $data['description'] ?? null,
                'requested_qty' => $reqQty,
                'available_qty' => $availQty,
                'short_qty' => $shortQty,
                'bin_location' => $data['bin_location'] ?? null,
                'status' => $status,
                'shortage_reason' => $data['shortage_reason'] ?? 'Shortage recorded',
                'sort_order' => $maxSort + 1,
            ]);

            $reservation->updated_by = $user->id;
            $reservation->recalculateTotals();

            return $item;
        });
    }
}
