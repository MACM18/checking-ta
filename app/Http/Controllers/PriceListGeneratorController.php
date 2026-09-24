<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\ItemPrice;
use App\Models\ItemPriceBase;
use App\Services\CurrencyRateService;
use App\Services\PriceListGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PriceListGeneratorController extends Controller
{
    public function __construct(
        private readonly PriceListGenerationService $generator,
        private readonly CurrencyRateService $currencyRates
    ) {}

    private function authorizeTracker(Request $request): void
    {
        abort_unless($request->user()?->canManagePriceTracker(), 403);
    }

    private function sources(): array
    {
        $sources = [];
        foreach (ItemPriceBase::query()->selectRaw('price_list, count(*) as item_count')->groupBy('price_list')->orderBy('price_list')->get() as $base) {
            $sources[] = ['type' => 'saved_base', 'list' => $base->price_list, 'label' => null, 'count' => $base->item_count];
        }
        foreach (ItemPrice::query()->selectRaw('price_list, price_label, currency, count(*) as item_count')
            ->groupBy('price_list', 'price_label', 'currency')->orderBy('price_list')->orderBy('price_label')->orderBy('currency')->get() as $tier) {
            $sources[] = ['type' => 'price_tier', 'list' => $tier->price_list, 'label' => $tier->price_label, 'currency' => $tier->currency, 'count' => $tier->item_count];
        }

        return $sources;
    }

    public function index(Request $request): View
    {
        $this->authorizeTracker($request);
        $sources = $this->sources();
        $currencies = Currency::getAllActive();
        $targetLists = ItemPrice::query()->distinct()->pluck('price_list')->filter()->values();
        $preview = $request->session()->get('price_list_generation_preview');

        return view('price_tracker.generate', compact('sources', 'currencies', 'targetLists', 'preview'));
    }

    public function preview(Request $request): RedirectResponse
    {
        $this->authorizeTracker($request);
        $data = $request->validate([
            'source_key' => ['required', 'string', 'max:500'],
            'target_list' => ['required', 'string', 'max:50'],
            'list_layout' => ['nullable', Rule::in(['combined', 'per_currency'])],
            'margins' => ['required', 'string', 'max:100'],
            'currencies' => ['required', 'array', 'min:1', 'max:10'],
            'currencies.*' => ['required', 'string', 'max:10'],
            'rate_mode' => ['required', Rule::in(['automatic', 'manual'])],
            'manual_rates' => ['nullable', 'array'],
            'mode' => ['required', Rule::in(['missing', 'replace'])],
        ]);

        $source = json_decode(base64_decode($data['source_key'], true) ?: '', true);
        $validSource = collect($this->sources())->contains(fn ($option) => is_array($source)
            && $option['type'] === ($source['type'] ?? null)
            && $option['list'] === ($source['list'] ?? null)
            && $option['label'] === ($source['label'] ?? null)
            && ($option['currency'] ?? 'USD') === ($source['currency'] ?? 'USD')
        );
        if (! $validSource) {
            throw ValidationException::withMessages(['source_key' => 'Select an available base source.']);
        }
        $targetList = trim($data['target_list']);
        if ($targetList === '') {
            throw ValidationException::withMessages(['target_list' => 'Enter a destination list name.']);
        }

        $parts = preg_split('/[,\s]+/', trim($data['margins']));
        if (count($parts) > 12 || collect($parts)->contains(fn ($part) => ! preg_match('/^\d+(?:\.\d{1,2})?$/', $part) || (float) $part >= 100)) {
            throw ValidationException::withMessages(['margins' => 'Enter up to 12 profit margins from 0 to under 100%, separated by commas.']);
        }
        $margins = collect($parts)->map(fn ($part) => (float) $part)->unique()->values()->all();
        $sourceCurrency = strtoupper($source['currency'] ?? 'USD');
        $selectedCurrencies = collect($data['currencies'])->map(fn ($code) => strtoupper($code))->unique()->values()->all();
        $allowedCurrencies = Currency::getAllActive()->pluck('code')->map(fn ($code) => strtoupper($code))->all();
        if (count($selectedCurrencies) !== count($data['currencies']) || array_diff($selectedCurrencies, $allowedCurrencies)) {
            throw ValidationException::withMessages(['currencies' => 'Choose active currencies only, without duplicates.']);
        }

        $layout = $data['list_layout'] ?? 'combined';
        $targetLists = [];
        foreach ($selectedCurrencies as $currency) {
            $name = $layout === 'per_currency' ? $targetList.' '.$currency : $targetList;
            if (mb_strlen($name) > 50) {
                throw ValidationException::withMessages(['target_list' => 'Destination list names must be 50 characters or fewer.']);
            }
            $targetLists[$currency] = $name;
        }

        $rates = [];
        foreach ($selectedCurrencies as $currency) {
            if ($currency === $sourceCurrency) {
                $rates[$currency] = 1.0;

                continue;
            }
            $rate = $data['rate_mode'] === 'automatic'
                ? $this->currencyRates->getExchangeRate($sourceCurrency, $currency)
                : ($data['manual_rates'][$currency] ?? null);
            if (! is_numeric($rate) || (float) $rate <= 0 || (float) $rate > 10000) {
                throw ValidationException::withMessages(['manual_rates' => "Enter a valid {$sourceCurrency} to {$currency} rate, or choose automatic rates."]);
            }
            $rates[$currency] = (float) $rate;
        }

        $config = [
            'source_type' => $source['type'],
            'source_list' => $source['list'],
            'source_label' => $source['label'],
            'source_currency' => $sourceCurrency,
            'target_list' => $targetList,
            'target_lists' => $targetLists,
            'list_layout' => $layout,
            'margins' => $margins,
            'currencies' => $selectedCurrencies,
            'rates' => $rates,
            'rate_mode' => $data['rate_mode'],
            'mode' => $data['mode'],
        ];
        $summary = $this->generator->inspect($config);
        $request->session()->put('price_list_generation_preview', ['config' => $config, 'summary' => $summary]);

        return redirect()->route('price-tracker.generate');
    }

    public function apply(Request $request): RedirectResponse
    {
        $this->authorizeTracker($request);
        $request->validate(['confirm_generation' => ['accepted']]);
        $preview = $request->session()->get('price_list_generation_preview');
        if (! $preview) {
            return back()->with('error', 'Preview the price list again before saving it.');
        }
        if ($preview['config']['mode'] === 'replace' && $request->input('replace_confirmation') !== 'REPLACE') {
            throw ValidationException::withMessages(['replace_confirmation' => 'Type REPLACE to confirm changes to existing tier prices.']);
        }

        $applied = DB::transaction(function () use ($preview) {
            $current = $this->generator->inspect($preview['config']);
            if ($current['digest'] !== $preview['summary']['digest']) {
                return false;
            }
            $this->generator->write($preview['config']);

            return true;
        });
        $request->session()->forget('price_list_generation_preview');
        if (! $applied) {
            return back()->with('error', 'A source base or destination price changed after the preview. Preview again before saving.');
        }

        $summary = $preview['summary'];

        $savedLists = array_values(array_unique($preview['config']['target_lists']));

        return redirect()->route('price-tracker.index', ['price_list' => $savedLists[0]])
            ->with('success', 'Saved '.implode(', ', $savedLists)." for {$summary['items']} items: {$summary['add']} added, {$summary['replace']} replaced, {$summary['keep']} kept.");
    }
}
