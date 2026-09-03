<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'item_code',
        'price_list',
        'currency',
        'price_label',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
        ];
    }

    public const PRICE_LIST_STANDARD = 'Price List';

    public const PRICE_LIST_UNION = 'Union';

    public const DEFAULT_PRICE_LISTS = [
        self::PRICE_LIST_STANDARD,
        self::PRICE_LIST_UNION,
    ];

    public const CURRENCY_AED = 'AED';

    public const CURRENCY_USD = 'USD';

    public const CURRENCIES = [
        self::CURRENCY_AED,
        self::CURRENCY_USD,
    ];

    public const DEFAULT_LABELS = [
        'AED 30%',
        'AED 40%',
        'AED 50%',
        'USD 30%',
        'USD 40%',
        'USD 50%',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
