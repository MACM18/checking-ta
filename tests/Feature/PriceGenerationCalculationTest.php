<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceGenerationCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_preview_uses_the_saved_multiplier_and_changes_nothing_until_applied(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $item = Item::create(['item_code' => 'DISC-001']);

        $this->actingAs($user)->post(route('price-tracker.items.base.save', $item), [
            'price_list' => 'Price List', 'base_price_usd' => 200, 'usd_to_aed_multiplier' => 3.5,
        ])->assertRedirect();
        $this->post(route('price-tracker.items.generate.preview', $item), [
            'price_list' => 'Price List', 'percentages' => '25', 'mode' => 'missing', 'calculation' => 'discount',
        ])->assertRedirect();

        $this->assertDatabaseCount('item_prices', 0);
        $this->get(route('price-tracker.items.edit', [$item, 'price_list' => 'Price List']))
            ->assertOk()->assertSee('150.0000')->assertSee('525.0000');
        $this->post(route('price-tracker.items.generate.apply', $item), ['confirm_generation' => 1])->assertRedirect();
        $this->assertDatabaseHas('item_prices', ['item_code' => 'DISC-001', 'price_label' => 'USD 25%', 'price' => 150]);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'DISC-001', 'price_label' => 'AED 25%', 'price' => 525]);
    }
}
