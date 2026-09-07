<?php

namespace Tests\Feature;

use App\Models\ChecklistTemplate;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentLock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTransferModeAndChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_show_view_renders_split_screen_transfer_mode_and_copy_buttons(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'E26211',
            'document_type' => Document::TYPE_PROFORMA_INVOICE,
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'address' => 'Industrial Area 10, Sharjah',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'subtotal' => 1500.00,
            'final_total' => 1500.00,
            'current_version' => 1,
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentItem::create([
            'document_id' => $doc->id,
            'item_code' => 'VALVE-99',
            'description' => 'Industrial Ball Valve 2 inch',
            'unit_amount' => 5,
            'unit_price' => 300.00,
            'total_amount' => 1500.00,
            'unit_weight' => 2.500,
            'total_weight' => 12.500,
        ]);

        ChecklistTemplate::create([
            'document_type' => Document::TYPE_PROFORMA_INVOICE,
            'item_text' => 'Verify recipient country customs code',
            'hint' => 'Check UAE TRN number',
            'is_required' => true,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('documents.show', $doc));

        $response->assertStatus(200);

        // Header Transfer Mode Toggle
        $response->assertSee('Transfer Mode');
        $response->assertSee('toggle-transfer-mode');

        // Split-Screen Content & Copy Actions
        $response->assertSee('Split-Screen Transfer Mode Active');
        $response->assertSee('Sequential Items Transfer List');
        $response->assertSee('Copy All Items (Excel / ERP Table)');
        $response->assertSee('Codes List');
        $response->assertSee('Copy Name');
        $response->assertSee('Copy Country');
        $response->assertSee('Copy Doc #');
        $response->assertSee('Copy Date');
        $response->assertSee('Copy Total');
        $response->assertSee('VALVE-99');
        $response->assertSee('Industrial Ball Valve 2 inch');

        // Verification Checklist
        $response->assertSee('Verification Checklist');
        $response->assertSee('Verify recipient country customs code');
        $response->assertSee('Check UAE TRN number');
        $response->assertSee('Operator Verification:');
        $response->assertSee('Checklist marks are independent of document edit locks');
        $response->assertSee('window.systemConfirm', false);
    }

    public function test_verification_checklist_is_accessible_when_document_is_locked_by_another_user(): void
    {
        $userA = User::factory()->create(['name' => 'Editor Alice', 'role' => 'editor']);
        $userB = User::factory()->create(['name' => 'Operator Bob', 'role' => 'viewer']);

        $doc = Document::create([
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
            'created_by' => $userA->id,
            'updated_by' => $userA->id,
        ]);

        // Alice acquires active lock
        DocumentLock::create([
            'document_id' => $doc->id,
            'user_id' => $userA->id,
            'locked_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        // Bob visits document show page
        $response = $this->actingAs($userB)->get(route('documents.show', $doc));

        $response->assertStatus(200);

        // Bob sees concurrency banner that document is locked
        $response->assertSee('Concurrency Protection:');
        $response->assertSee('Editor Alice');

        // Bob still has full access to the Verification Checklist for cross-system entry
        $response->assertSee('Verification Checklist');
        $response->assertSee('Operator Verification:');
        $response->assertSee('Checklist marks are independent of document edit locks');
    }

    public function test_subpages_have_single_export_button_linking_to_centralized_export_hub(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        // 1. Documents Index
        $docResp = $this->actingAs($user)->get(route('documents.index'));
        $docResp->assertStatus(200);
        $docResp->assertSee('Export Reports');
        $docResp->assertSee(route('dashboard').'#centralized-export-hub');

        // 2. Shipment Orders Index
        $orderResp = $this->actingAs($user)->get(route('shipment-orders.index'));
        $orderResp->assertStatus(200);
        $orderResp->assertSee('Export Reports');
        $orderResp->assertSee(route('dashboard').'#centralized-export-hub');

        // 3. Order Reservations Index
        $reserveResp = $this->actingAs($user)->get(route('order-reservations.index'));
        $reserveResp->assertStatus(200);
        $reserveResp->assertSee('Export Reports');
        $reserveResp->assertSee(route('dashboard').'#centralized-export-hub');
    }

    public function test_non_admin_user_has_checklist_focus_mode_and_collapsible_form_on_create(): void
    {
        $nonAdmin = User::factory()->create(['role' => 'viewer']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Non-admin request
        $nonAdminResp = $this->actingAs($nonAdmin)->get(route('documents.create'));
        $nonAdminResp->assertStatus(200);
        $nonAdminResp->assertSee('Checklist Verification Focus Mode');
        $nonAdminResp->assertSee('showFullForm: false');
        $nonAdminResp->assertSee('Show Complete Document Form ▼');

        // Admin request
        $adminResp = $this->actingAs($admin)->get(route('documents.create'));
        $adminResp->assertStatus(200);
        $adminResp->assertSee('showFullForm: true');
    }

    public function test_shortcut_guide_widget_is_rendered_in_navigation_with_hover_guide(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Shortcuts Guide');
        $response->assertSee('System hotkeys');
        $response->assertSee('Quick Save Form');
        $response->assertSee('Focus Search Bar');
        $response->assertSee('Split-Screen Transfer');
        $response->assertSee('Full Dialog');
    }
}
