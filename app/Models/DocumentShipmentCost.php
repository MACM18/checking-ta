<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentShipmentCost extends Model
{
    public const METHOD_DHL = 'dhl';

    public const METHOD_AIR_FREIGHT = 'air_freight';

    public const METHOD_SEA_FREIGHT = 'sea_freight';

    public static function supportedMethods(): array
    {
        return [
            self::METHOD_DHL => 'DHL',
            self::METHOD_AIR_FREIGHT => 'Air Freight',
            self::METHOD_SEA_FREIGHT => 'Sea Freight',
        ];
    }

    protected $fillable = [
        'document_id',
        'method',
        'checked_weight',
        'rate_per_kg',
        'chargeable_weight',
        'system_amount',
        'added_amount',
        'given_amount',
    ];

    protected function casts(): array
    {
        return [
            'checked_weight' => 'decimal:3',
            'rate_per_kg' => 'decimal:2',
            'chargeable_weight' => 'decimal:3',
            'system_amount' => 'decimal:2',
            'added_amount' => 'decimal:2',
            'given_amount' => 'decimal:2',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return self::supportedMethods()[$this->method] ?? ucwords(str_replace('_', ' ', $this->method));
    }
}
