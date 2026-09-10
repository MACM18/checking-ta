<?php

namespace Tests\Feature;

use App\Models\ChecklistTemplate;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_document_workspace(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->get('/documents');
        $response->assertStatus(200);
        $response->assertSee('Shared Documents Workspace');
    }

    public function test_document_detection_api(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        ChecklistTemplate::create([
            'document_type' => 'proforma_invoice',
            'item_text' => 'Verify Customer Details',
            'is_required' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/documents/detect?number=E26211');
        $response->assertStatus(200);
        $response->assertJson([
            'detected' => true,
            'type' => 'proforma_invoice',
            'label' => 'Proforma Invoice',
        ]);
        $response->assertJsonFragment(['item_text' => 'Verify Customer Details']);
    }

    public function test_user_can_create_document_with_items_and_shipment_costs(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $payload = [
            'document_number' => 'E26211',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Gulf Apex Trading',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'total_net_weight' => 50.5,
            'total_gross_weight' => 60.0,
            'final_total' => 2500.00,
            'items' => [
                [
                    'item_code' => 'PUMP-1',
                    'description' => 'Hydraulic Pump',
                    'unit_amount' => 2,
                    'unit_price' => 1000,
                ],
                [
                    'item_code' => 'VALVE-1',
                    'description' => 'Ball Valve',
                    'unit_amount' => 5,
                    'unit_price' => 100,
                ],
            ],
            'shipment_costs' => [
                'dhl' => [
                    'checked_weight' => 60.0,
                    'system_amount' => 500,
                    'added_amount' => 50,
                    'given_amount' => 450,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post('/documents', $payload);
        $response->assertRedirect();

        $document = Document::where('document_number', 'E26211')->first();
        $this->assertNotNull($document);
        $this->assertEquals(2, $document->items()->count());
        $this->assertEquals(1, $document->shipmentCosts()->count());
        $this->assertEquals('dhl', $document->shipmentCosts()->first()->method);
        $this->assertEquals(1, $document->versions()->count());
    }

    public function test_second_user_is_redirected_to_show_when_document_is_locked(): void
    {
        $userA = User::factory()->create(['name' => 'Sarah', 'role' => 'editor']);
        $userB = User::factory()->create(['name' => 'Alex', 'role' => 'editor']);

        $document = Document::create([
            'document_number' => 'E26211',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Gulf Apex LLC',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'created_by' => $userA->id,
        ]);

        // User A opens edit form -> acquires lock
        $resA = $this->actingAs($userA)->get("/documents/{$document->id}/edit");
        $resA->assertStatus(200);

        // User B tries to open edit form -> should be redirected to show with locked message
        $resB = $this->actingAs($userB)->get("/documents/{$document->id}/edit");
        $resB->assertRedirect("/documents/{$document->id}");
        $resB->assertSessionHas('locked_alert');
    }

    public function test_document_create_and_edit_views_render_grid_navigation_and_autocomplete_blocking(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $res = $this->actingAs($user)->get('/documents/create');
        $res->assertStatus(200);
        $res->assertSee('handleTableKeyNav($event, index, 0)', false);
        $res->assertSee('handleTableKeyNav($event, index, 1)', false);
        $res->assertSee('handleTableKeyNav($event, index, 2)', false);
        $res->assertSee('handleTableKeyNav($event, index, 3)', false);
        $res->assertSee('data-grid-item="true"', false);
        $res->assertSee('data-lpignore="true"', false);
        $res->assertSee('data-1p-ignore="true"', false);
        $res->assertSee('focusGridCell(rowIdx, colIdx', false);

        $document = Document::create([
            'document_number' => 'E26299',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Gulf Apex LLC',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'created_by' => $user->id,
        ]);

        $resEdit = $this->actingAs($user)->get("/documents/{$document->id}/edit");
        $resEdit->assertStatus(200);
        $resEdit->assertSee('handleTableKeyNav($event, index, 0)', false);
        $resEdit->assertSee('handleTableKeyNav($event, index, 2)', false);
        $resEdit->assertSee('data-1p-ignore="true"', false);
        $resEdit->assertSee('focusGridCell(rowIdx, colIdx', false);
    }

    public function test_document_views_support_decimal_quantities_item_insertion_and_drag_reordering(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $resCreate = $this->actingAs($user)->get('/documents/create');
        $resCreate->assertStatus(200);
        $resCreate->assertSee('inputmode="decimal"', false);
        $resCreate->assertSee('onQuantityInput(item)', false);
        $resCreate->assertSee('insertItemAfter(index)', false);
        $resCreate->assertDontSee('@click="moveItemUp(index)"', false);
        $resCreate->assertDontSee('@click="moveItemDown(index)"', false);
        $resCreate->assertSee('onRowDragStart($event, index)', false);
        $resCreate->assertSee('onRowDrop($event, index)', false);
        $resCreate->assertSee('max-w-[1680px]', false);

        $document = Document::create([
            'document_number' => 'E26300',
            'document_type' => 'commercial_invoice',
            'company_name' => 'Gulf Apex LLC',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'created_by' => $user->id,
        ]);

        $resEdit = $this->actingAs($user)->get("/documents/{$document->id}/edit");
        $resEdit->assertStatus(200);
        $resEdit->assertSee('inputmode="decimal"', false);
        $resEdit->assertSee('onQuantityInput(item)', false);
        $resEdit->assertSee('insertItemAfter(index)', false);
        $resEdit->assertDontSee('@click="moveItemUp(index)"', false);
        $resEdit->assertDontSee('@click="moveItemDown(index)"', false);
        $resEdit->assertSee('onRowDragStart($event, index)', false);
        $resEdit->assertSee('onRowDrop($event, index)', false);
        $resEdit->assertSee('max-w-[1680px]', false);
    }

    public function test_order_reservation_views_render_autocomplete_blocking_on_quantity_fields(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $res = $this->actingAs($user)->get('/order-reservations/create');
        $res->assertStatus(200);
        $res->assertSee('autocomplete="off"', false);
        $res->assertSee('data-lpignore="true"', false);
        $res->assertSee('data-1p-ignore="true"', false);
    }

    public function test_document_create_and_edit_views_render_bulk_paste_controls_and_handlers(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $resCreate = $this->actingAs($user)->get('/documents/create');
        $resCreate->assertStatus(200);
        $resCreate->assertSee('Bulk Paste Items / Qty', false);
        $resCreate->assertSee('@paste="handleItemCodePaste($event, index)"', false);
        $resCreate->assertSee('@paste="handleQuantityPaste($event, index)"', false);
        $resCreate->assertSee('openBulkPasteModal', false);
        $resCreate->assertSee('applyBulkAddItems', false);
        $resCreate->assertSee('applyBulkUpdateQuantities', false);
        $resCreate->assertSee('bulkPastePreviewItems', false);

        $document = Document::create([
            'document_number' => 'E26300',
            'document_type' => 'commercial_invoice',
            'company_name' => 'Gulf Apex LLC',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'created_by' => $user->id,
        ]);

        $resEdit = $this->actingAs($user)->get("/documents/{$document->id}/edit");
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Bulk Paste Items / Qty', false);
        $resEdit->assertSee('@paste="handleItemCodePaste($event, index)"', false);
        $resEdit->assertSee('@paste="handleQuantityPaste($event, index)"', false);
        $resEdit->assertSee('openBulkPasteModal', false);
        $resEdit->assertSee('applyBulkAddItems', false);
        $resEdit->assertSee('applyBulkUpdateQuantities', false);
        $resEdit->assertSee('bulkPastePreviewItems', false);
    }

    public function test_document_model_calculates_total_quantity_correctly(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'PI-99001',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Apex Global Trading',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'created_by' => $user->id,
        ]);

        $doc->items()->createMany([
            [
                'item_code' => 'SKU-001',
                'description' => 'Motor Unit',
                'unit_amount' => 15,
                'unit_price' => 100,
                'total_amount' => 1500,
                'sort_order' => 1,
            ],
            [
                'item_code' => 'SKU-002',
                'description' => 'Gearbox Component',
                'unit_amount' => 25.5,
                'unit_price' => 50,
                'total_amount' => 1275,
                'sort_order' => 2,
            ],
            [
                'item_code' => 'DISCOUNT',
                'description' => 'Loyalty Discount',
                'unit_amount' => 1,
                'unit_price' => -200,
                'total_amount' => -200,
                'sort_order' => 3,
            ],
            [
                'item_code' => 'TAX',
                'description' => 'VAT 5%',
                'unit_amount' => 1,
                'unit_price' => 128.75,
                'total_amount' => 128.75,
                'sort_order' => 4,
            ],
        ]);

        $doc->refresh();
        $this->assertEquals(40.5, $doc->total_quantity);
        $this->assertEquals('40.50', $doc->formatted_total_quantity);

        // Whole number test
        $doc->items()->where('item_code', 'SKU-002')->update(['unit_amount' => 25]);
        $doc->refresh();
        $this->assertEquals(40, $doc->total_quantity);
        $this->assertEquals('40', $doc->formatted_total_quantity);
    }

    public function test_document_views_display_total_quantity_everywhere(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'PI-99002',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Atlas Logistics FZE',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'created_by' => $user->id,
        ]);

        $doc->items()->createMany([
            [
                'item_code' => 'A101',
                'description' => 'Steel Rod',
                'unit_amount' => 50,
                'unit_price' => 10,
                'total_amount' => 500,
                'sort_order' => 1,
            ],
            [
                'item_code' => 'A102',
                'description' => 'Brass Fitting',
                'unit_amount' => 100,
                'unit_price' => 5,
                'total_amount' => 500,
                'sort_order' => 2,
            ],
        ]);

        // 1. Show page standard view & transfer mode
        $resShow = $this->actingAs($user)->get("/documents/{$doc->id}");
        $resShow->assertStatus(200);
        $resShow->assertSee('Total Quantity', false);
        $resShow->assertSee('150 units', false);
        $resShow->assertSee('Total Qty: 150 units', false);
        $resShow->assertSee('Copy Total Qty', false);

        // 2. Print page
        $resPrint = $this->actingAs($user)->get("/documents/{$doc->id}/print");
        $resPrint->assertStatus(200);
        $resPrint->assertSee('Total Quantity:', false);
        $resPrint->assertSee('150 units', false);

        // 3. Create page
        $resCreate = $this->actingAs($user)->get('/documents/create');
        $resCreate->assertStatus(200);
        $resCreate->assertSee('Total Quantity', false);
        $resCreate->assertSee('formattedTotalQuantity', false);

        // 4. Edit page
        $resEdit = $this->actingAs($user)->get("/documents/{$doc->id}/edit");
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Total Quantity', false);
        $resEdit->assertSee('formattedTotalQuantity', false);

        // 5. Index page
        $resIndex = $this->actingAs($user)->get('/documents');
        $resIndex->assertStatus(200);
        $resIndex->assertSee('<span class="text-indigo-600 font-semibold">Qty: 150</span>', false);
    }

    public function test_document_views_do_not_contain_apply_to_all_rows_button_and_use_currency_filtering(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $doc = Document::create([
            'document_number' => 'DOC-FILTER-1',
            'document_type' => 'commercial_invoice',
            'company_name' => 'Apex Co',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'created_by' => $user->id,
        ]);

        $resCreate = $this->actingAs($user)->get('/documents/create');
        $resCreate->assertOk();
        $resCreate->assertDontSee('Apply <span x-text="selectedPriceLabel"', false);
        $resCreate->assertDontSee('Apply to All Rows', false);
        $resCreate->assertSee('filteredPriceLists', false);
        $resCreate->assertSee('filteredPriceLabels', false);
        $resEdit = $this->actingAs($user)->get("/documents/{$doc->id}/edit");
        $resEdit->assertOk();
        $resEdit->assertDontSee('Apply <span x-text="selectedPriceLabel"', false);
        $resEdit->assertDontSee('Apply to All Rows', false);
        $resEdit->assertSee('filteredPriceLists', false);
        $resEdit->assertSee('filteredPriceLabels', false);
        $resEdit->assertSee('batchRepriceAllItems', false);
    }

    public function test_document_creation_persists_item_net_weights_and_computes_total_net_weight(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $payload = [
            'document_number' => 'DOC-WT-99',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Apex Industrial LLC',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'items' => [
                [
                    'item_code' => 'HEAVY-1',
                    'description' => 'Heavy Bearing',
                    'unit_amount' => 4,
                    'unit_price' => 250,
                    'unit_weight' => 2.5,
                    'total_weight' => 10.0,
                ],
                [
                    'item_code' => 'LIGHT-1',
                    'description' => 'Light Washer',
                    'unit_amount' => 20,
                    'unit_price' => 5,
                    'unit_weight' => 0.1,
                    'total_weight' => 2.0,
                ],
            ],
            'packages' => [
                [
                    'package_type' => 'Carton',
                    'dimension_type' => 'standard',
                    'length_cm' => 30,
                    'width_cm' => 20,
                    'height_cm' => 15,
                    'quantity' => 1,
                    'gross_weight_per_pkg_kg' => 13.5,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post('/documents', $payload);
        $response->assertRedirect();

        $doc = Document::where('document_number', 'DOC-WT-99')->first();
        $this->assertNotNull($doc);
        // Total net weight should be auto-computed from items (10.0 + 2.0 = 12.0 kg)
        $this->assertEquals(12.0, (float) $doc->total_net_weight);
        $this->assertEquals(2, $doc->items()->count());

        $firstItem = $doc->items()->where('item_code', 'HEAVY-1')->first();
        $this->assertEquals(2.5, (float) $firstItem->unit_weight);
        $this->assertEquals(10.0, (float) $firstItem->total_weight);

        $pkg = $doc->packages()->first();
        $this->assertNotNull($pkg);
    }

    public function test_document_weight_section_does_not_contain_volumetric_weight(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $doc = Document::create([
            'document_number' => 'DOC-NO-VOL',
            'document_type' => 'commercial_invoice',
            'company_name' => 'Global Ship',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'total_net_weight' => 25.0,
            'total_gross_weight' => 30.0,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/documents/{$doc->id}");
        $response->assertOk();

        // In show view, the document weight section should show Total Net Weight and Total Gross Weight,
        // but not "Volumetric Weight:"
        $response->assertSee('Total Net Weight:');
        $response->assertSee('Total Gross Weight:');
        $response->assertDontSee('Volumetric Weight:');
    }

    public function test_freight_charges_are_added_to_document_total_amount_on_creation(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $payload = [
            'document_number' => 'DOC-FREIGHT-101',
            'document_type' => 'commercial_invoice',
            'company_name' => 'Apex Cargo LLC',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'selected_shipment_method' => 'dhl',
            'items' => [
                [
                    'item_code' => 'PROD-A',
                    'description' => 'Item Alpha',
                    'unit_amount' => 2,
                    'unit_price' => 100,
                ],
                [
                    'item_code' => 'PROD-B',
                    'description' => 'Item Beta',
                    'unit_amount' => 1,
                    'unit_price' => 300,
                ],
            ],
            'shipment_costs' => [
                'dhl' => [
                    'checked_weight' => 5.0,
                    'rate_per_kg' => 15.0,
                    'system_amount' => 75.0,
                    'given_amount' => 75.0,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post('/documents', $payload);
        $response->assertRedirect();

        $doc = Document::where('document_number', 'DOC-FREIGHT-101')->first();
        $this->assertNotNull($doc);
        $this->assertEquals(500.00, (float) $doc->subtotal);
        $this->assertEquals(575.00, (float) $doc->final_total);

        // Verify show view displays the total with freight
        $showRes = $this->actingAs($user)->get("/documents/{$doc->id}");
        $showRes->assertOk();
        $showRes->assertSee('575.00');
        $showRes->assertSee('Subtotal: 500.00 + Freight: 75.00');

        // Verify print view displays the total with freight
        $printRes = $this->actingAs($user)->get("/documents/{$doc->id}/print");
        $printRes->assertOk();
        $printRes->assertSee('575.00');
        $printRes->assertSee('Subtotal: 500.00 + Freight: 75.00');
    }

    public function test_updating_freight_charges_updates_total_amount(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $doc = Document::create([
            'document_number' => 'DOC-FREIGHT-EDIT',
            'document_type' => 'commercial_invoice',
            'company_name' => 'Apex Cargo LLC',
            'country' => 'United Arab Emirates',
            'document_date' => now(),
            'currency' => 'USD',
            'subtotal' => 400.00,
            'final_total' => 400.00,
            'created_by' => $user->id,
        ]);
        $doc->items()->create([
            'item_code' => 'ITEM-1',
            'description' => 'Test Item',
            'unit_amount' => 4,
            'unit_price' => 100,
            'total_amount' => 400,
        ]);

        $updatePayload = [
            'document_number' => 'DOC-FREIGHT-EDIT',
            'document_type' => 'commercial_invoice',
            'company_name' => 'Apex Cargo LLC',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'selected_shipment_method' => 'air_freight',
            'items' => [
                [
                    'item_code' => 'ITEM-1',
                    'description' => 'Test Item',
                    'unit_amount' => 4,
                    'unit_price' => 100,
                ],
            ],
            'shipment_costs' => [
                'air_freight' => [
                    'checked_weight' => 10.0,
                    'rate_per_kg' => 12.0,
                    'system_amount' => 120.0,
                    'given_amount' => 120.0,
                ],
            ],
        ];

        $response = $this->actingAs($user)->put("/documents/{$doc->id}", $updatePayload);
        $response->assertRedirect();

        $doc->refresh();
        $this->assertEquals(400.00, (float) $doc->subtotal);
        $this->assertEquals(520.00, (float) $doc->final_total);
    }
}
