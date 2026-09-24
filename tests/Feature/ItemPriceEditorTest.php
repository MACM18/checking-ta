<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemPriceEditorTest extends TestCase
{
    use RefreshDatabase;

    private function item(): Item
    {
        return Item::create(['item_code' => 'EDIT-001', 'description' => 'Old description']);
    }

    public function test_item_editor_updates_details_and_one_price_without_changing_other_tiers(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $item = $this->item();
        $first = ItemPrice::create(['item_id' => $item->id, 'item_code' => $item->item_code, 'price_list' => 'Price List', 'currency' => 'USD', 'price_label' => 'USD 30%', 'price' => 130]);
        $other = ItemPrice::create(['item_id' => $item->id, 'item_code' => $item->item_code, 'price_list' => 'Union', 'currency' => 'USD', 'price_label' => 'USD 30%', 'price' => 99]);

        $this->actingAs($user)->get(route('price-tracker.items.edit', $item))->assertOk()->assertSee('Base price');
        $this->patch(route('price-tracker.items.update', $item), ['description' => 'Updated description', 'net_weight' => '2.5'])->assertRedirect();
        $this->patch(route('price-tracker.items.prices.update', [$item, $first]), ['price' => 140])->assertRedirect();

        $this->assertSame('Updated description', $item->fresh()->description);
        $this->assertEquals(2.5, $item->fresh()->net_weight);
        $this->assertEquals(140, $first->fresh()->price);
        $this->assertEquals(99, $other->fresh()->price);
    }

    public function test_generation_previews_then_adds_missing_tiers_without_overwriting_existing_prices(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $item = $this->item();
        ItemPrice::create(['item_id' => $item->id, 'item_code' => $item->item_code, 'price_list' => 'Price List', 'currency' => 'USD', 'price_label' => 'USD 30%', 'price' => 777]);
        ItemPrice::create(['item_id' => $item->id, 'item_code' => $item->item_code, 'price_list' => 'Union', 'currency' => 'AED', 'price_label' => 'AED 30%', 'price' => 888]);

        $this->actingAs($user)->post(route('price-tracker.items.base.save', $item), [
            'price_list' => 'Price List', 'base_price_usd' => 100, 'usd_to_aed_multiplier' => 3.5,
        ])->assertRedirect();
        $this->assertDatabaseCount('item_prices', 2);

        $this->post(route('price-tracker.items.generate.preview', $item), [
            'price_list' => 'Price List', 'percentages' => '30, 40', 'mode' => 'missing', 'calculation' => 'markup',
        ])->assertRedirect();
        $this->assertDatabaseCount('item_prices', 2);
        $this->get(route('price-tracker.items.edit', [$item, 'price_list' => 'Price List']))->assertOk()->assertSee('Keep existing')->assertSee('455.0000');
        $this->post(route('price-tracker.items.generate.apply', $item), ['confirm_generation' => 1])->assertRedirect();

        $this->assertDatabaseHas('item_prices', ['item_code' => $item->item_code, 'price_list' => 'Price List', 'price_label' => 'USD 30%', 'price' => 777]);
        $this->assertDatabaseHas('item_prices', ['item_code' => $item->item_code, 'price_list' => 'Price List', 'price_label' => 'AED 30%', 'price' => 455]);
        $this->assertDatabaseHas('item_prices', ['item_code' => $item->item_code, 'price_list' => 'Price List', 'price_label' => 'USD 40%', 'price' => 140]);
        $this->assertDatabaseHas('item_prices', ['item_code' => $item->item_code, 'price_list' => 'Union', 'price_label' => 'AED 30%', 'price' => 888]);
    }

    public function test_replacing_existing_prices_requires_confirmation_and_a_fresh_preview(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $item = $this->item();
        $price = ItemPrice::create(['item_id' => $item->id, 'item_code' => $item->item_code, 'price_list' => 'Price List', 'currency' => 'USD', 'price_label' => 'USD 30%', 'price' => 777]);
        $this->actingAs($user)->post(route('price-tracker.items.base.save', $item), [
            'price_list' => 'Price List', 'base_price_usd' => 100, 'usd_to_aed_multiplier' => 3.5,
        ])->assertRedirect();
        $this->post(route('price-tracker.items.generate.preview', $item), [
            'price_list' => 'Price List', 'percentages' => '30', 'mode' => 'replace', 'calculation' => 'markup',
        ])->assertRedirect();
        $this->post(route('price-tracker.items.generate.apply', $item), ['confirm_generation' => 1])->assertSessionHasErrors('replace_confirmation');
        $this->assertEquals(777, $price->fresh()->price);

        $price->update(['price' => 778]);
        $this->post(route('price-tracker.items.generate.apply', $item), [
            'confirm_generation' => 1, 'replace_confirmation' => 'REPLACE',
        ])->assertSessionHas('error');
        $this->assertEquals(778, $price->fresh()->price);

        $this->post(route('price-tracker.items.generate.preview', $item), [
            'price_list' => 'Price List', 'percentages' => '30', 'mode' => 'replace', 'calculation' => 'markup',
        ])->assertRedirect();
        $this->post(route('price-tracker.items.generate.apply', $item), [
            'confirm_generation' => 1, 'replace_confirmation' => 'REPLACE',
        ])->assertRedirect();
        $this->assertEquals(130, $price->fresh()->price);
    }
}
