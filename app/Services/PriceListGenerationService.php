<?php

namespace App\Services;

use App\Models\ItemPrice;
use App\Models\ItemPriceBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class PriceListGenerationService
{
    public function sourceQuery(array $config): Builder
    {
        if ($config['source_type'] === 'saved_base') {
            return ItemPriceBase::query()->with('item')->where('price_list', $config['source_list']);
        }

        return ItemPrice::query()->where('price_list', $config['source_list'])
            ->where('price_label', $config['source_label'])->where('currency', $config['source_currency'] ?? 'USD');
    }

    private function sourceDetails(ItemPrice|ItemPriceBase $source): array
    {
        return [
            $source->item_id,
            $source instanceof ItemPriceBase ? $source->item?->item_code : $source->item_code,
            (float) ($source instanceof ItemPriceBase ? $source->base_price_usd : $source->price),
        ];
    }

    public function labels(array $config): array
    {
        $labels = [];
        foreach ($config['currencies'] as $currency) {
            foreach ($config['margins'] as $margin) {
                $percent = rtrim(rtrim(number_format($margin, 2, '.', ''), '0'), '.');
                $labels[] = "{$currency} {$percent}%";
            }
        }

        return $labels;
    }

    public function inspect(array $config): array
    {
        $labels = $this->labels($config);
        $digest = hash_init('sha256');
        $summary = ['items' => 0, 'prices' => 0, 'add' => 0, 'replace' => 0, 'keep' => 0, 'sample' => []];

        $this->sourceQuery($config)->chunkById(100, function ($sources) use ($config, $labels, $digest, &$summary) {
            $existing = ItemPrice::query()->whereIn('item_id', $sources->pluck('item_id'))
                ->whereIn('price_list', array_values($config['target_lists']))->whereIn('price_label', $labels)
                ->get()->keyBy(fn ($price) => $price->item_id.'|'.$price->price_list.'|'.$price->price_label);

            foreach ($sources as $source) {
                [$itemId, $code, $basePrice] = $this->sourceDetails($source);
                if (! $code) {
                    continue;
                }
                $summary['items']++;
                hash_update($digest, json_encode([$source->id, $itemId, $code, $basePrice, $source->updated_at?->toISOString()]));

                foreach ($config['currencies'] as $currency) {
                    foreach ($config['margins'] as $margin) {
                        $percent = rtrim(rtrim(number_format($margin, 2, '.', ''), '0'), '.');
                        $label = "{$currency} {$percent}%";
                        $price = round($basePrice * (1 + $margin / 100) * $config['rates'][$currency], 2);
                        if (! is_finite($price) || $price > 99999999999) {
                            throw ValidationException::withMessages(['margins' => "Generated price for {$code} exceeds the supported range."]);
                        }
                        $current = $existing->get($itemId.'|'.$config['target_lists'][$currency].'|'.$label);
                        hash_update($digest, json_encode([$config['target_lists'][$currency], $label, $current?->id, $current?->currency, $current?->price, $current?->updated_at?->toISOString()]));
                        $action = $current ? ($config['mode'] === 'replace' ? 'Replace' : 'Keep existing') : 'Add';
                        $summary[strtolower($action) === 'keep existing' ? 'keep' : strtolower($action)]++;
                        $summary['prices']++;
                        if (count($summary['sample']) < 30) {
                            $summary['sample'][] = [
                                'item_code' => $code,
                                'price_list' => $config['target_lists'][$currency],
                                'label' => $label,
                                'currency' => $currency,
                                'current' => $current ? (float) $current->price : null,
                                'generated' => $price,
                                'action' => $action,
                            ];
                        }
                    }
                }
            }
        });

        if ($summary['items'] === 0) {
            throw ValidationException::withMessages(['source_key' => 'No items were found in the selected source currency tier.']);
        }
        $summary['digest'] = hash_final($digest);

        return $summary;
    }

    public function write(array $config): void
    {
        $this->sourceQuery($config)->chunkById(25, function ($sources) use ($config) {
            $now = now();
            $rows = [];
            foreach ($sources as $source) {
                [$itemId, $code, $basePrice] = $this->sourceDetails($source);
                if (! $code) {
                    continue;
                }
                foreach ($config['currencies'] as $currency) {
                    foreach ($config['margins'] as $margin) {
                        $percent = rtrim(rtrim(number_format($margin, 2, '.', ''), '0'), '.');
                        $rows[] = [
                            'item_id' => $itemId,
                            'item_code' => $code,
                            'price_list' => $config['target_lists'][$currency],
                            'currency' => $currency,
                            'price_label' => "{$currency} {$percent}%",
                            'price' => round($basePrice * (1 + $margin / 100) * $config['rates'][$currency], 2),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
            if ($rows === []) {
                return;
            }
            if ($config['mode'] === 'replace') {
                ItemPrice::upsert($rows, ['item_code', 'price_list', 'price_label'], ['item_id', 'currency', 'price', 'updated_at']);
            } else {
                ItemPrice::insertOrIgnore($rows);
            }
        });
    }
}
