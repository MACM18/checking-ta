<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentItem extends Model
{
    protected $fillable = [
        'document_id',
        'item_code',
        'description',
        'unit_amount',
        'unit_price',
        'total_amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_amount' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
