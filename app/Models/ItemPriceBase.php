<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPriceBase extends Model
{
    protected $fillable = ['item_id', 'price_list', 'base_price_usd', 'usd_to_aed_multiplier'];

    protected function casts(): array
    {
        return [
            'base_price_usd' => 'decimal:4',
            'usd_to_aed_multiplier' => 'decimal:6',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
