<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_permissions_matrix(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($admin)->get(route('permissions.index'));

        $response->assertOk();
        $response->assertSee('Permission Manager');
        $response->assertSee($editor->name);
        $response->assertSee('Manage Checklists');
    }

    public function test_non_admin_cannot_access_permissions_manager(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($editor)->get(route('permissions.index'));
        $response->assertForbidden();

        $editResponse = $this->actingAs($viewer)->get(route('permissions.edit', $editor));
        $editResponse->assertForbidden();
    }

    public function test_non_admin_without_permission_cannot_manage_checklists(): void
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'permissions' => [],
        ]);

        $this->assertFalse($viewer->canManageChecklists());

        // Visiting /checklists is forbidden
        $response = $this->actingAs($viewer)->get(route('checklists.index'));
        $response->assertForbidden();

        // Storing a checklist is forbidden
        $storeResponse = $this->actingAs($viewer)->post(route('checklists.store'), [
            'document_type' => Document::TYPE_PROFORMA,
            'item_text' => 'Unauthorized Item',
        ]);
        $storeResponse->assertForbidden();
    }

    public function test_granting_manage_checklists_permission_allows_user_to_manage_checklists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create([
            'role' => 'editor',
            'permissions' => [],
        ]);

        // Admin grants manage_checklists permission
        $updateResponse = $this->actingAs($admin)->put(route('permissions.update', $editor), [
            'permissions' => [User::PERM_MANAGE_CHECKLISTS],
        ]);

        $updateResponse->assertRedirect(route('permissions.index'));
        $updateResponse->assertSessionHas('success');

        $editor->refresh();
        $this->assertTrue($editor->canManageChecklists());
        $this->assertTrue($editor->hasPermission(User::PERM_MANAGE_CHECKLISTS));

        // Now the editor can access /checklists
        $response = $this->actingAs($editor)->get(route('checklists.index'));
        $response->assertOk();

        // And create a checklist template
        $createResponse = $this->actingAs($editor)->post(route('checklists.store'), [
            'document_type' => Document::TYPE_PROFORMA,
            'item_text' => 'Custom Granted Checklist Item',
            'sort_order' => 1,
        ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('checklist_templates', [
            'item_text' => 'Custom Granted Checklist Item',
            'document_type' => Document::TYPE_PROFORMA,
        ]);
    }

    public function test_user_permission_helpers_reflect_granted_capabilities(): void
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'permissions' => [
                User::PERM_MANAGE_CHECKLISTS,
                User::PERM_CREATE_DOCUMENTS,
                User::PERM_DELETE_DOCUMENTS,
            ],
        ]);

        $this->assertTrue($viewer->canManageChecklists());
        $this->assertTrue($viewer->canCreateDocuments());
        $this->assertTrue($viewer->canDeleteDocuments());
        $this->assertFalse($viewer->canRestoreVersions());

        // Admin always has all capabilities inherently
        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => null,
        ]);

        $this->assertTrue($admin->canManageChecklists());
        $this->assertTrue($admin->canCreateDocuments());
        $this->assertTrue($admin->canDeleteDocuments());
        $this->assertTrue($admin->canRestoreVersions());
        $this->assertTrue($admin->canManageShipments());
    }
}
