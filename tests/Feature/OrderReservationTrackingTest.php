<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\OrderReservation;
use App\Models\OrderReservationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReservationTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_reserve_document_automatically_initializes_order_reservation_and_items(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'document_number' => 'E26211R',
            'document_type' => 'reserve',
            'company_name' => 'Apex Mechanical Solutions',
            'country' => 'Oman',
            'document_date' => '2026-09-04',
            'currency' => 'USD',
            'status' => 'draft',
            'items' => [
                [
                    'item_code' => 'VALVE-A1',
                    'description' => 'Control Valve 50mm',
                    'unit_amount' => 10,
                    'unit_price' => 0,
                    'total_amount' => 0,
                    'unit_weight' => 2.5,
                    'total_weight' => 25.0,
                ],
                [
                    'item_code' => 'GASKET-B2',
                    'description' => 'Flange Gasket Spiral',
                    'unit_amount' => 20,
                    'unit_price' => 0,
                    'total_amount' => 0,
                    'unit_weight' => 0.5,
                    'total_weight' => 10.0,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('order_reservations', [
            'reserve_document_number' => 'E26211R',
            'company_name' => 'Apex Mechanical Solutions',
            'status' => OrderReservation::STATUS_PENDING_CHECK,
            'total_requested_qty' => 30.000,
            'total_items_count' => 2,
            'is_legacy_record' => false,
        ]);

        $this->assertDatabaseHas('order_reservation_items', [
            'item_code' => 'VALVE-A1',
            'requested_qty' => 10.000,
            'status' => OrderReservationItem::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('order_reservation_items', [
            'item_code' => 'GASKET-B2',
            'requested_qty' => 20.000,
            'status' => OrderReservationItem::STATUS_PENDING,
        ]);
    }

    public function test_viewing_reserve_document_lazy_initializes_order_reservation(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'E26999R',
            'document_type' => 'reserve',
            'company_name' => 'Sharjah Tech Marine',
            'country' => 'UAE',
            'document_date' => '2026-09-04',
            'currency' => 'USD',
            'status' => 'draft',
            'current_version' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentItem::create([
            'document_id' => $doc->id,
            'item_code' => 'PUMP-700',
            'description' => 'Submersible Pump',
            'unit_amount' => 4,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        $this->assertDatabaseMissing('order_reservations', [
            'reserve_document_number' => 'E26999R',
        ]);

        $response = $this->actingAs($user)->get(route('documents.show', $doc));
        $response->assertOk();
        $response->assertSee('Warehouse Stock Status');

        $this->assertDatabaseHas('order_reservations', [
            'reserve_document_number' => 'E26999R',
            'total_requested_qty' => 4.000,
            'status' => OrderReservation::STATUS_PENDING_CHECK,
        ]);
    }

    public function test_can_confirm_all_items_available_in_warehouse(): void
    {
        $user = User::factory()->create(['role' => 'editor', 'name' => 'Rashid Al Nuaimi']);

        $reservation = OrderReservation::create([
            'reservation_number' => 'RES-E26211R',
            'reserve_document_number' => 'E26211R',
            'company_name' => 'Gulf Apex',
            'status' => OrderReservation::STATUS_PENDING_CHECK,
            'total_requested_qty' => 15,
            'total_items_count' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $item = $reservation->items()->create([
            'item_code' => 'BEARING-6204',
            'requested_qty' => 15,
            'available_qty' => 0,
            'short_qty' => 0,
            'status' => OrderReservationItem::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->post(route('order-reservations.confirm-all', $reservation), [
            'warehouse_location' => 'Zone 4 / Shelf 12',
            'warehouse_notes' => 'All bearings physically inspected and boxed.',
        ]);

        $response->assertRedirect(route('order-reservations.show', $reservation));

        $reservation->refresh();
        $this->assertEquals(OrderReservation::STATUS_ALL_AVAILABLE, $reservation->status);
        $this->assertEquals(15.0, (float) $reservation->total_available_qty);
        $this->assertEquals(0.0, (float) $reservation->total_short_qty);
        $this->assertEquals(0, $reservation->short_items_count);
        $this->assertNotNull($reservation->warehouse_confirmed_at);
        $this->assertEquals($user->id, $reservation->warehouse_confirmed_by);

        $item->refresh();
        $this->assertEquals(15.0, (float) $item->available_qty);
        $this->assertEquals(0.0, (float) $item->short_qty);
        $this->assertEquals(OrderReservationItem::STATUS_AVAILABLE, $item->status);
    }

    public function test_confirm_all_returns_json_response_when_requested_via_ajax(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $reservation = OrderReservation::create([
            'reservation_number' => 'RES-E26222R',
            'reserve_document_number' => 'E26222R',
            'company_name' => 'Gulf Drilling Tech',
            'status' => OrderReservation::STATUS_PENDING_CHECK,
            'total_requested_qty' => 8,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $item = $reservation->items()->create([
            'item_code' => 'DRILL-BIT-40',
            'requested_qty' => 8,
            'available_qty' => 0,
            'short_qty' => 0,
            'status' => OrderReservationItem::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->postJson(route('order-reservations.confirm-all', $reservation), [
            'warehouse_location' => 'Main Rack 1',
            'warehouse_notes' => 'Checked and verified.',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => OrderReservation::STATUS_ALL_AVAILABLE,
            'status_label' => 'All Items Available',
            'total_short_qty' => 0,
            'short_items_count' => 0,
            'warehouse_confirmed_by' => $user->name,
        ]);

        $reservation->refresh();
        $this->assertEquals(OrderReservation::STATUS_ALL_AVAILABLE, $reservation->status);
        $this->assertEquals(8.0, (float) $reservation->total_available_qty);
        $this->assertEquals(0.0, (float) $reservation->total_short_qty);
    }

    public function test_can_update_item_quantities_and_record_shortages(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $reservation = OrderReservation::create([
            'reservation_number' => 'RES-E26300R',
            'reserve_document_number' => 'E26300R',
            'company_name' => 'Dubai Marine',
            'status' => OrderReservation::STATUS_PENDING_CHECK,
            'total_requested_qty' => 20,
            'total_items_count' => 2,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $item1 = $reservation->items()->create([
            'item_code' => 'BELT-100',
            'requested_qty' => 10,
            'available_qty' => 0,
            'short_qty' => 0,
            'status' => OrderReservationItem::STATUS_PENDING,
        ]);

        $item2 = $reservation->items()->create([
            'item_code' => 'SEAL-200',
            'requested_qty' => 10,
            'available_qty' => 0,
            'short_qty' => 0,
            'status' => OrderReservationItem::STATUS_PENDING,
        ]);

        // item1 is fully available (10), item2 is short (only 6 available, 4 short)
        $response = $this->actingAs($user)->post(route('order-reservations.update-items', $reservation), [
            'warehouse_location' => 'Bin Bay 2',
            'warehouse_notes' => 'Awaiting shipment for remaining 4 seals.',
            'items' => [
                $item1->id => [
                    'available_qty' => 10,
                    'bin_location' => 'Bay 2-A',
                    'shortage_reason' => null,
                ],
                $item2->id => [
                    'available_qty' => 6,
                    'bin_location' => 'Bay 2-B',
                    'shortage_reason' => 'Damaged batch, 4 pieces rejected.',
                ],
            ],
        ]);

        $response->assertRedirect(route('order-reservations.show', $reservation));

        $reservation->refresh();
        $this->assertEquals(OrderReservation::STATUS_HAS_SHORTAGE, $reservation->status);
        $this->assertEquals(16.0, (float) $reservation->total_available_qty);
        $this->assertEquals(4.0, (float) $reservation->total_short_qty);
        $this->assertEquals(1, $reservation->short_items_count);

        $item1->refresh();
        $this->assertEquals(OrderReservationItem::STATUS_AVAILABLE, $item1->status);
        $this->assertEquals(0.0, (float) $item1->short_qty);

        $item2->refresh();
        $this->assertEquals(OrderReservationItem::STATUS_SHORT, $item2->status);
        $this->assertEquals(4.0, (float) $item2->short_qty);
        $this->assertEquals('Damaged batch, 4 pieces rejected.', $item2->shortage_reason);
    }

    public function test_can_record_old_external_reserve_document_with_missing_parts(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->post(route('order-reservations.store'), [
            'reserve_document_number' => 'E24810R',
            'company_name' => 'Historical Petroleum Services',
            'country' => 'Saudi Arabia',
            'reservation_date' => '2025-06-15',
            'warehouse_location' => 'Archive Bin 8',
            'notes' => 'Transferred from paper ledger',
            'items' => [
                [
                    'item_code' => 'OLD-PART-01',
                    'description' => 'Original Gasket 1980 style',
                    'requested_qty' => 5,
                    'available_qty' => 2,
                    'bin_location' => 'Bin 8-A',
                    'shortage_reason' => '3 pieces missing from legacy stock',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('order_reservations', [
            'reserve_document_number' => 'E24810R',
            'company_name' => 'Historical Petroleum Services',
            'is_legacy_record' => true,
            'document_id' => null,
            'status' => OrderReservation::STATUS_HAS_SHORTAGE,
            'total_short_qty' => 3.000,
        ]);

        $this->assertDatabaseHas('order_reservation_items', [
            'item_code' => 'OLD-PART-01',
            'requested_qty' => 5.000,
            'available_qty' => 2.000,
            'short_qty' => 3.000,
            'status' => OrderReservationItem::STATUS_SHORT,
        ]);
    }

    public function test_can_add_custom_missing_part_and_view_printable_shortage_report(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $reservation = OrderReservation::create([
            'reservation_number' => 'RES-E25555R',
            'reserve_document_number' => 'E25555R',
            'company_name' => 'Ras Al Khaimah Quarry',
            'status' => OrderReservation::STATUS_PENDING_CHECK,
            'total_requested_qty' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Add short item
        $response = $this->actingAs($user)->post(route('order-reservations.add-short-item', $reservation), [
            'item_code' => 'HEAVY-COUPLING-88',
            'description' => 'Cast Iron Coupling',
            'requested_qty' => 8,
            'available_qty' => 3,
            'bin_location' => 'Yard 2',
            'shortage_reason' => 'Supplier delayed delivery',
        ]);

        $response->assertRedirect(route('order-reservations.show', $reservation));

        $reservation->refresh();
        $this->assertEquals(OrderReservation::STATUS_HAS_SHORTAGE, $reservation->status);
        $this->assertEquals(5.0, (float) $reservation->total_short_qty);

        // View printable report
        $printResponse = $this->actingAs($user)->get(route('order-reservations.print-shortage', $reservation));
        $printResponse->assertOk();
        $printResponse->assertSee('WAREHOUSE SHORTAGE REPORT');
        $printResponse->assertSee('HEAVY-COUPLING-88');
        $printResponse->assertSee('-5.00');
        $printResponse->assertSee('Supplier delayed delivery');
    }
}
