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
     * Get all active currencies.
     */
    public static function getAllActive(): Collection
    {
        try {
            $currencies = self::active()->get();

            if ($currencies->isNotEmpty()) {
                return $currencies;
            }
        } catch (\Throwable) {
            // Fallback during migrations or bootstrap
        }

        return collect([
            new self(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1.0, 'is_default' => true, 'is_active' => true]),
            new self(['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'AED', 'exchange_rate' => 3.6725, 'is_default' => false, 'is_active' => true]),
            new self(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.8693, 'is_default' => false, 'is_active' => true]),
        ]);
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
