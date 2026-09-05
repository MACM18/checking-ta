<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\ShipmentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ShipmentOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->editor = User::factory()->create(['role' => 'editor']);
    }

    public function test_can_create_shipment_order_and_initializes_milestones(): void
    {
        $response = $this->actingAs($this->editor)->post(route('shipment-orders.store'), [
            'order_number' => 'ORD-TEST-001',
            'company_name' => 'Acme Trading LLC',
            'country' => 'United States',
            'currency' => 'USD',
            'customer_po_number' => 'PO-998811',
            'customer_po_date' => '2026-09-01',
            'customer_po_notes' => 'Ship via air courier only',
            'payment_status' => 'advance_received',
            'carrier_method' => 'DHL Express',
        ]);

        $order = ShipmentOrder::where('order_number', 'ORD-TEST-001')->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('shipment-orders.show', $order));

        // Must have all 9 default milestones (including payment submitted & confirmed)
        $this->assertCount(9, $order->milestones);

        // Since customer_po_number was provided, stage 2 (po_received) should be completed
        $poStage = $order->milestones->firstWhere('stage_code', 'po_received');
        $this->assertTrue($poStage->is_completed);
        $this->assertEquals('PO-998811', $poStage->reference_no);
    }

    public function test_creating_order_with_source_proforma_invoice_auto_completes_pi_milestone(): void
    {
        $piDoc = Document::create([
            'document_number' => 'E26211',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Global Logistics Inc',
            'country' => 'Germany',
            'currency' => 'USD',
            'document_date' => '2026-09-03',
            'created_by' => $this->editor->id,
            'updated_by' => $this->editor->id,
            'current_version' => 1,
            'final_total' => 1200.0,
        ]);

        $this->actingAs($this->editor)->post(route('shipment-orders.store'), [
            'order_number' => 'ORD-PI-002',
            'document_id' => $piDoc->id,
            'company_name' => 'Global Logistics Inc',
            'country' => 'Germany',
            'currency' => 'USD',
            'payment_status' => 'pending',
        ]);

        $order = ShipmentOrder::where('order_number', 'ORD-PI-002')->first();
        $this->assertNotNull($order);
        $this->assertEquals($piDoc->id, $order->document_id);

        // Stage 1 (pi_sent) must be completed with PI document reference
        $piStage = $order->milestones->firstWhere('stage_code', 'pi_sent');
        $this->assertTrue($piStage->is_completed);
        $this->assertEquals('E26211', $piStage->reference_no);
    }

    public function test_can_toggle_milestone_via_ajax(): void
    {
        $order = ShipmentOrder::create([
            'order_number' => 'ORD-TOGGLE-01',
            'company_name' => 'Test Corp',
            'country' => 'Japan',
            'currency' => 'USD',
            'created_by' => $this->editor->id,
        ]);

        $milestone = $order->milestones()->create([
            'stage_code' => 'payment_confirmed',
            'stage_name' => 'Payment Received / Verified',
            'is_completed' => false,
            'sort_order' => 3,
        ]);

        $this->assertFalse($milestone->is_completed);

        // Toggle ON
        $response = $this->actingAs($this->editor)->postJson(
            route('shipment-orders.milestones.toggle', [$order, $milestone]),
            [
                'reference_no' => 'SWIFT-9918237',
                'notes' => 'Received via Citibank TT',
            ]
        );

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_completed' => true,
        ]);

        $milestone->refresh();
        $this->assertTrue($milestone->is_completed);
        $this->assertEquals('SWIFT-9918237', $milestone->reference_no);
        $this->assertEquals($this->editor->id, $milestone->completed_by);
    }

    public function test_document_creation_with_packages_and_carrier_rate_per_kg(): void
    {
        $response = $this->actingAs($this->editor)->post(route('documents.store'), [
            'document_number' => 'E26215',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Cylinder & Box Traders',
            'country' => 'UAE',
            'currency' => 'USD',
            'document_date' => '2026-09-03',
            'total_net_weight' => 50.0,
            'total_gross_weight' => 55.0,
            'final_total' => 2500.0,
            'items' => [
                [
                    'item_code' => 'ITM-01',
                    'description' => 'Industrial Parts',
                    'unit_amount' => 10,
                    'unit_price' => 250,
                    'total_amount' => 2500,
                ],
            ],
            'packages' => [
                // Standard rectangular box
                [
                    'package_type' => 'Carton',
                    'dimension_type' => 'standard',
                    'length_cm' => 50,
                    'width_cm' => 40,
                    'height_cm' => 30,
                    'quantity' => 2,
                    'gross_weight_per_pkg_kg' => 15.0,
                ],
                // Cylindrical drum with diameter
                [
                    'package_type' => 'Drum',
                    'dimension_type' => 'diameter',
                    'diameter_cm' => 40,
                    'height_cm' => 60,
                    'quantity' => 1,
                    'gross_weight_per_pkg_kg' => 25.0,
                ],
            ],
            'shipment_costs' => [
                'dhl' => [
                    'rate_per_kg' => 6.50,
                    'added_amount' => 50.0,
                    'given_amount' => 350.0,
                ],
            ],
        ]);

        $doc = Document::where('document_number', 'E26215')->first();
        $this->assertNotNull($doc);
        $response->assertRedirect(route('documents.show', $doc));

        // Verify packages saved
        $this->assertCount(2, $doc->packages);

        $box = $doc->packages->firstWhere('package_type', 'Carton');
        $this->assertEquals('standard', $box->dimension_type);
        $this->assertEquals(24.0, (float) $box->volumetric_weight_kg); // (50*40*30/5000)*2 = 24

        $drum = $doc->packages->firstWhere('package_type', 'Drum');
        $this->assertEquals('diameter', $drum->dimension_type);
        $this->assertEquals(40.0, (float) $drum->diameter_cm);
        $this->assertEquals(19.2, (float) $drum->volumetric_weight_kg); // (40*40*60/5000)*1 = 19.2

        // Verify shipment cost rate per kg saved
        $dhlCost = $doc->shipmentCosts->firstWhere('method', 'dhl');
        $this->assertNotNull($dhlCost);
        $this->assertEquals(6.50, (float) $dhlCost->rate_per_kg);
        $this->assertNotNull($dhlCost->chargeable_weight);
    }

    public function test_index_page_loads_and_renders_safely(): void
    {
        ShipmentOrder::create([
            'order_number' => 'ORD-INDEX-1',
            'company_name' => 'Apex Global Ltd',
            'country' => 'UAE',
            'created_by' => $this->editor->id,
        ]);

        $response = $this->actingAs($this->editor)->get(route('shipment-orders.index'));
        $response->assertOk();
        $response->assertSee('Apex Global Ltd');
    }

    public function test_index_page_handles_corrupted_or_array_cached_companies_without_type_error(): void
    {
        // Simulate cached companies containing arrays or unexpected structures
        Cache::put('shipment_companies_v2', [
            ['company_name' => 'Cached Company Array'],
            'Standard Company String',
        ], 300);

        $response = $this->actingAs($this->editor)->get(route('shipment-orders.index'));
        $response->assertOk();
        $response->assertSee('Cached Company Array');
        $response->assertSee('Standard Company String');
    }

    public function test_index_page_handles_array_query_params_without_error(): void
    {
        $response = $this->actingAs($this->editor)->get(route('shipment-orders.index', [
            'company_name' => ['Unexpected Array'],
            'category' => ['Urgent / Priority'],
            'search' => ['bad_param'],
        ]));

        $response->assertOk();
    }
}
