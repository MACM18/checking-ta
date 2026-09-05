<?php

namespace Tests\Feature;

use App\Models\ChecklistTemplate;
use App\Models\OrderMilestone;
use App\Models\ShipmentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentTrackerAndChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_can_update_custom_status_message_on_shipment_order()
    {
        $order = ShipmentOrder::create([
            'order_number' => 'SO-TEST-001',
            'company_name' => 'Acme Global',
            'country' => 'United States',
            'currency' => 'USD',
            'status' => 'active',
            'created_by' => $this->user->id,
            'custom_status_message' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('shipment-orders.custom-status', $order), [
                'custom_status_message' => 'Awaiting customs clearance inspection',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'custom_status_message' => 'Awaiting customs clearance inspection',
            ]);

        $this->assertDatabaseHas('shipment_orders', [
            'id' => $order->id,
            'custom_status_message' => 'Awaiting customs clearance inspection',
        ]);
    }

    public function test_can_mark_shipment_order_as_completed_with_one_click()
    {
        $order = ShipmentOrder::create([
            'order_number' => 'SO-TEST-002',
            'company_name' => 'Acme Global',
            'country' => 'United States',
            'currency' => 'USD',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Create 8 milestone stages with only 1 completed
        for ($i = 1; $i <= 8; $i++) {
            OrderMilestone::create([
                'shipment_order_id' => $order->id,
                'stage_code' => "stage_{$i}",
                'stage_name' => "Stage {$i}",
                'sort_order' => $i,
                'is_completed' => $i === 1,
            ]);
        }

        $this->assertEquals(1, $order->milestones()->where('is_completed', true)->count());

        $response = $this->actingAs($this->user)
            ->post(route('shipment-orders.complete', $order));

        $response->assertRedirect(route('shipment-orders.show', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertNotNull($order->delivery_date);
        $this->assertEquals(8, $order->milestones()->where('is_completed', true)->count());
    }

    public function test_shipment_order_index_highlights_custom_status_message()
    {
        $order = ShipmentOrder::create([
            'order_number' => 'SO-HIGHLIGHT-99',
            'company_name' => 'Acme Highlight Co',
            'country' => 'United Arab Emirates',
            'currency' => 'AED',
            'status' => 'active',
            'created_by' => $this->user->id,
            'custom_status_message' => 'Urgent priority inspection needed',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('shipment-orders.index'));

        $response->assertOk()
            ->assertSee('SO-HIGHLIGHT-99')
            ->assertSee('Urgent priority inspection needed');
    }

    public function test_can_import_checklists_from_another_type_with_append()
    {
        // Seed 2 items for Proforma Invoice
        ChecklistTemplate::create([
            'document_type' => 'Proforma Invoice',
            'item_text' => 'Check TRN and VAT registration',
            'hint' => 'Must match official registry',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        ChecklistTemplate::create([
            'document_type' => 'Proforma Invoice',
            'item_text' => 'Verify banking payment details',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Seed 1 existing item for Commercial Invoice
        ChecklistTemplate::create([
            'document_type' => 'Commercial Invoice',
            'item_text' => 'Check invoice serial number',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('checklists.import'), [
                'source_type' => 'Proforma Invoice',
                'target_type' => 'Commercial Invoice',
                'mode' => 'append',
            ]);

        $response->assertRedirect(route('checklists.index', ['type' => 'Commercial Invoice']))
            ->assertSessionHas('success');

        // Should now have 3 items in Commercial Invoice
        $ciItems = ChecklistTemplate::where('document_type', 'Commercial Invoice')->get();
        $this->assertCount(3, $ciItems);
        $this->assertTrue($ciItems->contains('item_text', 'Check invoice serial number'));
        $this->assertTrue($ciItems->contains('item_text', 'Check TRN and VAT registration'));
        $this->assertTrue($ciItems->contains('item_text', 'Verify banking payment details'));
    }

    public function test_can_import_checklists_from_another_type_with_replace()
    {
        // Seed 2 items for Proforma Invoice
        ChecklistTemplate::create([
            'document_type' => 'Proforma Invoice',
            'item_text' => 'Verify company billing address',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Seed 1 existing item for Packing List
        ChecklistTemplate::create([
            'document_type' => 'Packing List',
            'item_text' => 'Old packing list rule to be overwritten',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('checklists.import'), [
                'source_type' => 'Proforma Invoice',
                'target_type' => 'Packing List',
                'mode' => 'replace',
            ]);

        $response->assertRedirect(route('checklists.index', ['type' => 'Packing List']))
            ->assertSessionHas('success');

        $plItems = ChecklistTemplate::where('document_type', 'Packing List')->get();
        $this->assertCount(1, $plItems);
        $this->assertEquals('Verify company billing address', $plItems->first()->item_text);
    }

    public function test_can_bulk_destroy_checklist_items()
    {
        $item1 = ChecklistTemplate::create([
            'document_type' => 'Commercial Invoice',
            'item_text' => 'Item to delete 1',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $item2 = ChecklistTemplate::create([
            'document_type' => 'Commercial Invoice',
            'item_text' => 'Item to delete 2',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $item3 = ChecklistTemplate::create([
            'document_type' => 'Commercial Invoice',
            'item_text' => 'Item to keep',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('checklists.bulk-destroy'), [
                'target_type' => 'Commercial Invoice',
                'ids' => [$item1->id, $item2->id],
            ]);

        $response->assertRedirect(route('checklists.index', ['type' => 'Commercial Invoice']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('checklist_templates', ['id' => $item1->id]);
        $this->assertDatabaseMissing('checklist_templates', ['id' => $item2->id]);
        $this->assertDatabaseHas('checklist_templates', ['id' => $item3->id]);
    }

    public function test_document_create_page_contains_fixed_layout_and_no_native_dialogs()
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.create'));

        $response->assertOk();
        $content = $response->getContent();

        // Verify col-span-8 and col-span-4 are siblings with min-w-0
        $this->assertStringContainsString('lg:col-span-8 min-w-0 space-y-6', $content);
        $this->assertStringContainsString('lg:col-span-4 min-w-0 sticky top-6 space-y-6', $content);

        // Verify window.systemAlert, window.systemConfirm, and window.systemPrompt are used
        $this->assertStringContainsString('window.systemAlert', $content);
        $this->assertStringContainsString('window.systemConfirm', $content);
        $this->assertStringContainsString('window.systemPrompt', $content);
    }

    public function test_mark_shipment_order_as_completed_returns_json_when_requested()
    {
        $order = ShipmentOrder::create([
            'order_number' => 'SO-TEST-JSON',
            'company_name' => 'Acme Global',
            'country' => 'United States',
            'currency' => 'USD',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        OrderMilestone::create([
            'shipment_order_id' => $order->id,
            'stage_code' => 'stage_1',
            'stage_name' => 'Stage 1',
            'is_completed' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('shipment-orders.complete', $order));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'completed',
                'progress_percent' => 100,
            ]);

        $this->assertEquals('completed', $order->fresh()->status);
        $this->assertTrue($order->milestones()->first()->is_completed);
    }

    public function test_destroy_checklist_template_returns_json_when_requested()
    {
        $item = ChecklistTemplate::create([
            'document_type' => 'Commercial Invoice',
            'item_text' => 'Item to delete json',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('checklists.destroy', $item));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_id' => $item->id,
            ]);

        $this->assertDatabaseMissing('checklist_templates', ['id' => $item->id]);
    }

    public function test_bulk_destroy_checklist_templates_returns_json_when_requested()
    {
        $item1 = ChecklistTemplate::create([
            'document_type' => 'Commercial Invoice',
            'item_text' => 'Bulk json 1',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $item2 = ChecklistTemplate::create([
            'document_type' => 'Commercial Invoice',
            'item_text' => 'Bulk json 2',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('checklists.bulk-destroy'), [
                'target_type' => 'Commercial Invoice',
                'ids' => [$item1->id, $item2->id],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_ids' => [$item1->id, $item2->id],
            ]);

        $this->assertDatabaseMissing('checklist_templates', ['id' => $item1->id]);
        $this->assertDatabaseMissing('checklist_templates', ['id' => $item2->id]);
    }
}
