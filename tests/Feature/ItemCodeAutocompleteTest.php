<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemCodeAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_code_search_finds_matching_digits_anywhere_in_the_code(): void
    {
        foreach (['112245', '112246', '011224', '1011224', '2112245'] as $itemCode) {
            $user = User::factory()->create(['role' => 'editor']);
            Item::create(['item_code' => $itemCode]);
        }

        $response = $this->actingAs($user)->getJson('/api/price-items/search?q=11224');

        $response->assertOk();

        $suggestedCodes = collect($response->json('items'))->pluck('item_code')->all();

        $this->assertEqualsCanonicalizing(
            ['112245', '112246', '011224', '1011224', '2112245'],
            $suggestedCodes,
        );
    }
}
