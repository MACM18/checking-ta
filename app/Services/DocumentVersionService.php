<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentVersionService
{
    /**
     * Extract full snapshot array representation of a document.
     */
    public function extractDocumentSnapshotData(Document $document): array
    {
        $document->load(['items', 'packages', 'shipmentCosts']);

        return [
            'document' => [
                'document_number' => $document->document_number,
                'document_type' => $document->document_type,
                'company_name' => $document->company_name,
                'country' => $document->country,
                'address' => $document->address,
                'contact_details' => $document->contact_details,
                'document_date' => $document->document_date?->format('Y-m-d'),
                'currency' => $document->currency,
                'price_list' => $document->price_list,
                'price_label' => $document->price_label,
                'total_net_weight' => (float) $document->total_net_weight,
                'total_gross_weight' => (float) $document->total_gross_weight,
                'subtotal' => (float) $document->subtotal,
                'final_total' => (float) $document->final_total,
                'status' => $document->status,
                'notes' => $document->notes,
            ],
            'items' => $document->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'description' => $item->description,
                    'unit_amount' => (float) $item->unit_amount,
                    'unit_price' => (float) $item->unit_price,
                    'total_amount' => (float) $item->total_amount,
                    'sort_order' => $item->sort_order,
                ];
            })->toArray(),
            'packages' => $document->packages->map(function ($pkg) {
                return [
                    'package_type' => $pkg->package_type,
                    'dimension_type' => $pkg->dimension_type,
                    'length_cm' => $pkg->length_cm,
                    'width_cm' => $pkg->width_cm,
                    'height_cm' => $pkg->height_cm,
                    'diameter_cm' => $pkg->diameter_cm,
                    'quantity' => $pkg->quantity,
                    'gross_weight_per_pkg_kg' => (float) $pkg->gross_weight_per_pkg_kg,
                    'total_gross_weight_kg' => (float) $pkg->total_gross_weight_kg,
                    'volumetric_weight_kg' => (float) $pkg->volumetric_weight_kg,
                    'cbm' => (float) $pkg->cbm,
                    'sort_order' => $pkg->sort_order,
                ];
            })->toArray(),
            'shipment_costs' => $document->shipmentCosts->map(function ($cost) {
                return [
                    'method' => $cost->method,
                    'checked_weight' => $cost->checked_weight !== null ? (float) $cost->checked_weight : null,
                    'rate_per_kg' => $cost->rate_per_kg !== null ? (float) $cost->rate_per_kg : null,
                    'chargeable_weight' => $cost->chargeable_weight !== null ? (float) $cost->chargeable_weight : null,
                    'system_amount' => $cost->system_amount !== null ? (float) $cost->system_amount : null,
                    'added_amount' => $cost->added_amount !== null ? (float) $cost->added_amount : null,
                    'given_amount' => $cost->given_amount !== null ? (float) $cost->given_amount : null,
                ];
            })->toArray(),
        ];
    }

    /**
     * Create a snapshot of the current document state.
     */
    public function createSnapshot(Document $document, User $user, string $summary = 'Version snapshot'): DocumentVersion
    {
        $snapshot = $this->extractDocumentSnapshotData($document);

        return DocumentVersion::updateOrCreate(
            [
                'document_id' => $document->id,
                'version_number' => $document->current_version,
            ],
            [
                'snapshot_data' => $snapshot,
                'change_summary' => $summary,
                'created_by' => $user->id,
            ]
        );
    }

    /**
     * Compute a GitHub-style diff between a historical version snapshot and the current document state.
     */
    public function computeDiffWithCurrent(Document $document, DocumentVersion $version): array
    {
        $snap = $version->snapshot_data;
        $oldDoc = $snap['document'] ?? [];
        $oldItems = $snap['items'] ?? [];
        $oldShip = $snap['shipment_costs'] ?? [];

        $currentData = $this->extractDocumentSnapshotData($document);
        $currDoc = $currentData['document'];
        $currItems = $currentData['items'];
        $currShip = $currentData['shipment_costs'];

        // 1. Header fields diff
        $headerFields = [
            'company_name' => 'Company / Recipient',
            'country' => 'Country',
            'address' => 'Address',
            'contact_details' => 'Contact Details',
            'document_date' => 'Document Date',
            'currency' => 'Currency',
            'price_list' => 'Price List',
            'price_label' => 'Price Label',
            'status' => 'Status',
            'notes' => 'Notes',
        ];

        $headerDiffs = [];
        foreach ($headerFields as $field => $label) {
            $oldVal = trim((string) ($oldDoc[$field] ?? ''));
            $currVal = trim((string) ($currDoc[$field] ?? ''));
            if ($oldVal !== $currVal) {
                $headerDiffs[] = [
                    'field' => $field,
                    'label' => $label,
                    'old' => $oldVal !== '' ? $oldVal : '(empty)',
                    'current' => $currVal !== '' ? $currVal : '(empty)',
                ];
            }
        }

        // 2. Financial & Weight Deltas
        $oldFinalTotal = (float) ($oldDoc['final_total'] ?? 0);
        $currFinalTotal = (float) ($currDoc['final_total'] ?? 0);
        $diffFinalTotal = $currFinalTotal - $oldFinalTotal;

        $oldSubtotal = (float) ($oldDoc['subtotal'] ?? 0);
        $currSubtotal = (float) ($currDoc['subtotal'] ?? 0);
        $diffSubtotal = $currSubtotal - $oldSubtotal;

        $oldNetWt = (float) ($oldDoc['total_net_weight'] ?? 0);
        $currNetWt = (float) ($currDoc['total_net_weight'] ?? 0);
        $diffNetWt = $currNetWt - $oldNetWt;

        $oldGrossWt = (float) ($oldDoc['total_gross_weight'] ?? 0);
        $currGrossWt = (float) ($currDoc['total_gross_weight'] ?? 0);
        $diffGrossWt = $currGrossWt - $oldGrossWt;

        $financialDeltas = [
            'subtotal' => [
                'old' => $oldSubtotal,
                'current' => $currSubtotal,
                'diff' => $diffSubtotal,
            ],
            'final_total' => [
                'old' => $oldFinalTotal,
                'current' => $currFinalTotal,
                'diff' => $diffFinalTotal,
            ],
            'total_net_weight' => [
                'old' => $oldNetWt,
                'current' => $currNetWt,
                'diff' => $diffNetWt,
            ],
            'total_gross_weight' => [
                'old' => $oldGrossWt,
                'current' => $currGrossWt,
                'diff' => $diffGrossWt,
            ],
        ];

        // 3. Line items diff
        $currItemsByCode = [];
        foreach ($currItems as $cIndex => $cItem) {
            $code = strtoupper(trim((string) ($cItem['item_code'] ?? '')));
            $currItemsByCode[$code][] = array_merge($cItem, ['__idx' => $cIndex]);
        }

        $itemsDiff = [];
        $matchedCurrentIndices = [];
        $historicalStatusMap = [];
        $additionsCount = 0;
        $deletionsCount = 0;
        $modificationsCount = 0;
        $unchangedCount = 0;

        foreach ($oldItems as $oIndex => $oItem) {
            $code = strtoupper(trim((string) ($oItem['item_code'] ?? '')));
            $matchedCurrItem = null;

            if (! empty($currItemsByCode[$code])) {
                foreach ($currItemsByCode[$code] as $candidate) {
                    if (! in_array($candidate['__idx'], $matchedCurrentIndices, true)) {
                        $matchedCurrItem = $candidate;
                        $matchedCurrentIndices[] = $candidate['__idx'];
                        break;
                    }
                }
            }

            if ($matchedCurrItem !== null) {
                $qtyDiff = (float) ($matchedCurrItem['unit_amount'] ?? 0) - (float) ($oItem['unit_amount'] ?? 0);
                $priceDiff = (float) ($matchedCurrItem['unit_price'] ?? 0) - (float) ($oItem['unit_price'] ?? 0);
                $totalDiff = (float) ($matchedCurrItem['total_amount'] ?? 0) - (float) ($oItem['total_amount'] ?? 0);
                $descChanged = trim((string) ($oItem['description'] ?? '')) !== trim((string) ($matchedCurrItem['description'] ?? ''));

                $isModified = abs($qtyDiff) > 0.0001 || abs($priceDiff) > 0.0001 || abs($totalDiff) > 0.0001 || $descChanged;

                $deltas = [
                    'unit_amount' => $qtyDiff,
                    'unit_price' => $priceDiff,
                    'total_amount' => $totalDiff,
                    'description_changed' => $descChanged,
                ];

                if ($isModified) {
                    $modificationsCount++;
                    $entry = [
                        'type' => 'modified',
                        'item_code' => $oItem['item_code'],
                        'old' => $oItem,
                        'current' => $matchedCurrItem,
                        'deltas' => $deltas,
                        'historical_index' => $oIndex,
                    ];
                } else {
                    $unchangedCount++;
                    $entry = [
                        'type' => 'unchanged',
                        'item_code' => $oItem['item_code'],
                        'old' => $oItem,
                        'current' => $matchedCurrItem,
                        'deltas' => $deltas,
                        'historical_index' => $oIndex,
                    ];
                }

                $itemsDiff[] = $entry;
                $historicalStatusMap[$oIndex] = $entry;
            } else {
                $deletionsCount++;
                $entry = [
                    'type' => 'removed',
                    'item_code' => $oItem['item_code'],
                    'old' => $oItem,
                    'current' => null,
                    'deltas' => [
                        'unit_amount' => -(float) ($oItem['unit_amount'] ?? 0),
                        'unit_price' => -(float) ($oItem['unit_price'] ?? 0),
                        'total_amount' => -(float) ($oItem['total_amount'] ?? 0),
                        'description_changed' => false,
                    ],
                    'historical_index' => $oIndex,
                ];
                $itemsDiff[] = $entry;
                $historicalStatusMap[$oIndex] = $entry;
            }
        }

        // Additions: items present in current but not in historical version
        foreach ($currItems as $cIndex => $cItem) {
            if (! in_array($cIndex, $matchedCurrentIndices, true)) {
                $additionsCount++;
                $itemsDiff[] = [
                    'type' => 'added',
                    'item_code' => $cItem['item_code'],
                    'old' => null,
                    'current' => $cItem,
                    'deltas' => [
                        'unit_amount' => (float) ($cItem['unit_amount'] ?? 0),
                        'unit_price' => (float) ($cItem['unit_price'] ?? 0),
                        'total_amount' => (float) ($cItem['total_amount'] ?? 0),
                        'description_changed' => false,
                    ],
                    'historical_index' => null,
                ];
            }
        }

        // 4. Shipment costs diff
        $shipDiff = [];
        $currShipByMethod = [];
        foreach ($currShip as $sIndex => $s) {
            $m = strtolower(trim((string) ($s['method'] ?? '')));
            $currShipByMethod[$m][] = array_merge($s, ['__idx' => $sIndex]);
        }
        $matchedShipIndices = [];

        foreach ($oldShip as $oS) {
            $m = strtolower(trim((string) ($oS['method'] ?? '')));
            $matchedCurrS = null;
            if (! empty($currShipByMethod[$m])) {
                foreach ($currShipByMethod[$m] as $cand) {
                    if (! in_array($cand['__idx'], $matchedShipIndices, true)) {
                        $matchedCurrS = $cand;
                        $matchedShipIndices[] = $cand['__idx'];
                        break;
                    }
                }
            }

            if ($matchedCurrS !== null) {
                $diffGiven = (float) ($matchedCurrS['given_amount'] ?? 0) - (float) ($oS['given_amount'] ?? 0);
                $diffWeight = (float) ($matchedCurrS['checked_weight'] ?? 0) - (float) ($oS['checked_weight'] ?? 0);
                $diffRate = (float) ($matchedCurrS['rate_per_kg'] ?? 0) - (float) ($oS['rate_per_kg'] ?? 0);
                $isMod = abs($diffGiven) > 0.001 || abs($diffWeight) > 0.001 || abs($diffRate) > 0.001;

                $shipDiff[] = [
                    'type' => $isMod ? 'modified' : 'unchanged',
                    'method' => $oS['method'],
                    'old' => $oS,
                    'current' => $matchedCurrS,
                    'diff_amount' => $diffGiven,
                    'diff_weight' => $diffWeight,
                ];
            } else {
                $shipDiff[] = [
                    'type' => 'removed',
                    'method' => $oS['method'],
                    'old' => $oS,
                    'current' => null,
                    'diff_amount' => -(float) ($oS['given_amount'] ?? 0),
                    'diff_weight' => -(float) ($oS['checked_weight'] ?? 0),
                ];
            }
        }

        foreach ($currShip as $sIndex => $s) {
            if (! in_array($sIndex, $matchedShipIndices, true)) {
                $shipDiff[] = [
                    'type' => 'added',
                    'method' => $s['method'],
                    'old' => null,
                    'current' => $s,
                    'diff_amount' => (float) ($s['given_amount'] ?? 0),
                    'diff_weight' => (float) ($s['checked_weight'] ?? 0),
                ];
            }
        }

        $hasChanges = $additionsCount > 0
            || $deletionsCount > 0
            || $modificationsCount > 0
            || count($headerDiffs) > 0
            || abs($diffFinalTotal) > 0.001
            || abs($diffNetWt) > 0.001;

        return [
            'has_changes' => $hasChanges,
            'summary' => [
                'additions' => $additionsCount,
                'deletions' => $deletionsCount,
                'modifications' => $modificationsCount + count($headerDiffs),
                'unchanged' => $unchangedCount,
                'total_changes' => $additionsCount + $deletionsCount + $modificationsCount + count($headerDiffs),
            ],
            'header_diffs' => $headerDiffs,
            'financial_deltas' => $financialDeltas,
            'items_diff' => $itemsDiff,
            'historical_item_status_map' => $historicalStatusMap,
            'shipment_diff' => $shipDiff,
            'current_version' => $document->current_version,
            'snapshot_version' => $version->version_number,
        ];
    }

    /**
     * Restore a document to a previous version state.
     */
    public function restoreVersion(Document $document, int $versionNumber, User $user): Document
    {
        $version = $document->versions()->where('version_number', $versionNumber)->firstOrFail();
        $data = $version->snapshot_data;

        return DB::transaction(function () use ($document, $data, $versionNumber, $user) {
            $newVersionNumber = $document->current_version + 1;

            // 1. Update document header
            $document->update([
                'company_name' => $data['document']['company_name'] ?? $document->company_name,
                'country' => $data['document']['country'] ?? $document->country,
                'address' => $data['document']['address'] ?? null,
                'contact_details' => $data['document']['contact_details'] ?? null,
                'document_date' => $data['document']['document_date'] ?? $document->document_date,
                'currency' => $data['document']['currency'] ?? 'USD',
                'price_list' => $data['document']['price_list'] ?? null,
                'price_label' => $data['document']['price_label'] ?? null,
                'total_net_weight' => $data['document']['total_net_weight'] ?? 0,
                'total_gross_weight' => $data['document']['total_gross_weight'] ?? 0,
                'subtotal' => $data['document']['subtotal'] ?? 0,
                'final_total' => $data['document']['final_total'] ?? 0,
                'status' => $data['document']['status'] ?? 'draft',
                'notes' => $data['document']['notes'] ?? null,
                'current_version' => $newVersionNumber,
                'updated_by' => $user->id,
            ]);

            // 2. Replace items
            $document->items()->delete();
            if (! empty($data['items'])) {
                foreach ($data['items'] as $index => $itemData) {
                    $document->items()->create([
                        'item_code' => $itemData['item_code'],
                        'description' => $itemData['description'] ?? null,
                        'unit_amount' => $itemData['unit_amount'] ?? 1,
                        'unit_price' => $itemData['unit_price'] ?? 0,
                        'total_amount' => $itemData['total_amount'] ?? 0,
                        'sort_order' => $itemData['sort_order'] ?? $index,
                    ]);
                }
            }

            // 3. Replace packages
            $document->packages()->delete();
            if (! empty($data['packages'])) {
                foreach ($data['packages'] as $pkgData) {
                    $document->packages()->create($pkgData);
                }
            }

            // 4. Replace shipment costs
            $document->shipmentCosts()->delete();
            if (! empty($data['shipment_costs'])) {
                foreach ($data['shipment_costs'] as $shipData) {
                    $document->shipmentCosts()->create([
                        'method' => $shipData['method'],
                        'checked_weight' => $shipData['checked_weight'] ?? null,
                        'rate_per_kg' => $shipData['rate_per_kg'] ?? null,
                        'chargeable_weight' => $shipData['chargeable_weight'] ?? null,
                        'system_amount' => $shipData['system_amount'] ?? null,
                        'added_amount' => $shipData['added_amount'] ?? null,
                        'given_amount' => $shipData['given_amount'] ?? null,
                    ]);
                }
            }

            // 5. Create snapshot for the new restored version
            $this->createSnapshot($document, $user, "Restored from version {$versionNumber}");

            return $document->fresh(['items', 'packages', 'shipmentCosts', 'versions']);
        });
    }
}
