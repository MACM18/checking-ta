<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_active',
        'is_default',
        'rate_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'rate_updated_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('is_default')->orderBy('code');
    }

    /**
     * Get all active currencies (cached for fast repeated lookups).
     */
    public static function getAllActive(): Collection
    {
        return Cache::remember('active_currencies_list', 300, function () {
            return self::active()->get();
        });
    }

    /**
     * Get the default currency (USD).
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->first() ?? self::where('code', 'USD')->first();
    }

    /**
     * Get exchange rate against 1 USD for a specific currency code.
     */
    public static function getRate(string $code): float
    {
        $code = strtoupper(trim($code));
        if ($code === 'USD') {
            return 1.0;
        }

        $currency = self::where('code', $code)->first();

        return $currency ? (float) $currency->exchange_rate : 1.0;
    }

    /**
     * Clear cached active currencies list on model events.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('active_currencies_list');
        });

        static::deleted(function () {
            Cache::forget('active_currencies_list');
        });
    }
}
