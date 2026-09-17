<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentItem extends Model
{
    protected $fillable = [
        'document_id',
        'item_code',
        'order_sheet_reference',
        'description',
        'unit_amount',
        'unit_price',
        'price_list',
        'is_fallback',
        'unit_weight',
        'total_weight',
        'total_amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_amount' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'is_fallback' => 'boolean',
            'unit_weight' => 'decimal:3',
            'total_weight' => 'decimal:3',
            'total_amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Check if this item's price is derived from Union / Union Special list as a fallback.
     */
    public function isUnionFallback(): bool
    {
        if ($this->is_fallback) {
            return true;
        }

        if (! empty($this->price_list) && stripos($this->price_list, 'Union') !== false) {
            $docPriceList = $this->document?->price_list;
            if (empty($docPriceList) || stripos($docPriceList, 'Union') === false) {
                return true;
            }
        }

        return false;
    }
}
