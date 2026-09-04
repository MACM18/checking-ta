<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSourceImportAndWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_source_document_data_by_id_and_by_document_number(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $sourceDoc = Document::create([
            'document_number' => 'E26211',
            'document_type' => Document::TYPE_PROFORMA_INVOICE,
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'address' => 'Industrial Area 10, Sharjah',
            'contact_details' => 'Attn: Mr. Tariq, tariq@gulfapex.com',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'total_net_weight' => 25.500,
            'total_gross_weight' => 28.000,
            'subtotal' => 1500.00,
            'final_total' => 1500.00,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentItem::create([
            'document_id' => $sourceDoc->id,
            'item_code' => 'VALVE-99',
            'description' => 'Industrial Ball Valve 2 inch',
            'unit_amount' => 5,
            'unit_price' => 300.00,
            'total_amount' => 1500.00,
            'unit_weight' => 5.100,
            'total_weight' => 25.500,
        ]);

        DocumentPackage::create([
            'document_id' => $sourceDoc->id,
            'package_type' => 'Carton',
            'dimension_type' => 'standard',
            'length_cm' => 40,
            'width_cm' => 30,
            'height_cm' => 25,
            'quantity' => 2,
            'gross_weight_per_pkg_kg' => 14.000,
            'volumetric_weight_kg' => 12.000,
            'cbm' => 0.060,
        ]);

        // Test fetching by numeric ID
        $responseById = $this->actingAs($user)->getJson("/api/documents/source-data/{$sourceDoc->id}");
        $responseById->assertStatus(200);
        $responseById->assertJson([
            'document_number' => 'E26211',
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'currency' => 'USD',
        ]);
        $responseById->assertJsonCount(1, 'items');
        $responseById->assertJsonFragment(['item_code' => 'VALVE-99', 'unit_weight' => 5.1]);

        // Test fetching by document code
        $responseByCode = $this->actingAs($user)->getJson('/api/documents/source-data/E26211');
        $responseByCode->assertStatus(200);
        $responseByCode->assertJson([
            'id' => $sourceDoc->id,
            'document_number' => 'E26211',
        ]);
    }

    public function test_can_create_packing_list_linked_to_source_with_weights_and_zero_financial_totals(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $sourceDoc = Document::create([
            'document_number' => 'E26211',
            'document_type' => Document::TYPE_PROFORMA_INVOICE,
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'subtotal' => 1000.00,
            'final_total' => 1000.00,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $payload = [
            'document_number' => 'PL-26211',
            'document_type' => Document::TYPE_PACKING_LIST,
            'source_document_id' => $sourceDoc->id,
            'source_document_number' => 'E26211',
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'total_gross_weight' => 20.000,
            'items' => [
                [
                    'item_code' => 'PUMP-10',
                    'description' => 'Heavy Duty Water Pump',
                    'unit_amount' => 2,
                    'unit_price' => 0, // Weight-only document sends 0
                    'unit_weight' => 8.500,
                    'total_weight' => 17.000,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post('/documents', $payload);
        $response->assertRedirect();

        $pl = Document::where('document_number', 'PL-26211')->first();
        $this->assertNotNull($pl);
        $this->assertEquals(Document::TYPE_PACKING_LIST, $pl->document_type);
        $this->assertEquals($sourceDoc->id, $pl->source_document_id);
        $this->assertEquals('E26211', $pl->source_document_number);
        $this->assertTrue($pl->isWeightOnly());

        // Subtotal and final_total are 0 for weight-only documents
        $this->assertEquals(0, $pl->subtotal);
        $this->assertEquals(0, $pl->final_total);

        // Net weight is auto-computed from items total_weight
        $this->assertEquals(17.000, $pl->total_net_weight);

        // Verify item weights
        $item = $pl->items->first();
        $this->assertEquals('PUMP-10', $item->item_code);
        $this->assertEquals(8.500, $item->unit_weight);
        $this->assertEquals(17.000, $item->total_weight);
    }

    public function test_document_show_view_renders_weights_and_hides_prices_for_packing_list(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $sourceDoc = Document::create([
            'document_number' => 'E26211',
            'document_type' => Document::TYPE_PROFORMA_INVOICE,
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'subtotal' => 2000.00,
            'final_total' => 2000.00,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $packingList = Document::create([
            'document_number' => 'PL-26211',
            'document_type' => Document::TYPE_PACKING_LIST,
            'source_document_id' => $sourceDoc->id,
            'source_document_number' => 'E26211',
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'total_net_weight' => 15.250,
            'total_gross_weight' => 18.000,
            'subtotal' => 0,
            'final_total' => 0,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentItem::create([
            'document_id' => $packingList->id,
            'item_code' => 'MOTOR-40',
            'description' => 'Stepper Motor',
            'unit_amount' => 3,
            'unit_price' => 0,
            'total_amount' => 0,
            'unit_weight' => 5.000,
            'total_weight' => 15.000,
        ]);

        $response = $this->actingAs($user)->get("/documents/{$packingList->id}");
        $response->assertStatus(200);

        // Verify Weight-Only headers and elements
        $response->assertSee('Packing List & Weights Breakdown', false);
        $response->assertSee('Weight-Only (No Prices)');
        $response->assertSee('Unit Net Wt (kg)');
        $response->assertSee('Total Net Wt (kg)');
        $response->assertSee('Non-Commercial Document (No Prices)');
        $response->assertSee('Derived from Source:');
        $response->assertSee('E26211');

        // Confirm unit price header is NOT rendered
        $response->assertDontSee('Unit Price');
    }

    public function test_document_show_view_shows_quick_generator_shortcuts(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $sourceDoc = Document::create([
            'document_number' => 'E26211',
            'document_type' => Document::TYPE_PROFORMA_INVOICE,
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'subtotal' => 2000.00,
            'final_total' => 2000.00,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/documents/{$sourceDoc->id}");
        $response->assertStatus(200);
        $response->assertSee('Generate Linked Documents');
        $response->assertSee('Create Packing List (Weights Only)');
        $response->assertSee('Create Commercial Invoice');
        $response->assertSee('Create Reserve Document');
    }
}
