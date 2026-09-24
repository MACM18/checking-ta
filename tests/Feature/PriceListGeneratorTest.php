<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\ItemPriceBase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PriceListGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function sourceKey(string $type, string $list, ?string $label = null): string
    {
        return base64_encode(json_encode(['type' => $type, 'list' => $list, 'label' => $label]));
    }

    public function test_manual_rates_generate_true_margin_prices_for_all_items_in_a_new_list(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        foreach ([['A-1', 100], ['A-2', 200]] as [$code, $base]) {
            $item = Item::create(['item_code' => $code]);
            ItemPriceBase::create(['item_id' => $item->id, 'price_list' => 'Price List', 'base_price_usd' => $base, 'usd_to_aed_multiplier' => 3.6725]);
        }
        ItemPrice::create(['item_id' => Item::where('item_code', 'A-1')->value('id'), 'item_code' => 'A-1', 'price_list' => 'Export Margin', 'currency' => 'USD', 'price_label' => 'USD 30%', 'price' => 999]);

        $this->actingAs($user)->get(route('price-tracker.generate'))->assertOk()->assertSee('Generate currency price lists');
        $this->post(route('price-tracker.generate.preview'), [
            'source_key' => $this->sourceKey('saved_base', 'Price List'),
            'target_list' => 'Export Margin', 'margins' => '30, 40, 50',
            'currencies' => ['USD', 'AED', 'EUR'], 'rate_mode' => 'manual',
            'manual_rates' => ['AED' => 3.5, 'EUR' => 0.9], 'mode' => 'missing',
        ])->assertRedirect(route('price-tracker.generate'));
        $this->assertDatabaseCount('item_prices', 1);
        $this->get(route('price-tracker.generate'))->assertOk()->assertSee('128.5714')->assertSee('Keep existing');
        $this->post(route('price-tracker.generate.apply'), ['confirm_generation' => 1])->assertRedirect(route('price-tracker.index', ['price_list' => 'Export Margin']));

        $this->assertDatabaseCount('item_prices', 18);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'A-1', 'price_list' => 'Export Margin', 'price_label' => 'USD 30%', 'price' => 999]);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'A-1', 'price_list' => 'Export Margin', 'price_label' => 'AED 30%', 'currency' => 'AED', 'price' => 500]);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'A-1', 'price_list' => 'Export Margin', 'price_label' => 'EUR 30%', 'currency' => 'EUR', 'price' => 128.5714]);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'A-2', 'price_list' => 'Export Margin', 'price_label' => 'USD 50%', 'price' => 400]);
        $this->getJson(route('api.price-items.lookup', ['item_code' => 'A-1', 'price_list' => 'Export Margin', 'price_label' => 'EUR 30%', 'currency' => 'EUR']))
            ->assertOk()->assertJson(['unit_price' => 128.5714, 'currency' => 'EUR', 'price_list' => 'Export Margin']);
    }

    public function test_existing_usd_tier_can_feed_auto_rate_list_and_replacement_requires_confirmation(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $item = Item::create(['item_code' => 'SOURCE-1']);
        ItemPrice::create(['item_id' => $item->id, 'item_code' => $item->item_code, 'price_list' => 'Price List', 'currency' => 'USD', 'price_label' => 'USD Base', 'price' => 80]);
        $existing = ItemPrice::create(['item_id' => $item->id, 'item_code' => $item->item_code, 'price_list' => 'Seasonal', 'currency' => 'EUR', 'price_label' => 'EUR 50%', 'price' => 20]);
        Cache::forget('live_exchange_rates_USD');
        Http::fake(['open.er-api.com/*' => Http::response(['rates' => ['USD' => 1, 'EUR' => 0.8]], 200)]);

        $this->actingAs($user)->post(route('price-tracker.generate.preview'), [
            'source_key' => $this->sourceKey('price_tier', 'Price List', 'USD Base'),
            'target_list' => 'Seasonal', 'margins' => '50', 'currencies' => ['EUR'],
            'rate_mode' => 'automatic', 'mode' => 'replace',
        ])->assertRedirect(route('price-tracker.generate'));
        $this->get(route('price-tracker.generate'))->assertOk()->assertSee('128.0000')->assertSee('0.800000');
        $this->post(route('price-tracker.generate.apply'), ['confirm_generation' => 1])->assertSessionHasErrors('replace_confirmation');
        $this->assertEquals(20, $existing->fresh()->price);

        $this->post(route('price-tracker.generate.apply'), ['confirm_generation' => 1, 'replace_confirmation' => 'REPLACE'])->assertRedirect();
        $this->assertEquals(128, $existing->fresh()->price);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'SOURCE-1', 'price_list' => 'Price List', 'price_label' => 'USD Base', 'price' => 80]);
    }

    public function test_changed_base_rejects_a_stale_preview(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $item = Item::create(['item_code' => 'STALE-1']);
        $base = ItemPriceBase::create(['item_id' => $item->id, 'price_list' => 'Price List', 'base_price_usd' => 100, 'usd_to_aed_multiplier' => 3.6725]);
        $this->actingAs($user)->post(route('price-tracker.generate.preview'), [
            'source_key' => $this->sourceKey('saved_base', 'Price List'), 'target_list' => 'New List',
            'margins' => '30', 'currencies' => ['USD'], 'rate_mode' => 'manual', 'mode' => 'missing',
        ])->assertRedirect(route('price-tracker.generate'));
        $base->update(['base_price_usd' => 120]);
        $this->post(route('price-tracker.generate.apply'), ['confirm_generation' => 1])->assertSessionHas('error');
        $this->assertDatabaseCount('item_prices', 0);
    }
}
