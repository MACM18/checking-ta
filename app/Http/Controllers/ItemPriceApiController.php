<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $results = $items->map(function ($item) {
            $firstPrice = $item->prices->first();

            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'description' => $item->description ?? '',
                'unit_price' => $firstPrice ? (float) $firstPrice->price : null,
                'currency' => $firstPrice?->currency ?? null,
                'price_label' => $firstPrice?->price_label ?? null,
            ];
        });

        return response()->json(['items' => $results]);
    }

    /**
     * Exact price lookup for a specific item code and price label.
     */
    public function lookup(Request $request): JsonResponse
    {
        $code = trim($request->input('item_code', ''));
        $label = trim($request->input('price_label', ''));
        $list = trim($request->input('price_list', ''));

        if (empty($code)) {
            return response()->json(['found' => false]);
        }

        $item = Item::where('item_code', $code)->first();
        if (! $item) {
            return response()->json(['found' => false]);
        }

        $priceQuery = ItemPrice::where('item_code', $code);
        if ($label) {
            $priceQuery->where('price_label', $label);
        }
        if ($list) {
            $priceQuery->where('price_list', $list);
        }

        $priceRecord = $priceQuery->first();

        return response()->json([
            'found' => true,
            'item_code' => $item->item_code,
            'description' => $item->description ?? '',
            'unit_price' => $priceRecord ? (float) $priceRecord->price : null,
            'currency' => $priceRecord?->currency,
            'price_label' => $priceRecord?->price_label,
            'price_list' => $priceRecord?->price_list,
        ]);
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
