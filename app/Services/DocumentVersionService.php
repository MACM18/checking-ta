<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentVersionService
{
    /**
     * Create a snapshot of the current document state.
     */
    public function createSnapshot(Document $document, User $user, string $summary = 'Version snapshot'): DocumentVersion
    {
        $document->loadMissing(['items', 'shipmentCosts']);

        $snapshot = [
            'document' => [
                'document_number' => $document->document_number,
                'document_type' => $document->document_type,
                'company_name' => $document->company_name,
                'country' => $document->country,
                'address' => $document->address,
                'contact_details' => $document->contact_details,
                'document_date' => $document->document_date?->format('Y-m-d'),
                'currency' => $document->currency,
                'total_net_weight' => $document->total_net_weight,
                'total_gross_weight' => $document->total_gross_weight,
                'subtotal' => $document->subtotal,
                'final_total' => $document->final_total,
                'status' => $document->status,
                'notes' => $document->notes,
            ],
            'items' => $document->items->map(function ($item) {
                return [
                    'item_code' => $item->item_code,
                    'description' => $item->description,
                    'unit_amount' => $item->unit_amount,
                    'unit_price' => $item->unit_price,
                    'total_amount' => $item->total_amount,
                    'sort_order' => $item->sort_order,
                ];
            })->toArray(),
            'shipment_costs' => $document->shipmentCosts->map(function ($cost) {
                return [
                    'method' => $cost->method,
                    'checked_weight' => $cost->checked_weight,
                    'system_amount' => $cost->system_amount,
                    'added_amount' => $cost->added_amount,
                    'given_amount' => $cost->given_amount,
                ];
            })->toArray(),
        ];

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
     * Restore document data from a previous version snapshot.
     */
    public function restoreVersion(Document $document, int $versionNumber, User $user): Document
    {
        $version = DocumentVersion::where('document_id', $document->id)
            ->where('version_number', $versionNumber)
            ->firstOrFail();

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

            // 3. Replace shipment costs
            $document->shipmentCosts()->delete();
            if (! empty($data['shipment_costs'])) {
                foreach ($data['shipment_costs'] as $shipData) {
                    $document->shipmentCosts()->create([
                        'method' => $shipData['method'],
                        'checked_weight' => $shipData['checked_weight'] ?? null,
                        'system_amount' => $shipData['system_amount'] ?? null,
                        'added_amount' => $shipData['added_amount'] ?? null,
                        'given_amount' => $shipData['given_amount'] ?? null,
                    ]);
                }
            }

            // 4. Create snapshot for the new restored version
            $this->createSnapshot($document, $user, "Restored from version {$versionNumber}");

            return $document->fresh(['items', 'shipmentCosts', 'versions']);
        });
    }
}
