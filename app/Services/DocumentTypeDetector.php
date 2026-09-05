<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DocumentTypeDetector
{
    /**
     * Detect document type based on document number pattern.
     *
     * Rules:
     * - Ends with 'CR' -> Credit Note
     * - Ends with 'C' (and not CR) -> Clearing invoice (e.g. E26211C)
     * - Ends with 'R' (and not CR) -> Reserve (e.g. E26211R)
     * - Ends with 'D' or starts with 'DN' -> Delivery note (e.g. E26211D, DN...)
     * - Starts with 'EL' or 'E' -> Proforma invoice (e.g. E26211, EL26211)
     * - Starts with 'N' -> Invoice (e.g. N10045)
     * - Starts with 'W' -> Packing list (e.g. W30012)
     * - Starts with 'REC' or 'RCP' -> Cash Receipt
     */
    public static function detect(?string $documentNumber): array
    {
        if (empty($documentNumber)) {
            return [
                'type' => null,
                'confidence' => 'none',
                'rule_matched' => null,
                'label' => null,
            ];
        }

        $code = strtoupper(trim($documentNumber));

        // 1. Check dynamic active DocumentTypes with configured prefixes/suffixes
        try {
            $customTypes = Cache::remember('active_custom_document_types', 3600, function () {
                if (! Schema::hasTable('document_types')) {
                    return collect();
                }

                return DocumentType::active()
                    ->where(function ($q) {
                        $q->whereNotNull('prefix')->orWhereNotNull('suffix');
                    })
                    ->ordered()
                    ->get();
            });

            foreach ($customTypes as $dt) {
                if ($dt->matchesNumber($code)) {
                    return [
                        'type' => $dt->code,
                        'confidence' => 'high',
                        'rule_matched' => "Matched {$dt->name} rule",
                        'label' => $dt->name,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Fallback to static rules
        }

        // 2. Fallback to built-in rules
        if (str_ends_with($code, 'CR') || str_starts_with($code, 'CR')) {
            return [
                'type' => Document::TYPE_CREDIT_NOTE,
                'confidence' => 'high',
                'rule_matched' => 'Credit Note (CR format)',
                'label' => 'Credit Note',
            ];
        }

        if (str_ends_with($code, 'C') && ! str_ends_with($code, 'REC')) {
            return [
                'type' => Document::TYPE_CLEARING_INVOICE,
                'confidence' => 'high',
                'rule_matched' => 'Ends with C (Clearing Invoice)',
                'label' => 'Clearing Invoice',
            ];
        }

        if (str_ends_with($code, 'R')) {
            return [
                'type' => Document::TYPE_RESERVE,
                'confidence' => 'high',
                'rule_matched' => 'Ends with R (Reserve)',
                'label' => 'Reserve',
            ];
        }

        if (str_ends_with($code, 'D') || str_starts_with($code, 'DN')) {
            return [
                'type' => Document::TYPE_DELIVERY_NOTE,
                'confidence' => 'high',
                'rule_matched' => 'Ends with D or starts with DN (Delivery Note)',
                'label' => 'Delivery Note',
            ];
        }

        // Prefix rules
        if (str_starts_with($code, 'REC') || str_starts_with($code, 'RCP')) {
            return [
                'type' => Document::TYPE_CASH_RECEIPT,
                'confidence' => 'high',
                'rule_matched' => 'Starts with REC/RCP (Cash Receipt)',
                'label' => 'Cash Receipt',
            ];
        }

        if (str_starts_with($code, 'EL') || str_starts_with($code, 'E')) {
            return [
                'type' => Document::TYPE_PROFORMA,
                'confidence' => 'high',
                'rule_matched' => 'Starts with E / EL (Proforma Invoice)',
                'label' => 'Proforma Invoice',
            ];
        }

        if (str_starts_with($code, 'N')) {
            return [
                'type' => Document::TYPE_INVOICE,
                'confidence' => 'high',
                'rule_matched' => 'Starts with N (Invoice)',
                'label' => 'Invoice',
            ];
        }

        if (str_starts_with($code, 'W')) {
            return [
                'type' => Document::TYPE_PACKING_LIST,
                'confidence' => 'high',
                'rule_matched' => 'Starts with W (Packing List)',
                'label' => 'Packing List',
            ];
        }

        return [
            'type' => null,
            'confidence' => 'manual',
            'rule_matched' => 'Custom Numbering (Please select type)',
            'label' => null,
        ];
    }
}
