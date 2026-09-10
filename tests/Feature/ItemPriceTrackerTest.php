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

    public function test_bulk_import_with_sparse_or_missing_descriptions_does_not_fail_column_count(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        // Row 1 and 2 have descriptions, Row 3 has empty description, Row 4 has description
        $codes = "C80820L\nC80830\n57890B\n22560A";
        $descriptions = "Presser Foot for styles 80800CN\nPresser Foot Shank\n\nSet Screw to align presser foot";
        $prices = "120.00\n45.50\n18.00\n5.25";

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

        $this->assertDatabaseCount('items', 4);
        $this->assertDatabaseCount('item_prices', 4);

        $itemWithoutDesc = Item::where('item_code', '57890B')->first();
        $this->assertNotNull($itemWithoutDesc);
        $this->assertNull($itemWithoutDesc->description);
        $this->assertEquals(18.00, $itemWithoutDesc->getPriceForLabel('AED 30%'));
    }

    public function test_batch_lookup_api_returns_all_items_together(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $itemA = Item::create([
            'item_code' => 'BATCH-A',
            'description' => 'Item A Description',
        ]);
        ItemPrice::create([
            'item_id' => $itemA->id,
            'item_code' => 'BATCH-A',
            'price_list' => 'Price List',
            'currency' => 'AED',
            'price_label' => 'AED 30%',
            'price' => 100.00,
        ]);

        $itemB = Item::create([
            'item_code' => 'BATCH-B',
            'description' => 'Item B Description',
        ]);
        ItemPrice::create([
            'item_id' => $itemB->id,
            'item_code' => 'BATCH-B',
            'price_list' => 'Price List',
            'currency' => 'AED',
            'price_label' => 'AED 30%',
            'price' => 250.50,
        ]);

        $response = $this->actingAs($user)->postJson(route('api.price-items.batch-lookup'), [
            'item_codes' => ['BATCH-A', 'BATCH-B', 'BATCH-MISSING'],
            'price_label' => 'AED 30%',
            'price_list' => 'Price List',
            'currency' => 'AED',
        ]);

        $response->assertOk();
        $response->assertJson([
            'results' => [
                'BATCH-A' => [
                    'found' => true,
                    'item_code' => 'BATCH-A',
                    'unit_price' => 100.0,
                    'description' => 'Item A Description',
                    'is_fallback' => false,
                ],
                'BATCH-B' => [
                    'found' => true,
                    'item_code' => 'BATCH-B',
                    'unit_price' => 250.5,
                    'description' => 'Item B Description',
                    'is_fallback' => false,
                ],
                'BATCH-MISSING' => [
                    'found' => false,
                    'item_code' => 'BATCH-MISSING',
                ],
            ],
        ]);
    }

    public function test_price_lookup_falls_back_to_union_or_union_special_when_missing_in_requested_list(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        // Item 1 is in "Price List"
        $item1 = Item::create([
            'item_code' => 'PART-NORMAL',
            'description' => 'Normal Part',
        ]);
        ItemPrice::create([
            'item_id' => $item1->id,
            'item_code' => 'PART-NORMAL',
            'price_list' => 'Price List',
            'currency' => 'USD',
            'price_label' => 'USD 30%',
            'price' => 50.00,
        ]);

        // Item 2 is NOT in "Price List", only in "Union Special"
        $item2 = Item::create([
            'item_code' => 'PART-UNION-ONLY',
            'description' => 'Special Union Part',
        ]);
        ItemPrice::create([
            'item_id' => $item2->id,
            'item_code' => 'PART-UNION-ONLY',
            'price_list' => 'Union Special',
            'currency' => 'USD',
            'price_label' => 'USD 30%',
            'price' => 99.00,
        ]);

        // 1. Single lookup for PART-UNION-ONLY requesting "Price List" should fall back to Union Special
        $singleRes = $this->actingAs($user)->getJson(route('api.price-items.lookup', [
            'item_code' => 'PART-UNION-ONLY',
            'price_list' => 'Price List',
            'price_label' => 'USD 30%',
            'currency' => 'USD',
        ]));

        $singleRes->assertOk();
        $singleRes->assertJson([
            'found' => true,
            'item_code' => 'PART-UNION-ONLY',
            'unit_price' => 99.0,
            'price_list' => 'Union Special',
            'is_fallback' => true,
        ]);

        // 2. Batch lookup with both items
        $batchRes = $this->actingAs($user)->postJson(route('api.price-items.batch-lookup'), [
            'item_codes' => ['PART-NORMAL', 'PART-UNION-ONLY'],
            'price_list' => 'Price List',
            'price_label' => 'USD 30%',
            'currency' => 'USD',
        ]);

        $batchRes->assertOk();
        $batchRes->assertJson([
            'results' => [
                'PART-NORMAL' => [
                    'found' => true,
                    'unit_price' => 50.0,
                    'price_list' => 'Price List',
                    'is_fallback' => false,
                ],
                'PART-UNION-ONLY' => [
                    'found' => true,
                    'unit_price' => 99.0,
                    'price_list' => 'Union Special',
                    'is_fallback' => true,
                ],
            ],
        ]);
    }

    public function test_can_import_excel_columns_with_optional_net_weights(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $codes = "WT-101\nWT-102";
        $descriptions = "Heavy Flange\nLight Gasket";
        $prices = "150.00\n25.00";
        $weights = "12.450\n0.350";

        $response = $this->actingAs($user)->post(route('price-tracker.import.store'), [
            'price_list_select' => 'Price List',
            'currency' => 'AED',
            'price_label_select' => 'AED 30%',
            'item_codes' => $codes,
            'descriptions' => $descriptions,
            'prices' => $prices,
            'weights' => $weights,
        ]);

        $response->assertRedirect(route('price-tracker.index'));

        $this->assertDatabaseHas('items', [
            'item_code' => 'WT-101',
            'net_weight' => 12.450,
        ]);
        $this->assertDatabaseHas('items', [
            'item_code' => 'WT-102',
            'net_weight' => 0.350,
        ]);
    }

    public function test_user_can_update_item_weight_via_patch(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $item = Item::create([
            'item_code' => 'PUMP-TEST',
            'description' => 'Test Pump',
            'net_weight' => 5.200,
        ]);

        $response = $this->actingAs($user)->patchJson(route('price-tracker.items.update-weight', $item), [
            'net_weight' => 8.750,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'item_code' => 'PUMP-TEST',
            'net_weight' => 8.75,
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'net_weight' => 8.750,
        ]);
    }

    public function test_price_lookup_and_batch_lookup_return_unit_weight(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $item = Item::create([
            'item_code' => 'VALVE-WT',
            'description' => 'Weight Valve',
            'net_weight' => 4.250,
        ]);
        ItemPrice::create([
            'item_id' => $item->id,
            'item_code' => 'VALVE-WT',
            'price_list' => 'Price List',
            'currency' => 'AED',
            'price_label' => 'AED 30%',
            'price' => 200.00,
        ]);

        // Single lookup
        $singleRes = $this->actingAs($user)->getJson(route('api.price-items.lookup', [
            'item_code' => 'VALVE-WT',
            'currency' => 'AED',
        ]));
        $singleRes->assertOk();
        $singleRes->assertJson([
            'found' => true,
            'item_code' => 'VALVE-WT',
            'unit_weight' => 4.25,
        ]);

        // Batch lookup
        $batchRes = $this->actingAs($user)->postJson(route('api.price-items.batch-lookup'), [
            'item_codes' => ['VALVE-WT'],
            'currency' => 'AED',
        ]);
        $batchRes->assertOk();
        $batchRes->assertJson([
            'results' => [
                'VALVE-WT' => [
                    'found' => true,
                    'unit_weight' => 4.25,
                ],
            ],
        ]);
    }
}
