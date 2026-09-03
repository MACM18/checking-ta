<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemPrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ItemPriceTrackerController extends Controller
{
    /**
     * Display the item price tracker catalogue and price points.
     */
    public function index(Request $request): View
    {
        $query = Item::query()->with('prices');

        // Search by Item Code or Description
        if ($search = trim($request->input('q', ''))) {
            $query->search($search);
        }

        // Filter by Price List
        if ($priceList = trim($request->input('price_list', ''))) {
            $query->whereHas('prices', function ($q) use ($priceList) {
                $q->where('price_list', $priceList);
            });
        }

        // Filter by Price Label
        if ($priceLabel = trim($request->input('price_label', ''))) {
            $query->whereHas('prices', function ($q) use ($priceLabel) {
                $q->where('price_label', $priceLabel);
            });
        }

        $items = $query->orderBy('item_code')->paginate(50)->withQueryString();

        // Statistics
        $totalItems = Item::count();
        $totalPrices = ItemPrice::count();
        $availablePriceLists = ItemPrice::distinct()->pluck('price_list')->filter()->values()->all();
        $availablePriceLabels = ItemPrice::distinct()->pluck('price_label')->filter()->values()->all();

        return view('price_tracker.index', compact(
            'items',
            'totalItems',
            'totalPrices',
            'availablePriceLists',
            'availablePriceLabels'
        ));
    }

    /**
     * Show the Column-by-Column Excel copy and paste import workstation.
     */
    public function import(): View
    {
        $defaultPriceLists = ItemPrice::DEFAULT_PRICE_LISTS;
        $currencies = ItemPrice::CURRENCIES;
        $defaultLabels = ItemPrice::DEFAULT_LABELS;

        // Also fetch any existing custom price lists and labels
        $existingLists = ItemPrice::distinct()->pluck('price_list')->filter()->values()->all();
        $allPriceLists = array_values(array_unique(array_merge($defaultPriceLists, $existingLists)));

        $existingLabels = ItemPrice::distinct()->pluck('price_label')->filter()->values()->all();
        $allPriceLabels = array_values(array_unique(array_merge($defaultLabels, $existingLabels)));

        return view('price_tracker.import', compact(
            'allPriceLists',
            'currencies',
            'allPriceLabels'
        ));
    }

    /**
     * Store and upsert copied Excel data into items and item_prices.
     */
    public function storeImport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'price_list_select' => 'required|string|max:100',
            'price_list_custom' => 'nullable|string|max:100',
            'currency' => 'required|string|in:AED,USD',
            'price_label_select' => 'required|string|max:100',
            'price_label_custom' => 'nullable|string|max:100',
            'item_codes' => 'required|string',
            'descriptions' => 'nullable|string',
            'prices' => 'required|string',
        ]);

        // Resolve Price List
        $priceList = $validated['price_list_select'] === 'custom'
            ? trim($validated['price_list_custom'] ?? '')
            : trim($validated['price_list_select']);

        if (empty($priceList)) {
            $priceList = ItemPrice::PRICE_LIST_STANDARD;
        }

        // Resolve Currency
        $currency = $validated['currency'];

        // Resolve Price Label
        $priceLabel = $validated['price_label_select'] === 'custom'
            ? trim($validated['price_label_custom'] ?? '')
            : trim($validated['price_label_select']);

        if (empty($priceLabel)) {
            $priceLabel = 'AED 30%';
        }

        // Parse line-by-line inputs
        $codeLines = preg_split('/\r\n|\r|\n/', trim($validated['item_codes']));
        $priceLines = preg_split('/\r\n|\r|\n/', trim($validated['prices']));
        $descLines = ! empty($validated['descriptions'])
            ? preg_split('/\r\n|\r|\n/', trim($validated['descriptions']))
            : [];

        $cleanedRows = [];
        $totalRows = min(count($codeLines), count($priceLines));

        for ($i = 0; $i < $totalRows; $i++) {
            $code = trim($codeLines[$i] ?? '');
            $rawPrice = trim($priceLines[$i] ?? '');
            $desc = isset($descLines[$i]) ? trim($descLines[$i]) : null;

            if ($code === '' || $rawPrice === '') {
                continue;
            }

            // Clean price string (remove commas, currency symbols, whitespace)
            $cleanPrice = preg_replace('/[^0-9.]/', '', $rawPrice);

            if (! is_numeric($cleanPrice)) {
                continue;
            }

            $cleanedRows[] = [
                'code' => $code,
                'description' => $desc,
                'price' => (float) $cleanPrice,
            ];
        }

        if (empty($cleanedRows)) {
            return back()->withInput()->with('error', 'No valid items and prices were parsed from the pasted data. Please check your columns and try again.');
        }

        // Batch processing in chunks of 1000 for high performance on 10K+ items
        DB::transaction(function () use ($cleanedRows, $priceList, $currency, $priceLabel) {
            $chunks = array_chunk($cleanedRows, 1000);

            foreach ($chunks as $chunk) {
                // 1. Prepare items batch
                $now = now();
                $itemsBatch = [];
                foreach ($chunk as $row) {
                    $itemData = [
                        'item_code' => $row['code'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if ($row['description'] !== null && $row['description'] !== '') {
                        $itemData['description'] = $row['description'];
                    }
                    $itemsBatch[] = $itemData;
                }

                // Upsert items (inserts new, or updates description if provided)
                Item::upsert(
                    $itemsBatch,
                    ['item_code'],
                    ['description', 'updated_at']
                );

                // 2. Fetch IDs for the chunked item codes
                $codes = array_column($chunk, 'code');
                $itemMap = Item::whereIn('item_code', $codes)->pluck('id', 'item_code');

                // 3. Prepare item_prices batch
                $pricesBatch = [];
                foreach ($chunk as $row) {
                    $itemId = $itemMap[$row['code']] ?? null;
                    if (! $itemId) {
                        continue;
                    }

                    $pricesBatch[] = [
                        'item_id' => $itemId,
                        'item_code' => $row['code'],
                        'price_list' => $priceList,
                        'currency' => $currency,
                        'price_label' => $priceLabel,
                        'price' => $row['price'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Upsert item prices (overrides price and currency if item_code + price_list + price_label exists)
                ItemPrice::upsert(
                    $pricesBatch,
                    ['item_code', 'price_list', 'price_label'],
                    ['item_id', 'currency', 'price', 'updated_at']
                );
            }
        });

        $count = count($cleanedRows);

        return redirect()->route('price-tracker.index')
            ->with('success', "Import Complete! {$count} item prices successfully processed and overridden for '{$priceLabel}' under Price List '{$priceList}' ({$currency}).");
    }

    /**
     * Delete an item and its associated prices.
     */
    public function destroy(Item $item): RedirectResponse
    {
        $code = $item->item_code;
        $item->delete();

        return redirect()->route('price-tracker.index')
            ->with('success', "Item '{$code}' and all associated prices removed.");
    }
}
