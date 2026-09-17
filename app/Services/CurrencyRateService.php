<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyRateService
{
    /**
     * Primary open-source free exchange rates API (Open Exchange Rates / ExchangeRate-API free tier).
     * Requires no API key, free, open, and reliable.
     */
    protected const PRIMARY_API_URL = 'https://open.er-api.com/v6/latest/USD';

    /**
     * Secondary open-source fallback API (Frankfurter API, tracking European Central Bank rates).
     */
    protected const FALLBACK_API_URL = 'https://api.frankfurter.dev/v1/latest?base=USD';

    /**
     * Fetch live exchange rates relative to USD from the free open-source API.
     */
    public function fetchLiveRates(string $base = 'USD'): array
    {
        $cacheKey = 'live_exchange_rates_'.strtoupper($base);

        return Cache::remember($cacheKey, 1800, function () {
            // 1. Try Primary Open API
            try {
                $response = Http::timeout(6)->get(self::PRIMARY_API_URL);

                if ($response->successful()) {
                    $data = $response->json();
                    if (! empty($data['rates']) && is_array($data['rates'])) {
                        return $data['rates'];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Primary currency API error: '.$e->getMessage());
            }

            // 2. Try Fallback Open API (Frankfurter)
            try {
                $fallbackResponse = Http::timeout(6)->get(self::FALLBACK_API_URL);

                if ($fallbackResponse->successful()) {
                    $fbData = $fallbackResponse->json();
                    if (! empty($fbData['rates']) && is_array($fbData['rates'])) {
                        $rates = $fbData['rates'];
                        $rates['USD'] = 1.0;
                        if (! isset($rates['AED'])) {
                            $rates['AED'] = 3.6725;
                        }

                        return $rates;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Fallback currency API error: '.$e->getMessage());
            }

            // 3. Fallback to existing rates recorded in currencies table
            $dbRates = Currency::pluck('exchange_rate', 'code')->map(fn ($r) => (float) $r)->toArray();
            $dbRates['USD'] = 1.0;
            if (! isset($dbRates['AED'])) {
                $dbRates['AED'] = 3.6725;
            }
            if (! isset($dbRates['EUR'])) {
                $dbRates['EUR'] = 0.8693;
            }

            return $dbRates;
        });
    }

    /**
     * Fetch live rate for a single currency relative to 1 USD.
     */
    public function getRateForCurrency(string $code): ?float
    {
        $code = strtoupper(trim($code));
        if ($code === 'USD') {
            return 1.0;
        }

        $rates = $this->fetchLiveRates();

        if (isset($rates[$code])) {
            return (float) $rates[$code];
        }

        // Fallback to database record if API did not have this code
        $dbCurrency = Currency::where('code', $code)->first();

        return $dbCurrency ? (float) $dbCurrency->exchange_rate : null;
    }

    /**
     * Sync all active currencies in database with latest live exchange rates.
     */
    public function syncActiveCurrencies(): array
    {
        // Clear cached rates to force fresh fetch
        Cache::forget('live_exchange_rates_USD');
        $liveRates = $this->fetchLiveRates();

        $updated = [];
        $currencies = Currency::all();
        $now = now();

        foreach ($currencies as $currency) {
            $code = strtoupper($currency->code);
            if ($code === 'USD') {
                $currency->update([
                    'exchange_rate' => 1.000000,
                    'rate_updated_at' => $now,
                ]);
                $updated[$code] = 1.000000;

                continue;
            }

            if (isset($liveRates[$code])) {
                $rate = (float) $liveRates[$code];
                $currency->update([
                    'exchange_rate' => $rate,
                    'rate_updated_at' => $now,
                ]);
                $updated[$code] = $rate;
            }
        }

        Cache::forget('active_currencies_list');

        return $updated;
    }

    /**
     * Calculate exchange rate from one currency to another.
     */
    public function getExchangeRate(string $from, string $to): float
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === $to) {
            return 1.0;
        }

        // Both from live rates or database
        $fromRate = ($from === 'USD') ? 1.0 : ($this->getRateForCurrency($from) ?: (Currency::getRate($from) ?: 1.0));
        $toRate = ($to === 'USD') ? 1.0 : ($this->getRateForCurrency($to) ?: (Currency::getRate($to) ?: 1.0));

        if ($fromRate <= 0) {
            return 1.0;
        }

        // Cross-currency conversion through USD base
        return (1.0 / $fromRate) * $toRate;
    }

    /**
     * Convert an amount from one currency to another.
     */
    public function convert(float $amount, string $from, string $to, int $decimals = 2): float
    {
        $rate = $this->getExchangeRate($from, $to);

        return round($amount * $rate, $decimals);
    }
}
