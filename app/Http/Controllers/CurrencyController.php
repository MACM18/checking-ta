<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Services\CurrencyRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrencyController extends Controller
{
    protected CurrencyRateService $currencyService;

    public function __construct(CurrencyRateService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get active currencies and their rates as JSON.
     */
    public function index(): JsonResponse
    {
        $currencies = Currency::active()->get();

        return response()->json([
            'currencies' => $currencies,
        ]);
    }

    /**
     * Query exchange rate for a currency code (from USD by default) or between two currencies.
     */
    public function rate(Request $request): JsonResponse
    {
        $to = strtoupper(trim($request->input('to', $request->input('code', 'USD'))));
        $from = strtoupper(trim($request->input('from', 'USD')));

        $rate = $this->currencyService->getExchangeRate($from, $to);
        $currencyModel = Currency::where('code', $to)->first();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'rate' => $rate,
            'source' => 'Open Currency API (open.er-api.com)',
            'symbol' => $currencyModel?->symbol ?: $to,
            'name' => $currencyModel?->name ?: $to,
            'rate_updated_at' => $currencyModel?->rate_updated_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Store a new currency type from the Price Tracker page.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (! Auth::user()?->canManagePriceTracker()) {
            abort(403, 'You do not have permission to manage currency types.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z]{3,10}$/', 'unique:currencies,code'],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $rate = $validated['exchange_rate'] ?? null;

        // If exchange rate is not manually supplied, fetch live rate from open API
        if (! $rate) {
            $liveRate = $this->currencyService->getRateForCurrency($code);
            $rate = $liveRate ?: 1.0;
        }

        $currency = Currency::create([
            'code' => $code,
            'name' => trim($validated['name']),
            'symbol' => trim($validated['symbol'] ?? $code),
            'exchange_rate' => $rate,
            'is_active' => true,
            'is_default' => false,
            'rate_updated_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Currency {$currency->code} successfully added.",
                'currency' => $currency,
            ], 201);
        }

        return redirect()->route('price-tracker.index')
            ->with('success', "Currency {$currency->code} ({$currency->name}) added with rate 1 USD = {$currency->exchange_rate} {$currency->code}.");
    }

    /**
     * Refresh and sync live rates for all active currencies from the open-source API.
     */
    public function syncRates(Request $request): RedirectResponse|JsonResponse
    {
        if (! Auth::user()?->canManagePriceTracker()) {
            abort(403, 'You do not have permission to sync currency rates.');
        }

        $updated = $this->currencyService->syncActiveCurrencies();
        $count = count($updated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Successfully refreshed live rates for {$count} currency types.",
                'rates' => $updated,
            ]);
        }

        return redirect()->route('price-tracker.index')
            ->with('success', "Successfully refreshed live exchange rates for {$count} currencies from Open Currency API.");
    }

    /**
     * Delete a custom currency type.
     */
    public function destroy(Currency $currency): RedirectResponse
    {
        if (! Auth::user()?->canManagePriceTracker()) {
            abort(403, 'You do not have permission to delete currency types.');
        }

        if (in_array(strtoupper($currency->code), ['USD', 'AED'])) {
            return redirect()->route('price-tracker.index')
                ->with('error', "Base currency {$currency->code} cannot be deleted.");
        }

        $code = $currency->code;
        $currency->delete();

        return redirect()->route('price-tracker.index')
            ->with('success', "Currency {$code} has been deleted.");
    }
}
