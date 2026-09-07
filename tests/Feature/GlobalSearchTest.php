<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\OrderReservation;
use App\Models\ShipmentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_global_search(): void
    {
        $response = $this->getJson('/api/global-search?q=test');
        $response->assertStatus(401);
    }

    public function test_empty_query_returns_empty_results(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/global-search?q=');

        $response->assertStatus(200);
        $response->assertJson([
            'query' => '',
            'results' => [],
            'counts' => [
                'all' => 0,
            ],
            'took_ms' => 0,
        ]);
    }

    public function test_admin_searches_across_all_modules(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $doc = Document::create([
            'document_number' => 'INV-2026-999',
            'document_type' => Document::TYPE_INVOICE,
            'company_name' => 'Solaris Global Corp',
            'country' => 'Germany',
            'document_date' => now(),
            'currency' => 'EUR',
            'subtotal' => 4500,
            'final_total' => 4500,
            'current_version' => 1,
            'status' => 'issued',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $shipment = ShipmentOrder::create([
            'order_number' => 'SO-2026-SOLARIS',
            'company_name' => 'Solaris Global Corp',
            'country' => 'Germany',
            'carrier_method' => 'Air Freight',
            'tracking_awb_no' => 'AWB-SOL-777',
            'status' => 'active',
            'current_stage' => 1,
            'created_by' => $admin->id,
        ]);

        $res = OrderReservation::create([
            'reservation_number' => 'RES-SOL-101',
            'reserve_document_number' => 'R-2026-SOL',
            'company_name' => 'Solaris Global Corp',
            'country' => 'Germany',
            'status' => OrderReservation::STATUS_HAS_SHORTAGE,
            'short_items_count' => 2,
            'total_short_qty' => 15,
        ]);

        $item = Item::create([
            'item_code' => 'SOL-PANEL-450W',
            'description' => 'Solaris Monocrystalline 450W Solar Panel',
        ]);

        ItemPrice::create([
            'item_id' => $item->id,
            'item_code' => $item->item_code,
            'price_list' => 'Default',
            'currency' => 'USD',
            'price_label' => 'Standard Rate',
            'price' => 185.50,
        ]);

        // Search for "Solaris"
        $response = $this->actingAs($admin)->getJson('/api/global-search?q=Solaris');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('Solaris', $data['query']);
        $this->assertGreaterThanOrEqual(4, $data['counts']['all']);
        $this->assertEquals(1, $data['counts']['documents']);
        $this->assertEquals(1, $data['counts']['shipment_orders']);
        $this->assertEquals(1, $data['counts']['reservations']);
        $this->assertEquals(1, $data['counts']['items']);

        // Check exact match has higher score
        $docSearch = $this->actingAs($admin)->getJson('/api/global-search?q=INV-2026-999');
        $docSearchData = $docSearch->json();
        $this->assertEquals(100, $docSearchData['results'][0]['score']);
        $this->assertEquals('INV-2026-999', $docSearchData['results'][0]['title']);
    }

    public function test_category_filter_limits_results(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Document::create([
            'document_number' => 'PL-CAT-001',
            'document_type' => Document::TYPE_PACKING_LIST,
            'company_name' => 'Alpha Industrial',
            'country' => 'Japan',
            'document_date' => now(),
            'currency' => 'USD',
            'subtotal' => 0,
            'final_total' => 0,
            'current_version' => 1,
            'status' => 'draft',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        ShipmentOrder::create([
            'order_number' => 'SO-CAT-001',
            'company_name' => 'Alpha Industrial',
            'country' => 'Japan',
            'carrier_method' => 'Sea Freight',
            'tracking_awb_no' => 'BL-ALPHA-99',
            'status' => 'active',
            'current_stage' => 1,
            'created_by' => $admin->id,
        ]);

        // Query with category=documents
        $response = $this->actingAs($admin)->getJson('/api/global-search?q=Alpha&category=documents');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals(1, $data['counts']['documents']);
        $this->assertEquals(0, $data['counts']['shipment_orders']);
        $this->assertCount(1, $data['results']);
        $this->assertEquals('PL-CAT-001', $data['results'][0]['title']);
    }

    public function test_role_permissions_restrict_unauthorized_categories(): void
    {
        // Normal viewer without shipments, reservations, or price tracker permissions
        $user = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'permissions' => [],
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Document::create([
            'document_number' => 'PI-PERM-001',
            'document_type' => Document::TYPE_PROFORMA_INVOICE,
            'company_name' => 'Secure Logistics Corp',
            'country' => 'United States',
            'document_date' => now(),
            'currency' => 'USD',
            'subtotal' => 1000,
            'final_total' => 1000,
            'current_version' => 1,
            'status' => 'draft',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        ShipmentOrder::create([
            'order_number' => 'SO-PERM-001',
            'company_name' => 'Secure Logistics Corp',
            'country' => 'United States',
            'carrier_method' => 'DHL',
            'status' => 'active',
            'current_stage' => 1,
            'created_by' => $admin->id,
        ]);

        OrderReservation::create([
            'reservation_number' => 'RES-PERM-001',
            'reserve_document_number' => 'R-PERM-001',
            'company_name' => 'Secure Logistics Corp',
            'country' => 'United States',
            'status' => OrderReservation::STATUS_PENDING_CHECK,
        ]);

        Item::create([
            'item_code' => 'SECURE-ITEM-1',
            'description' => 'Secure Logistics Device',
        ]);

        $response = $this->actingAs($user)->getJson('/api/global-search?q=Secure');

        $response->assertStatus(200);
        $data = $response->json();

        // User should ONLY see documents
        $this->assertEquals(1, $data['counts']['documents']);
        $this->assertEquals(0, $data['counts']['shipment_orders']);
        $this->assertEquals(0, $data['counts']['reservations']);
        $this->assertEquals(0, $data['counts']['items']);
        $this->assertCount(1, $data['results']);
        $this->assertEquals('PI-PERM-001', $data['results'][0]['title']);
    }
}
