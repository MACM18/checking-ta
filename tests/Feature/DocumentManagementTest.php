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
}
