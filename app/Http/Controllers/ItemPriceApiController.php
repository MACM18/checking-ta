<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ItemPriceApiController extends Controller
{
    /**
     * Autocomplete suggestions search by item code or description (Fast prefix query).
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim($request->input('q', ''));
        $priceLabel = trim($request->input('price_label', ''));
        $priceList = trim($request->input('price_list', ''));
        $currency = strtoupper(trim($request->input('currency', '')));
        if ($currency && ! in_array($currency, ['USD', 'AED'])) {
            $currency = 'USD';
        }

        if (strlen($term) < 1) {
            return response()->json(['items' => []]);
        }

        $items = Item::query()
            ->where(function ($q) use ($term) {
                $q->where('item_code', 'LIKE', "{$term}%")
                    ->orWhere('item_code', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            })
            ->with('prices')
            ->limit(25)
            ->get();

        $results = $items->map(function ($item) use ($priceList, $priceLabel, $currency) {
            $resolved = $this->resolveItemPrice($item->item_code, $priceLabel, $priceList, $currency, $item->prices);
            $firstPrice = $resolved['record'];
            $isFallback = $resolved['is_fallback'];

            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'description' => $item->description ?? '',
                'unit_price' => $firstPrice ? (float) $firstPrice->price : null,
                'unit_weight' => $item->net_weight !== null ? (float) $item->net_weight : null,
                'net_weight' => $item->net_weight !== null ? (float) $item->net_weight : null,
                'currency' => $firstPrice?->currency ?? null,
                'price_label' => $firstPrice?->price_label ?? null,
                'price_list' => $firstPrice?->price_list ?? null,
                'is_fallback' => $isFallback,
            ];
        });

        return response()->json(['items' => $results]);
    }

    /**
     * Exact price lookup for a specific item code and price label, with fallback to Machine, Union, and other available lists.
     */
    public function lookup(Request $request): JsonResponse
    {
        $code = trim($request->input('item_code', ''));
        $label = trim($request->input('price_label', ''));
        $priceList = trim($request->input('price_list', ''));
        $currency = strtoupper(trim($request->input('currency', '')));
        if ($currency && ! in_array($currency, ['USD', 'AED'])) {
            $currency = 'USD';
        }

        if (empty($code)) {
            return response()->json(['found' => false]);
        }

        $item = Item::where('item_code', $code)->first();
        if (! $item && ! ItemPrice::where('item_code', $code)->exists()) {
            return response()->json(['found' => false]);
        }

        $resolved = $this->resolveItemPrice($code, $label, $priceList, $currency);
        $priceRecord = $resolved['record'];
        $isFallback = $resolved['is_fallback'];

        return response()->json([
            'found' => true,
            'item_code' => $code,
            'description' => $item?->description ?? '',
            'unit_price' => $priceRecord ? (float) $priceRecord->price : null,
            'unit_weight' => $item && $item->net_weight !== null ? (float) $item->net_weight : null,
            'net_weight' => $item && $item->net_weight !== null ? (float) $item->net_weight : null,
            'currency' => $priceRecord?->currency,
            'price_label' => $priceRecord?->price_label,
            'price_list' => $priceRecord?->price_list,
            'is_fallback' => $isFallback,
        ]);
    }

    /**
     * Batch price lookup for multiple items in a single request, with fallback to Machine, Union, and other available lists.
     */
    public function batchLookup(Request $request): JsonResponse
    {
        $rawCodes = $request->input('item_codes', []);
        if (is_string($rawCodes)) {
            $rawCodes = preg_split('/[\r\n,;\t]+/', $rawCodes);
        }
        $codes = array_values(array_unique(array_filter(array_map('trim', (array) $rawCodes))));

        $label = trim($request->input('price_label', ''));
        $list = trim($request->input('price_list', ''));
        $currency = strtoupper(trim($request->input('currency', '')));
        if ($currency && ! in_array($currency, ['USD', 'AED'])) {
            $currency = 'USD';
        }

        if (empty($codes)) {
            return response()->json(['results' => (object) []]);
        }

        $items = Item::whereIn('item_code', $codes)->get()->keyBy('item_code');
        $allPrices = ItemPrice::whereIn('item_code', $codes)->get()->groupBy('item_code');

        $results = [];

        foreach ($codes as $code) {
            $item = $items->get($code);
            $prices = $allPrices->get($code, collect());

            if (! $item && $prices->isEmpty()) {
                $results[$code] = [
                    'found' => false,
                    'item_code' => $code,
                    'description' => '',
                    'unit_price' => null,
                    'unit_weight' => null,
                    'net_weight' => null,
                    'currency' => null,
                    'price_label' => null,
                    'price_list' => null,
                    'is_fallback' => false,
                ];

                continue;
            }

            $resolved = $this->resolveItemPrice($code, $label, $list, $currency, $prices);
            $priceRecord = $resolved['record'];
            $isFallback = $resolved['is_fallback'];

            $results[$code] = [
                'found' => true,
                'item_code' => $code,
                'description' => $item?->description ?? '',
                'unit_price' => $priceRecord ? (float) $priceRecord->price : null,
                'unit_weight' => $item && $item->net_weight !== null ? (float) $item->net_weight : null,
                'net_weight' => $item && $item->net_weight !== null ? (float) $item->net_weight : null,
                'currency' => $priceRecord?->currency,
                'price_label' => $priceRecord?->price_label,
                'price_list' => $priceRecord?->price_list,
                'is_fallback' => $isFallback,
            ];
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Resolve the price record for a given item code, applying fallback to Union / Union Special if missing.
     */
    protected function resolveItemPrice(
        string $code,
        ?string $label,
        ?string $list,
        ?string $currency,
        ?Collection $preloadedPrices = null
    ): array {
        $prices = $preloadedPrices ?? ItemPrice::where('item_code', $code)->get();
        $priceRecord = null;
        $isFallback = false;

        // Always prefer the explicitly selected list when it contains this item.
        if ($list) {
            $priceRecord = $this->selectBestPrice(
                $prices->filter(fn ($price) => strcasecmp((string) $price->price_list, $list) === 0),
                $label,
                $currency
            );
        }

        // If the selected list has no item, prefer Machine, then Union, then any
        // other list that has a usable price for this item.
        if (! $priceRecord) {
            $listGroups = $prices->filter(fn ($price) => filled($price->price_list))
                ->groupBy(fn ($price) => strtolower(trim((string) $price->price_list)))
                ->sortBy(function ($group) use ($list) {
                    $name = strtolower(trim((string) $group->first()->price_list));
                    if ($list && $name === strtolower(trim($list))) {
                        return 99;
                    }
                    if (str_starts_with($name, 'machine')) {
                        return 0;
                    }
                    if (str_starts_with($name, 'union')) {
                        return 1;
                    }

                    return 2;
                });

            foreach ($listGroups as $group) {
                $priceRecord = $this->selectBestPrice($group, $label, $currency);
                if ($priceRecord) {
                    $isFallback = true;
                    break;
                }
            }
        }

        return [
            'record' => $priceRecord,
            'is_fallback' => $isFallback,
        ];
    }

    protected function selectBestPrice(Collection $prices, ?string $label, ?string $currency): ?ItemPrice
    {
        if ($prices->isEmpty()) {
            return null;
        }

        if ($label && $currency) {
            $match = $prices->first(fn ($price) =>
                strcasecmp((string) $price->price_label, $label) === 0
                && strcasecmp((string) $price->currency, $currency) === 0
            );
            if ($match) {
                return $match;
            }
        }

        if ($label) {
            $match = $prices->first(fn ($price) => strcasecmp((string) $price->price_label, $label) === 0);
            if ($match) {
                return $match;
            }
        }

        if ($currency) {
            $match = $prices->first(fn ($price) => strcasecmp((string) $price->currency, $currency) === 0);
            if ($match) {
                return $match;
            }
        }

        return $prices->first();
    }

    /**
     * Get available price lists and labels for dropdowns.
     */
    public function labels(): JsonResponse
    {
        $defaultLists = ItemPrice::DEFAULT_PRICE_LISTS;
        $defaultLabels = ItemPrice::DEFAULT_LABELS;

        $dbLists = ItemPrice::distinct()->pluck('price_list')->filter()->values()->all();
        $dbLabels = ItemPrice::distinct()->pluck('price_label')->filter()->values()->all();

        $lists = array_values(array_unique(array_merge($defaultLists, $dbLists)));
        $labels = array_values(array_unique(array_merge($defaultLabels, $dbLabels)));

        return response()->json([
            'price_lists' => $lists,
            'price_labels' => $labels,
            'currencies' => ItemPrice::CURRENCIES,
        ]);
    }
}
