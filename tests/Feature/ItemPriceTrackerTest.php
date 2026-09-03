<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemPriceTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_price_tracker_and_import_view(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $indexResponse = $this->actingAs($user)->get(route('price-tracker.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Item Price Tracker');

        $importResponse = $this->actingAs($user)->get(route('price-tracker.import'));
        $importResponse->assertOk();
        $importResponse->assertSee('Excel Column-by-Column Price Importer');
    }

    public function test_can_bulk_import_excel_columns_with_upsert(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $codes = "ITM-101\nITM-102\nITM-103";
        $descriptions = "Stainless Steel Flange 2 inch\nHigh Pressure Valve 50mm\nTitanium Fastener M8";
        $prices = "125.50\n240.00\n18.75";

        $response = $this->actingAs($user)->post(route('price-tracker.import.store'), [
            'price_list_select' => 'Price List',
            'currency' => 'AED',
            'price_label_select' => 'AED 30%',
            'item_codes' => $codes,
            'descriptions' => $descriptions,
            'prices' => $prices,
        ]);

        $response->assertRedirect(route('price-tracker.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('items', 3);
        $this->assertDatabaseCount('item_prices', 3);

        $this->assertDatabaseHas('items', [
            'item_code' => 'ITM-101',
            'description' => 'Stainless Steel Flange 2 inch',
        ]);

        $this->assertDatabaseHas('item_prices', [
            'item_code' => 'ITM-101',
            'price_list' => 'Price List',
            'currency' => 'AED',
            'price_label' => 'AED 30%',
            'price' => 125.50,
        ]);
    }

    public function test_re_importing_same_items_overrides_prices_for_that_label(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        // First import
        $this->actingAs($user)->post(route('price-tracker.import.store'), [
            'price_list_select' => 'Price List',
            'currency' => 'AED',
            'price_label_select' => 'AED 30%',
            'item_codes' => "SKU-999\nSKU-888",
            'descriptions' => "Desc 1\nDesc 2",
            'prices' => "100.00\n200.00",
        ]);

        $this->assertDatabaseHas('item_prices', [
            'item_code' => 'SKU-999',
            'price_label' => 'AED 30%',
            'price' => 100.00,
        ]);

        // Second import with updated prices (Overriding existing label price)
        $overrideResponse = $this->actingAs($user)->post(route('price-tracker.import.store'), [
            'price_list_select' => 'Price List',
            'currency' => 'AED',
            'price_label_select' => 'AED 30%',
            'item_codes' => "SKU-999\nSKU-888",
            'descriptions' => "Updated Desc 1\nUpdated Desc 2",
            'prices' => "150.00\n275.50",
        ]);

        $overrideResponse->assertRedirect(route('price-tracker.index'));

        // Count should still be 2 (not duplicated!)
        $this->assertDatabaseCount('items', 2);
        $this->assertDatabaseCount('item_prices', 2);

        // Prices must be overridden
        $this->assertDatabaseHas('item_prices', [
            'item_code' => 'SKU-999',
            'price_label' => 'AED 30%',
            'price' => 150.00,
        ]);
        $this->assertDatabaseHas('item_prices', [
            'item_code' => 'SKU-888',
            'price_label' => 'AED 30%',
            'price' => 275.50,
        ]);
    }

    public function test_importing_different_price_label_adds_new_tier_without_overwriting_other_labels(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        // Import AED 30%
        $this->actingAs($user)->post(route('price-tracker.import.store'), [
            'price_list_select' => 'Price List',
            'currency' => 'AED',
            'price_label_select' => 'AED 30%',
            'item_codes' => 'SKU-ALPHA',
            'descriptions' => 'Alpha Item',
            'prices' => '100.00',
        ]);

        // Import AED 50%
        $this->actingAs($user)->post(route('price-tracker.import.store'), [
            'price_list_select' => 'Price List',
            'currency' => 'AED',
            'price_label_select' => 'AED 50%',
            'item_codes' => 'SKU-ALPHA',
            'descriptions' => 'Alpha Item',
            'prices' => '130.00',
        ]);

        // Import USD 40%
        $this->actingAs($user)->post(route('price-tracker.import.store'), [
            'price_list_select' => 'Union',
            'currency' => 'USD',
            'price_label_select' => 'USD 40%',
            'item_codes' => 'SKU-ALPHA',
            'descriptions' => 'Alpha Item',
            'prices' => '35.00',
        ]);

        $this->assertDatabaseCount('items', 1);
        $this->assertDatabaseCount('item_prices', 3);

        $item = Item::where('item_code', 'SKU-ALPHA')->first();
        $this->assertEquals(100.00, $item->getPriceForLabel('AED 30%'));
        $this->assertEquals(130.00, $item->getPriceForLabel('AED 50%'));
        $this->assertEquals(35.00, $item->getPriceForLabel('USD 40%'));
    }

    public function test_api_search_endpoint_returns_autocomplete_suggestions(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $item = Item::create([
            'item_code' => 'VALVE-HEX-101',
            'description' => 'Heavy Duty Hexagonal Valve',
        ]);

        ItemPrice::create([
            'item_id' => $item->id,
            'item_code' => $item->item_code,
            'price_list' => 'Price List',
            'currency' => 'AED',
            'price_label' => 'AED 30%',
            'price' => 85.00,
        ]);

        $response = $this->actingAs($user)->getJson(route('api.price-items.search', ['q' => 'VALVE']));

        $response->assertOk();
        $response->assertJsonFragment([
            'item_code' => 'VALVE-HEX-101',
            'description' => 'Heavy Duty Hexagonal Valve',
            'unit_price' => 85.0,
        ]);
    }

    public function test_api_lookup_endpoint_returns_exact_item_price_and_description(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $item = Item::create([
            'item_code' => 'PUMP-500',
            'description' => 'Centrifugal Submersible Pump',
        ]);

        ItemPrice::create([
            'item_id' => $item->id,
            'item_code' => $item->item_code,
            'price_list' => 'Price List',
            'currency' => 'AED',
            'price_label' => 'AED 40%',
            'price' => 520.00,
        ]);

        $response = $this->actingAs($user)->getJson(route('api.price-items.lookup', [
            'item_code' => 'PUMP-500',
            'price_label' => 'AED 40%',
        ]));

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'item_code' => 'PUMP-500',
            'description' => 'Centrifugal Submersible Pump',
            'unit_price' => 520.0,
            'currency' => 'AED',
            'price_label' => 'AED 40%',
        ]);
    }
}
