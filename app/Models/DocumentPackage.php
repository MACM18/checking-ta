<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentPackage extends Model
{
    protected $fillable = [
        'document_id',
        'package_type',
        'dimension_type',
        'length_cm',
        'width_cm',
        'height_cm',
        'diameter_cm',
        'quantity',
        'gross_weight_per_pkg_kg',
        'total_gross_weight_kg',
        'volumetric_weight_kg',
        'cbm',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'diameter_cm' => 'decimal:2',
            'quantity' => 'integer',
            'gross_weight_per_pkg_kg' => 'decimal:3',
            'total_gross_weight_kg' => 'decimal:3',
            'volumetric_weight_kg' => 'decimal:3',
            'cbm' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function getFormattedDimensionsAttribute(): string
    {
        if ($this->dimension_type === 'diameter') {
            return "Ø {$this->diameter_cm} × {$this->height_cm} cm";
        }

        return "{$this->length_cm} × {$this->width_cm} × {$this->height_cm} cm";
    }
}
