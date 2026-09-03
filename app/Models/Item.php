<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'description',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            $q->where('item_code', 'LIKE', "{$term}%")
                ->orWhere('item_code', 'LIKE', "%{$term}%")
                ->orWhere('description', 'LIKE', "%{$term}%");
        });
    }

    public function getPriceForLabel(string $label, ?string $priceList = null): ?float
    {
        $query = $this->prices()->where('price_label', $label);

        if ($priceList) {
            $query->where('price_list', $priceList);
        }

        $priceRecord = $query->first();

        return $priceRecord ? (float) $priceRecord->price : null;
    }
}
