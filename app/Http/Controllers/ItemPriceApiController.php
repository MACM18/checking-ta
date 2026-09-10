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
        $currency = trim($request->input('currency', ''));

        if (strlen($term) < 1) {
            return response()->json(['items' => []]);
        }

        $items = Item::query()
            ->where(function ($q) use ($term) {
                $q->where('item_code', 'LIKE', "{$term}%")
                    ->orWhere('item_code', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            })
            ->with(['prices' => function ($q) use ($priceLabel, $priceList) {
                if ($priceLabel) {
                    $q->where('price_label', $priceLabel);
                }
                if ($priceList) {
                    $q->where('price_list', $priceList);
                }
            }])
            ->limit(25)
            ->get();

        $results = $items->map(function ($item) use ($priceList, $priceLabel, $currency) {
            $firstPrice = $item->prices->first();

            // If not found in selected price list, fallback to Union / Union Special
            if (! $firstPrice && $priceList) {
                $firstPrice = ItemPrice::where('item_code', $item->item_code)
                    ->where(function ($q) {
                        $q->where('price_list', 'Union')
                            ->orWhere('price_list', 'like', 'Union%')
                            ->orWhere('price_list', 'like', '%Union Special%');
                    })
                    ->when($priceLabel, fn ($q) => $q->where('price_label', $priceLabel))
                    ->when($currency, fn ($q) => $q->where('currency', $currency))
                    ->first();
            }

            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'description' => $item->description ?? '',
                'unit_price' => $firstPrice ? (float) $firstPrice->price : null,
                'unit_weight' => $item->net_weight !== null ? (float) $item->net_weight : null,
                'net_weight' => $item->net_weight !== null ? (float) $item->net_weight : null,
                'currency' => $firstPrice?->currency ?? null,
                'price_label' => $firstPrice?->price_label ?? null,
            ];
        });

        return response()->json(['items' => $results]);
    }

    /**
     * Exact price lookup for a specific item code and price label, with fallback to Union / Union Special.
     */
    public function lookup(Request $request): JsonResponse
    {
        $code = trim($request->input('item_code', ''));
        $label = trim($request->input('price_label', ''));
        $list = trim($request->input('price_list', ''));
        $currency = trim($request->input('currency', ''));

        if (empty($code)) {
            return response()->json(['found' => false]);
        }

        $item = Item::where('item_code', $code)->first();
        if (! $item && ! ItemPrice::where('item_code', $code)->exists()) {
            return response()->json(['found' => false]);
        }

        $resolved = $this->resolveItemPrice($code, $label, $list, $currency);
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
     * Batch price lookup for multiple items in a single request, with fallback to Union / Union Special.
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
        $currency = trim($request->input('currency', ''));

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

        // 1. Exact match with requested price_list and price_label
        $priceRecord = $prices->first(function ($p) use ($list, $label) {
            if ($list && strcasecmp($p->price_list, $list) !== 0) {
                return false;
            }
            if ($label && strcasecmp($p->price_label, $label) !== 0) {
                return false;
            }

            return true;
        });

        // 2. Fallback to Union / Union Special if not found in requested price list
        if (! $priceRecord) {
            $unionPrices = $prices->filter(function ($p) {
                $pl = strtolower($p->price_list ?? '');

                return str_starts_with($pl, 'union') || str_contains($pl, 'union special');
            });

            if ($unionPrices->isNotEmpty()) {
                // Try matching exact label in Union / Union Special
                if ($label) {
                    $priceRecord = $unionPrices->first(function ($p) use ($label) {
                        return strcasecmp($p->price_label, $label) === 0;
                    });
                }
                // Try matching currency in Union / Union Special
                if (! $priceRecord && $currency) {
                    $priceRecord = $unionPrices->first(function ($p) use ($currency) {
                        return strcasecmp($p->currency, $currency) === 0;
                    });
                }
                // Fallback to any Union price
                if (! $priceRecord) {
                    $priceRecord = $unionPrices->first();
                }
                if ($priceRecord) {
                    $isFallback = true;
                }
            }
        }

        // 3. If still no record and no specific price list was requested, try any price matching currency or label
        if (! $priceRecord && empty($list)) {
            if ($currency) {
                $priceRecord = $prices->first(function ($p) use ($currency) {
                    return strcasecmp($p->currency, $currency) === 0;
                });
            }
            if (! $priceRecord) {
                $priceRecord = $prices->first();
            }
        }

        return [
            'record' => $priceRecord,
            'is_fallback' => $isFallback,
        ];
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
