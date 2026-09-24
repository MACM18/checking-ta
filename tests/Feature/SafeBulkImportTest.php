<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SafeBulkImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_import_keeps_existing_values_by_default_and_requires_confirmation_to_replace(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $item = Item::create(['item_code' => 'KEEP-001', 'description' => 'Original', 'net_weight' => 2.5]);
        ItemPrice::create([
            'item_id' => $item->id, 'item_code' => $item->item_code, 'price_list' => 'Price List',
            'currency' => 'USD', 'price_label' => 'USD 30%', 'price' => 100,
        ]);
        $payload = [
            'price_list_select' => 'Price List', 'currency' => 'USD', 'price_label_select' => 'USD 30%',
            'item_codes' => "KEEP-001\nNEW-002", 'descriptions' => "Changed\nNew item",
            'prices' => "900\n250", 'weights' => "9\n3",
        ];

        $this->actingAs($user)->post(route('price-tracker.import.store'), $payload)->assertRedirect(route('price-tracker.index'));
        $this->assertDatabaseHas('items', ['item_code' => 'KEEP-001', 'description' => 'Original', 'net_weight' => 2.5]);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'KEEP-001', 'price' => 100]);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'NEW-002', 'price' => 250]);

        $this->post(route('price-tracker.import.store'), $payload + ['import_mode' => 'replace'])->assertSessionHasErrors('confirm_bulk_replace');
        $this->assertDatabaseHas('item_prices', ['item_code' => 'KEEP-001', 'price' => 100]);

        $this->post(route('price-tracker.import.store'), $payload + ['import_mode' => 'replace', 'confirm_bulk_replace' => 1])->assertRedirect(route('price-tracker.index'));
        $this->assertDatabaseHas('items', ['item_code' => 'KEEP-001', 'description' => 'Changed', 'net_weight' => 9]);
        $this->assertDatabaseHas('item_prices', ['item_code' => 'KEEP-001', 'price' => 900]);
    }
}
