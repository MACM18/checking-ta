<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'prefix',
        'suffix',
        'description',
        'badge_color',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get array of prefix values.
     */
    public function getPrefixListAttribute(): array
    {
        if (empty($this->prefix)) {
            return [];
        }

        return array_map('trim', explode(',', strtoupper($this->prefix)));
    }

    /**
     * Get array of suffix values.
     */
    public function getSuffixListAttribute(): array
    {
        if (empty($this->suffix)) {
            return [];
        }

        return array_map('trim', explode(',', strtoupper($this->suffix)));
    }

    /**
     * Check if a document number matches this document type's prefix or suffix rules.
     */
    public function matchesNumber(string $number): bool
    {
        $code = strtoupper(trim($number));

        // Check suffixes first
        foreach ($this->suffix_list as $suffix) {
            if ($suffix !== '' && str_ends_with($code, $suffix)) {
                return true;
            }
        }

        // Check prefixes
        foreach ($this->prefix_list as $prefix) {
            if ($prefix !== '' && str_starts_with($code, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * System default document types.
     */
    public static function defaultTypes(): array
    {
        return [
            [
                'code' => 'proforma_invoice',
                'name' => 'Proforma Invoice (E / EL)',
                'prefix' => 'E,EL',
                'suffix' => null,
                'description' => 'Proforma commercial invoice for quotation and orders',
                'badge_color' => 'indigo',
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'invoice',
                'name' => 'Invoice (N)',
                'prefix' => 'N',
                'suffix' => null,
                'description' => 'Official final commercial invoice',
                'badge_color' => 'emerald',
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'packing_list',
                'name' => 'Packing List (W)',
                'prefix' => 'W',
                'suffix' => null,
                'description' => 'Shipment cargo packaging and weight list',
                'badge_color' => 'blue',
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'reserve',
                'name' => 'Reserve (ends with R)',
                'prefix' => null,
                'suffix' => 'R',
                'description' => 'Reserve documentation copy',
                'badge_color' => 'amber',
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'credit_note',
                'name' => 'Credit Note (ends with CR)',
                'prefix' => 'CR',
                'suffix' => 'CR',
                'description' => 'Customer credit refund note',
                'badge_color' => 'rose',
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'delivery_note',
                'name' => 'Delivery Note (ends with D)',
                'prefix' => 'DN',
                'suffix' => 'D',
                'description' => 'Customer delivery sign-off receipt',
                'badge_color' => 'teal',
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 6,
            ],
            [
                'code' => 'clearing_invoice',
                'name' => 'Clearing Invoice (ends with C)',
                'prefix' => null,
                'suffix' => 'C',
                'description' => 'Customs and clearance invoice',
                'badge_color' => 'violet',
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 7,
            ],
            [
                'code' => 'cash_receipt',
                'name' => 'Cash Receipt (Custom / CR)',
                'prefix' => 'REC,RCP',
                'suffix' => null,
                'description' => 'Payment cash receipt confirmation',
                'badge_color' => 'sky',
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 8,
            ],
            [
                'code' => 'other',
                'name' => 'Other Document',
                'prefix' => null,
                'suffix' => null,
                'description' => 'General non-categorized document',
                'badge_color' => 'gray',
                'is_active' => true,
                'is_system' => true,
                'sort_order' => 99,
            ],
        ];
    }
}
