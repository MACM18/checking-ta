<?php

namespace Tests\Feature;

use App\Models\ShipmentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentOrderFilterAndReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_shipment_order_with_older_document_reference_and_category(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->post(route('shipment-orders.store'), [
            'order_number' => 'ORD-2026-TEST1',
            'document_reference' => 'OLD-PI-2024-88',
            'proforma_invoice_no' => 'E24190',
            'company_name' => 'Legacy Trading Co',
            'country' => 'Saudi Arabia',
            'shipment_category' => 'Air Freight',
            'customer_po_number' => 'PO-LEGACY-101',
            'payment_status' => 'advance_received',
            'payment_amount' => 5000,
            'currency' => 'USD',
            'linked_invoice_no' => 'N24190',
            'carrier_method' => 'DHL Express',
        ]);

        $response->assertRedirect();

        $order = ShipmentOrder::where('order_number', 'ORD-2026-TEST1')->first();
        $this->assertNotNull($order);
        $this->assertEquals('OLD-PI-2024-88', $order->document_reference);
        $this->assertEquals('E24190', $order->proforma_invoice_no);
        $this->assertEquals('Air Freight', $order->shipment_category);
        $this->assertEquals('N24190', $order->linked_invoice_no);

        // Stage 1 (PI Sent), Stage 2 (PO Received), and Stage 5 (Invoice/Packing) should be auto-completed
        $this->assertTrue($order->milestones()->where('stage_code', 'pi_sent')->first()->is_completed);
        $this->assertTrue($order->milestones()->where('stage_code', 'po_received')->first()->is_completed);
        $this->assertTrue($order->milestones()->where('stage_code', 'invoice_packing_list')->first()->is_completed);
    }

    public function test_can_filter_shipment_orders_by_company_and_category(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        ShipmentOrder::create([
            'order_number' => 'ORD-COMP-A',
            'company_name' => 'Alpha Dynamics',
            'country' => 'UAE',
            'shipment_category' => 'Air Freight',
            'currency' => 'USD',
            'payment_status' => 'pending',
            'created_by' => $user->id,
        ]);

        ShipmentOrder::create([
            'order_number' => 'ORD-COMP-B',
            'company_name' => 'Beta Logistics',
            'country' => 'Oman',
            'shipment_category' => 'Sea Freight',
            'currency' => 'USD',
            'payment_status' => 'pending',
            'created_by' => $user->id,
        ]);

        // Filter by Company Alpha Dynamics
        $resCompany = $this->actingAs($user)->get(route('shipment-orders.index', ['company_name' => 'Alpha Dynamics']));
        $resCompany->assertOk();
        $resCompany->assertSee('ORD-COMP-A');
        $resCompany->assertDontSee('ORD-COMP-B');

        // Filter by Category Sea Freight
        $resCategory = $this->actingAs($user)->get(route('shipment-orders.index', ['category' => 'Sea Freight']));
        $resCategory->assertOk();
        $resCategory->assertSee('ORD-COMP-B');
        $resCategory->assertDontSee('ORD-COMP-A');
    }
}
