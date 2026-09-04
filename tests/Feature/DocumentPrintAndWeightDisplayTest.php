<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentPackage;
use App\Models\DocumentShipmentCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPrintAndWeightDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_document_printable_page(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'E10001',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Acme International',
            'country' => 'United States',
            'address' => "123 Ocean Blvd\nSuite 400\nMiami, FL",
            'contact_details' => 'Attn: John Doe, +1 555 1234',
            'document_date' => now(),
            'currency' => 'USD',
            'total_net_weight' => 25.5,
            'total_gross_weight' => 30.0,
            'subtotal' => 1200.00,
            'final_total' => 1350.00,
            'current_version' => 1,
            'status' => 'active',
            'notes' => 'Net 30 days payment. TT transfer only.',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentItem::create([
            'document_id' => $doc->id,
            'item_code' => 'VALVE-01',
            'description' => 'Precision Control Valve',
            'unit_amount' => 10,
            'unit_price' => 120.00,
            'total_amount' => 1200.00,
            'unit_weight' => 2.55,
            'total_weight' => 25.5,
        ]);

        DocumentPackage::create([
            'document_id' => $doc->id,
            'package_type' => 'Carton',
            'dimension_type' => 'standard',
            'length_cm' => 40,
            'width_cm' => 30,
            'height_cm' => 25,
            'quantity' => 2,
            'gross_weight_per_pkg_kg' => 15.0,
            'total_gross_weight_kg' => 30.0,
            'volumetric_weight_kg' => 12.0,
            'cbm' => 0.06,
        ]);

        DocumentShipmentCost::create([
            'document_id' => $doc->id,
            'method' => 'dhl',
            'checked_weight' => 30.0,
            'rate_per_kg' => 5.00,
            'system_amount' => 150.00,
            'given_amount' => 150.00,
        ]);

        $response = $this->actingAs($user)->get(route('documents.print', $doc));

        $response->assertStatus(200);
        $response->assertSee('PROFORMA INVOICE');
        $response->assertSee('E10001');
        $response->assertSee('Acme International');
        $response->assertSee('United States');
        $response->assertSee('VALVE-01');
        $response->assertSee('USD 1,350.00');
        $response->assertSee('Shipping & Freight Charges', false);
        $response->assertSee('DHL');
        $response->assertSee('Shipping / Freight:');
        $response->assertSee('Prepared By:');
        $response->assertSee('Print / Save to PDF');
    }

    public function test_packing_list_print_and_show_omit_shipment_costs_and_prices(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $packingList = Document::create([
            'document_number' => 'W20002',
            'document_type' => 'packing_list',
            'company_name' => 'Tokyo Logistics Corp',
            'country' => 'Japan',
            'document_date' => now(),
            'currency' => 'USD',
            'total_net_weight' => 45.000,
            'total_gross_weight' => 52.500,
            'subtotal' => 0,
            'final_total' => 0,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentItem::create([
            'document_id' => $packingList->id,
            'item_code' => 'STEEL-ROD',
            'description' => 'Industrial Steel Rods',
            'unit_amount' => 15,
            'unit_price' => 0,
            'total_amount' => 0,
            'unit_weight' => 3.000,
            'total_weight' => 45.000,
        ]);

        // Print page check
        $printResponse = $this->actingAs($user)->get(route('documents.print', $packingList));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('PACKING LIST');
        $printResponse->assertSee('Tokyo Logistics Corp');
        $printResponse->assertSee('Total Gross Weight:');
        $printResponse->assertSee('52.500 kg');
        $printResponse->assertSee('45.000 kg');
        $printResponse->assertDontSee('Shipping & Freight Charges', false);
        $printResponse->assertDontSee('Shipment Method Costs');
        $printResponse->assertDontSee('Final Total Amount');

        // Show page check
        $showResponse = $this->actingAs($user)->get(route('documents.show', $packingList));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Packing List (Weights Only)');
        $showResponse->assertSee('52.500 kg');
        $showResponse->assertSee('45.000 kg');
        $showResponse->assertDontSee('Shipment Method Costs Audit & Rate / KG', false);
    }

    public function test_reserve_print_and_show_displays_net_weight_only(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $reserve = Document::create([
            'document_number' => 'E9999R',
            'document_type' => 'reserve',
            'company_name' => 'Dubai Marina Warehouse',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'AED',
            'total_net_weight' => 18.250,
            'total_gross_weight' => 20.000,
            'subtotal' => 0,
            'final_total' => 0,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentItem::create([
            'document_id' => $reserve->id,
            'item_code' => 'PUMP-SEAL',
            'description' => 'Rubber Gasket & Seal',
            'unit_amount' => 50,
            'unit_price' => 0,
            'total_amount' => 0,
            'unit_weight' => 0.365,
            'total_weight' => 18.250,
        ]);

        $printResponse = $this->actingAs($user)->get(route('documents.print', $reserve));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('WAREHOUSE RESERVATION');
        $printResponse->assertSee('Warehouse Net Weight');
        $printResponse->assertSee('18.250 kg');
        $printResponse->assertDontSee('Shipping & Freight Charges', false);

        $showResponse = $this->actingAs($user)->get(route('documents.show', $reserve));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Warehouse Reserve (Net Weight Only)');
        $showResponse->assertSee('18.250 kg');
        $showResponse->assertDontSee('Shipment Method Costs Audit & Rate / KG', false);
    }

    public function test_documents_index_shows_gw_and_nw_for_packing_list_and_nw_for_reserve(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $packingList = Document::create([
            'document_number' => 'W30003',
            'document_type' => 'packing_list',
            'company_name' => 'Global Logistics PL',
            'country' => 'Germany',
            'document_date' => now(),
            'currency' => 'EUR',
            'total_net_weight' => 60.000,
            'total_gross_weight' => 70.500,
            'subtotal' => 0,
            'final_total' => 0,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $reserve = Document::create([
            'document_number' => 'E7777R',
            'document_type' => 'reserve',
            'company_name' => 'Stock Reserve Depot',
            'country' => 'Oman',
            'document_date' => now(),
            'currency' => 'USD',
            'total_net_weight' => 33.300,
            'subtotal' => 0,
            'final_total' => 0,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $commercial = Document::create([
            'document_number' => 'E88881',
            'document_type' => 'invoice',
            'company_name' => 'Commercial Buyer LLC',
            'country' => 'Qatar',
            'document_date' => now(),
            'currency' => 'USD',
            'subtotal' => 5000.00,
            'final_total' => 5250.00,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertStatus(200);
        $response->assertSee('Total / Weight');

        // Packing list shows GW and NW
        $response->assertSee('GW:');
        $response->assertSee('70.500 kg');
        $response->assertSee('NW:');
        $response->assertSee('60.000 kg');

        // Reserve shows NW
        $response->assertSee('33.300 kg');

        // Commercial shows money value
        $response->assertSee('USD 5,250.00');
    }

    public function test_creating_document_with_applied_carrier_freight_adds_to_final_total(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $payload = [
            'document_number' => 'E55001',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Atlas Heavy Industries',
            'country' => 'Singapore',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'items' => [
                [
                    'item_code' => 'GEAR-01',
                    'description' => 'Transmission Gear',
                    'unit_amount' => 4,
                    'unit_price' => 250.00, // 4 * 250 = 1000.00 subtotal
                ],
            ],
            'selected_shipment_method' => 'air_freight',
            'shipment_costs' => [
                'air_freight' => [
                    'checked_weight' => 25.0,
                    'rate_per_kg' => 8.00,
                    'system_amount' => 200.00,
                    'given_amount' => 200.00,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('documents.store'), $payload);
        $response->assertRedirect();

        $document = Document::where('document_number', 'E55001')->first();
        $this->assertNotNull($document);
        $this->assertEquals(1000.00, (float) $document->subtotal);
        // Subtotal (1000) + Air Freight (200) = 1200
        $this->assertEquals(1200.00, (float) $document->final_total);
    }

    public function test_print_view_omits_macm_branding_and_uses_sanitized_officer_title(): void
    {
        $user = User::factory()->create([
            'name' => 'MACM',
            'role' => 'admin',
        ]);

        $doc = Document::create([
            'document_number' => 'E77001',
            'document_type' => 'invoice',
            'company_name' => 'Gulf Tech Marine',
            'country' => 'Oman',
            'document_date' => now(),
            'currency' => 'USD',
            'final_total' => 2500.00,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('documents.print', $doc));
        $response->assertOk();

        // Must NOT render MACM as creator / prepared by
        $response->assertDontSee('Prepared By:</p>
                <div class="border-b border-gray-400 pb-1">
                    <span class="font-semibold text-gray-900">MACM</span>', false);
        $response->assertDontSee('Generated By: MACM');
        $response->assertSee('Authorized Officer');
    }

    public function test_sidebar_navigation_is_organized_into_numbered_groups(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('documents.index'));
        $response->assertOk();

        $response->assertSee('1. Operations');
        $response->assertSee('2. Management');
        $response->assertSee('3. Configuration');
        $response->assertSee('4. Administration');
    }
}
