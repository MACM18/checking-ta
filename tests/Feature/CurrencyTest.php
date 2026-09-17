<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\User;
use App\Services\CurrencyRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_currencies_are_seeded(): void
    {
        $this->assertDatabaseHas('currencies', [
            'code' => 'USD',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('currencies', [
            'code' => 'AED',
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('currencies', [
            'code' => 'EUR',
            'is_default' => false,
        ]);
    }

    public function test_currency_rate_service_fetches_and_caches_rates(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'result' => 'success',
                'base_code' => 'USD',
                'rates' => [
                    'USD' => 1.0,
                    'AED' => 3.6725,
                    'EUR' => 0.92,
                    'GBP' => 0.78,
                ],
            ], 200),
        ]);

        $service = app(CurrencyRateService::class);
        $rate = $service->getRateForCurrency('EUR');

        $this->assertEquals(0.92, $rate);

        // Convert amount
        $eurAmount = $service->convert(100, 'USD', 'EUR');
        $this->assertEquals(92.00, $eurAmount);
    }

    public function test_api_currencies_endpoint_returns_active_currencies(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->getJson(route('api.currencies.index'));

        $response->assertOk();
        $response->assertJsonStructure([
            'currencies' => [
                '*' => ['code', 'name', 'symbol', 'exchange_rate', 'is_default'],
            ],
        ]);
    }

    public function test_api_currencies_rate_endpoint_returns_rate(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->getJson(route('api.currencies.rate', ['from' => 'USD', 'to' => 'EUR']));

        $response->assertOk();
        $response->assertJsonStructure(['from', 'to', 'rate', 'source']);
        $this->assertEquals('USD', $response->json('from'));
        $this->assertEquals('EUR', $response->json('to'));
    }

    public function test_user_can_add_custom_currency_in_price_tracker(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'result' => 'success',
                'base_code' => 'USD',
                'rates' => [
                    'USD' => 1.0,
                    'GBP' => 0.79,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('price-tracker.currencies.store'), [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
        ]);

        $response->assertRedirect(route('price-tracker.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('currencies', [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
            'exchange_rate' => 0.79,
        ]);
    }

    public function test_user_can_sync_all_active_currency_rates(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'result' => 'success',
                'base_code' => 'USD',
                'rates' => [
                    'USD' => 1.0,
                    'AED' => 3.6725,
                    'EUR' => 0.95,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('price-tracker.currencies.sync'));

        $response->assertRedirect(route('price-tracker.index'));
        $response->assertSessionHas('success');

        $eur = Currency::where('code', 'EUR')->first();
        $this->assertEquals(0.95, (float) $eur->exchange_rate);
    }

    public function test_base_currencies_cannot_be_deleted_but_custom_ones_can(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $usd = Currency::where('code', 'USD')->first();
        $deleteUsd = $this->actingAs($user)->delete(route('price-tracker.currencies.destroy', $usd));
        $deleteUsd->assertSessionHas('error');
        $this->assertDatabaseHas('currencies', ['code' => 'USD']);

        $custom = Currency::create([
            'code' => 'CAD',
            'name' => 'Canadian Dollar',
            'symbol' => 'CA$',
            'exchange_rate' => 1.35,
            'is_active' => true,
        ]);

        $deleteCustom = $this->actingAs($user)->delete(route('price-tracker.currencies.destroy', $custom));
        $deleteCustom->assertSessionHas('success');
        $this->assertDatabaseMissing('currencies', ['code' => 'CAD']);
    }

    public function test_document_can_be_created_with_eur_currency(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'document_type' => 'proforma_invoice',
            'document_number' => 'PI-EUR-001',
            'company_name' => 'European Partner GmbH',
            'country' => 'Germany',
            'currency' => 'EUR',
            'document_date' => now()->toDateString(),
            'status' => 'active',
            'items' => [
                [
                    'item_code' => 'SKU-EUR-1',
                    'description' => 'German Precision Gear',
                    'unit_amount' => 5,
                    'unit_price' => 85.00,
                    'total_amount' => 425.00,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'document_number' => 'PI-EUR-001',
            'currency' => 'EUR',
            'company_name' => 'European Partner GmbH',
        ]);
    }

    public function test_shipment_order_can_be_created_with_eur_currency(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->post(route('shipment-orders.store'), [
            'order_number' => 'SO-EUR-001',
            'company_name' => 'European Partner GmbH',
            'country' => 'Germany',
            'currency' => 'EUR',
            'payment_status' => 'pending',
            'carrier_method' => 'dhl',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shipment_orders', [
            'order_number' => 'SO-EUR-001',
            'currency' => 'EUR',
        ]);
    }

    public function test_price_items_api_maps_additional_currencies_to_usd_base(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $item = Item::create([
            'item_code' => 'CAT-100',
            'description' => 'Universal Coupling Base',
            'net_weight' => 1.25,
        ]);

        ItemPrice::create([
            'item_id' => $item->id,
            'item_code' => 'CAT-100',
            'price_list' => 'Price List',
            'currency' => 'USD',
            'price_label' => 'USD 30%',
            'price' => 150.00,
        ]);

        // Requesting lookup with currency=EUR should find USD catalog price as base
        $response = $this->actingAs($user)->getJson(route('api.price-items.lookup', [
            'item_code' => 'CAT-100',
            'price_label' => 'USD 30%',
            'currency' => 'EUR',
        ]));

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'item_code' => 'CAT-100',
            'unit_price' => 150.00,
        ]);
    }
}
