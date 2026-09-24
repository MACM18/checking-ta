<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\ItemPriceBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ItemPriceEditorController extends Controller
{
    private function authorizeTracker(Request $request): void
    {
        abort_unless($request->user()?->canManagePriceTracker(), 403);
    }

    private function previewKey(Item $item): string
    {
        return "price_generation_preview_{$item->id}";
    }

    public function edit(Request $request, Item $item): View
    {
        $this->authorizeTracker($request);
        $item->load(['prices' => fn ($query) => $query->orderBy('price_list')->orderBy('price_label'), 'priceBases']);
        $lists = collect(ItemPrice::DEFAULT_PRICE_LISTS)
            ->merge(ItemPrice::query()->distinct()->pluck('price_list'))
            ->merge($item->prices->pluck('price_list'))
            ->merge($item->priceBases->pluck('price_list'))
            ->unique()->values();
        $selectedList = $request->query('price_list', $item->priceBases->first()?->price_list ?? $item->prices->first()?->price_list ?? ItemPrice::PRICE_LIST_STANDARD);
        $base = $item->priceBases->firstWhere('price_list', $selectedList);
        $currencies = Currency::getAllActive();
        $preview = $request->session()->get($this->previewKey($item));
        if ($preview && $preview['price_list'] !== $selectedList) {
            $preview = null;
        }

        return view('price_tracker.edit', compact('item', 'lists', 'selectedList', 'base', 'preview', 'currencies'));
    }

    public function updateItem(Request $request, Item $item): RedirectResponse
    {
        $this->authorizeTracker($request);
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:10000'],
            'net_weight' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
        ]);
        $item->update([
            'description' => $data['description'] ?? null,
            'net_weight' => $data['net_weight'] ?? null,
        ]);

        return back()->with('success', 'Item details saved.');
    }

    public function updatePrice(Request $request, Item $item, ItemPrice $price): RedirectResponse
    {
        $this->authorizeTracker($request);
        abort_unless($price->item_id === $item->id, 404);
        $data = $request->validate(['price' => ['required', 'numeric', 'min:0', 'max:9999999999']]);
        $price->update(['price' => $data['price']]);
        $request->session()->forget($this->previewKey($item));

        return back()->with('success', "{$price->price_label} price saved.");
    }

    public function storePrice(Request $request, Item $item): RedirectResponse
    {
        $this->authorizeTracker($request);
        $data = $request->validate([
            'price_list' => ['required', 'string', 'max:50'],
            'currency' => ['required', Rule::exists('currencies', 'code')->where('is_active', true)],
            'price_label' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ]);
        $exists = $item->prices()->where('price_list', $data['price_list'])->where('price_label', $data['price_label'])->exists();
        if ($exists) {
            throw ValidationException::withMessages(['price_label' => 'That tier already exists in this list. Edit its price above.']);
        }
        $item->prices()->create($data + ['item_code' => $item->item_code]);
        $request->session()->forget($this->previewKey($item));

        return back()->with('success', 'Price tier added.');
    }

    public function saveBase(Request $request, Item $item): RedirectResponse
    {
        $this->authorizeTracker($request);
        $data = $request->validate([
            'price_list' => ['required', 'string', 'max:50'],
            'base_price_usd' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'usd_to_aed_multiplier' => ['required', 'numeric', 'gt:0', 'max:100'],
        ]);
        $item->priceBases()->updateOrCreate(
            ['price_list' => trim($data['price_list'])],
            ['base_price_usd' => $data['base_price_usd'], 'usd_to_aed_multiplier' => $data['usd_to_aed_multiplier']]
        );
        $request->session()->forget($this->previewKey($item));

        return redirect()->route('price-tracker.items.edit', [$item, 'price_list' => trim($data['price_list'])])
            ->with('success', 'Base price saved. Existing tiers have not changed.');
    }

    private function snapshot(Item $item, ItemPriceBase $base, array $labels): string
    {
        $prices = $item->prices()->where('price_list', $base->price_list)
            ->whereIn('price_label', $labels)->orderBy('price_label')->get()
            ->map(fn ($price) => [$price->price_label, $price->currency, $price->price, $price->updated_at?->toISOString()])->all();

        return hash('sha256', json_encode([$base->id, $base->price_list, $base->base_price_usd, $base->usd_to_aed_multiplier, $prices]));
    }

    public function preview(Request $request, Item $item): RedirectResponse
    {
        $this->authorizeTracker($request);
        $data = $request->validate([
            'price_list' => ['required', 'string', 'max:50'],
            'percentages' => ['required', 'string', 'max:100'],
            'mode' => ['required', Rule::in(['missing', 'replace'])],
            'calculation' => ['required', Rule::in(['markup', 'discount'])],
        ]);
        $base = $item->priceBases()->where('price_list', $data['price_list'])->first();
        if (! $base) {
            throw ValidationException::withMessages(['price_list' => 'Save a USD base price for this list before generating tiers.']);
        }
        $parts = preg_split('/[,\s]+/', trim($data['percentages']));
        if (count($parts) > 12 || collect($parts)->contains(fn ($part) => ! preg_match('/^\d+(?:\.\d{1,2})?$/', $part) || (float) $part > 500)) {
            throw ValidationException::withMessages(['percentages' => 'Enter up to 12 percentages from 0 to 500, separated by commas.']);
        }
        if ($data['calculation'] === 'discount' && collect($parts)->contains(fn ($part) => (float) $part > 100)) {
            throw ValidationException::withMessages(['percentages' => 'Discount percentages cannot exceed 100.']);
        }
        $percentages = collect($parts)->map(fn ($part) => (float) $part)->unique()->values();
        $rows = [];
        foreach ($percentages as $percent) {
            $labelPercent = rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');
            foreach (['USD', 'AED'] as $currency) {
                $label = "{$currency} {$labelPercent}%";
                $existing = $item->prices()->where('price_list', $base->price_list)->where('price_label', $label)->first();
                $factor = $data['calculation'] === 'discount' ? 1 - $percent / 100 : 1 + $percent / 100;
                $computed = (float) $base->base_price_usd * $factor;
                $newPrice = round($currency === 'AED' ? $computed * (float) $base->usd_to_aed_multiplier : $computed, 4);
                $rows[] = [
                    'label' => $label,
                    'currency' => $currency,
                    'existing' => $existing ? (float) $existing->price : null,
                    'generated' => $newPrice,
                    'action' => $existing ? ($data['mode'] === 'replace' ? 'Replace' : 'Keep existing') : 'Add',
                ];
            }
        }
        $labels = array_column($rows, 'label');
        $request->session()->put($this->previewKey($item), [
            'price_list' => $base->price_list,
            'mode' => $data['mode'],
            'calculation' => $data['calculation'],
            'rows' => $rows,
            'snapshot' => $this->snapshot($item, $base, $labels),
        ]);

        return redirect()->route('price-tracker.items.edit', [$item, 'price_list' => $base->price_list]);
    }

    public function apply(Request $request, Item $item): RedirectResponse
    {
        $this->authorizeTracker($request);
        $request->validate(['confirm_generation' => ['accepted']]);
        $preview = $request->session()->get($this->previewKey($item));
        if (! $preview) {
            return back()->with('error', 'Preview the prices again before applying them.');
        }
        if ($preview['mode'] === 'replace' && $request->input('replace_confirmation') !== 'REPLACE') {
            throw ValidationException::withMessages(['replace_confirmation' => 'Type REPLACE to confirm changes to existing prices.']);
        }

        $result = DB::transaction(function () use ($item, $preview) {
            Item::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $base = ItemPriceBase::where('item_id', $item->id)->where('price_list', $preview['price_list'])->lockForUpdate()->first();
            if (! $base || $this->snapshot($item, $base, array_column($preview['rows'], 'label')) !== $preview['snapshot']) {
                return false;
            }
            foreach ($preview['rows'] as $row) {
                if ($row['action'] === 'Keep existing') {
                    continue;
                }
                $price = ItemPrice::firstOrNew([
                    'item_code' => $item->item_code,
                    'price_list' => $base->price_list,
                    'price_label' => $row['label'],
                ]);
                $price->item_id = $item->id;
                $price->currency = $row['currency'];
                $price->price = $row['generated'];
                $price->save();
            }

            return true;
        });
        $request->session()->forget($this->previewKey($item));
        if (! $result) {
            return back()->with('error', 'The base or a tier changed since the preview. Review the prices and preview again.');
        }

        return redirect()->route('price-tracker.items.edit', [$item, 'price_list' => $preview['price_list']])
            ->with('success', 'Previewed prices applied.');
    }
}
