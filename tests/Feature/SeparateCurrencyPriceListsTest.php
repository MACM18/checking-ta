<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemPriceBase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeparateCurrencyPriceListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_run_saves_separate_named_lists_for_selected_currencies(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $item = Item::create(['item_code' => 'SPLIT-1']);
        ItemPriceBase::create(['item_id' => $item->id, 'price_list' => 'Price List', 'base_price_usd' => 100, 'usd_to_aed_multiplier' => 3.6725]);
        $sourceKey = base64_encode(json_encode(['type' => 'saved_base', 'list' => 'Price List', 'label' => null]));

        $this->actingAs($user)->post(route('price-tracker.generate.preview'), [
            'source_key' => $sourceKey, 'target_list' => 'Export', 'list_layout' => 'per_currency',
            'margins' => '30', 'currencies' => ['USD', 'AED'], 'rate_mode' => 'manual',
            'manual_rates' => ['AED' => 3.5], 'mode' => 'missing',
        ])->assertRedirect(route('price-tracker.generate'));
        $this->get(route('price-tracker.generate'))->assertOk()->assertSee('Export USD')->assertSee('Export AED');
        $this->post(route('price-tracker.generate.apply'), ['confirm_generation' => 1])->assertRedirect(route('price-tracker.index', ['price_list' => 'Export USD']));

        $this->assertDatabaseHas('item_prices', ['item_code' => 'SPLIT-1', 'price_list' => 'Export USD', 'currency' => 'USD', 'price_label' => 'USD 30%', 'price' => 142.8571]);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'SPLIT-1', 'price_list' => 'Export AED', 'currency' => 'AED', 'price_label' => 'AED 30%', 'price' => 500]);
        $this->actingAs($user)->get(route('price-tracker.index', ['price_list' => 'Export AED']))->assertOk()->assertSee('SPLIT-1');
    }
}
