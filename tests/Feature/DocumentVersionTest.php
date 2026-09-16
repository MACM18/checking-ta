<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_versioning_and_restore_workflow(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'E26211',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Original Apex LLC',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'current_version' => 1,
            'created_by' => $user->id,
        ]);

        $doc->items()->create([
            'item_code' => 'PUMP-01',
            'description' => 'Original Pump',
            'unit_amount' => 2,
            'unit_price' => 500,
            'total_amount' => 1000,
        ]);

        $versionService = new DocumentVersionService;

        // 1. Snapshot Version 1
        $versionService->createSnapshot($doc, $user, 'Initial Version');

        // 2. Modify document to Version 2
        $doc->update([
            'company_name' => 'Updated Apex LLC',
            'current_version' => 2,
        ]);
        $doc->items()->delete();
        $doc->items()->create([
            'item_code' => 'VALVE-99',
            'description' => 'New Valve',
            'unit_amount' => 5,
            'unit_price' => 100,
            'total_amount' => 500,
        ]);

        // Snapshot Version 2
        $versionService->createSnapshot($doc, $user, 'Replaced pump with valve');

        $this->assertEquals(2, $doc->versions()->count());
        $this->assertEquals('VALVE-99', $doc->items()->first()->item_code);

        // 3. Restore Version 1
        $restored = $versionService->restoreVersion($doc, 1, $user);

        // Version should now be 3 (non-destructive restoration), but content matches Version 1
        $this->assertEquals(3, $restored->current_version);
        $this->assertEquals('Original Apex LLC', $restored->company_name);
        $this->assertEquals(1, $restored->items()->count());
        $this->assertEquals('PUMP-01', $restored->items()->first()->item_code);
        $this->assertEquals(1000, $restored->items()->first()->total_amount);
    }

    public function test_user_can_create_new_version_snapshot_via_http_post(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'E99001',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Gulf Traders',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'current_version' => 1,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('documents.versions.store', $doc), [
            'change_summary' => 'Approved by buyer; locked v2 milestone',
        ]);

        $response->assertRedirect(route('documents.edit', $doc));
        $response->assertSessionHas('success');

        $doc->refresh();
        $this->assertEquals(2, $doc->current_version);
        $this->assertEquals(1, $doc->versions()->count());
        $this->assertEquals('Approved by buyer; locked v2 milestone', $doc->versions()->first()->change_summary);
    }

    public function test_viewer_cannot_create_version_snapshot(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $editor = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'E99002',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Gulf Traders',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'current_version' => 1,
            'created_by' => $editor->id,
        ]);

        $response = $this->actingAs($viewer)->post(route('documents.versions.store', $doc), [
            'change_summary' => 'Unauthorized attempt',
        ]);

        $response->assertRedirect(route('documents.show', $doc));
        $response->assertSessionHas('error');

        $doc->refresh();
        $this->assertEquals(1, $doc->current_version);
    }

    public function test_compute_diff_with_current_detects_additions_deletions_and_modifications(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'E-DIFF-001',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Alpha Corp',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'subtotal' => 1000,
            'final_total' => 1000,
            'current_version' => 1,
            'created_by' => $user->id,
        ]);

        $itemA = $doc->items()->create([
            'item_code' => 'SKU-A',
            'description' => 'Original Item A',
            'unit_amount' => 10,
            'unit_price' => 50,
            'total_amount' => 500,
            'sort_order' => 0,
        ]);

        $itemB = $doc->items()->create([
            'item_code' => 'SKU-B',
            'description' => 'Item B to be removed',
            'unit_amount' => 2,
            'unit_price' => 100,
            'total_amount' => 200,
            'sort_order' => 1,
        ]);

        $itemC = $doc->items()->create([
            'item_code' => 'SKU-C',
            'description' => 'Item C unchanged',
            'unit_amount' => 1,
            'unit_price' => 300,
            'total_amount' => 300,
            'sort_order' => 2,
        ]);

        $versionService = new DocumentVersionService;
        $snapshotV1 = $versionService->createSnapshot($doc, $user, 'Snapshot Version 1');

        // Now modify document state to represent Current Version 2
        $doc->update([
            'company_name' => 'Alpha Global Corp',
            'subtotal' => 1150,
            'final_total' => 1150,
            'current_version' => 2,
        ]);

        // Modify SKU-A
        $itemA->update([
            'unit_amount' => 15,
            'total_amount' => 750,
        ]);

        // Delete SKU-B
        $itemB->delete();

        // Add SKU-D
        $doc->items()->create([
            'item_code' => 'SKU-D',
            'description' => 'Brand New Item D',
            'unit_amount' => 4,
            'unit_price' => 25,
            'total_amount' => 100,
            'sort_order' => 3,
        ]);

        $diff = $versionService->computeDiffWithCurrent($doc, $snapshotV1);

        $this->assertTrue($diff['has_changes']);
        $this->assertEquals(1, $diff['summary']['additions'], 'Expected 1 added item (SKU-D)');
        $this->assertEquals(1, $diff['summary']['deletions'], 'Expected 1 deleted item (SKU-B)');
        $this->assertEquals(1, $diff['summary']['unchanged'], 'Expected 1 unchanged item (SKU-C)');

        // Check header diff contains company_name
        $this->assertNotEmpty($diff['header_diffs']);
        $this->assertEquals('company_name', $diff['header_diffs'][0]['field']);
        $this->assertEquals('Alpha Corp', $diff['header_diffs'][0]['old']);
        $this->assertEquals('Alpha Global Corp', $diff['header_diffs'][0]['current']);

        // Check financial deltas (1150 - 1000 = +150)
        $this->assertEquals(150.0, $diff['financial_deltas']['final_total']['diff']);

        // Check items diff entries
        $itemsDiff = collect($diff['items_diff']);
        $itemADiff = $itemsDiff->firstWhere('item_code', 'SKU-A');
        $this->assertNotNull($itemADiff);
        $this->assertEquals('modified', $itemADiff['type']);
        $this->assertEquals(5.0, $itemADiff['deltas']['unit_amount']);

        $itemBDiff = $itemsDiff->firstWhere('item_code', 'SKU-B');
        $this->assertNotNull($itemBDiff);
        $this->assertEquals('removed', $itemBDiff['type']);

        $itemDDiff = $itemsDiff->firstWhere('item_code', 'SKU-D');
        $this->assertNotNull($itemDDiff);
        $this->assertEquals('added', $itemDDiff['type']);
    }

    public function test_version_show_renders_github_style_diff_view_with_indicators(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $doc = Document::create([
            'document_number' => 'E-DIFF-UI-01',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Original Beta Trading',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'subtotal' => 800,
            'final_total' => 800,
            'current_version' => 1,
            'created_by' => $user->id,
        ]);

        $doc->items()->create([
            'item_code' => 'PUMP-100',
            'description' => 'Water Pump',
            'unit_amount' => 2,
            'unit_price' => 400,
            'total_amount' => 800,
        ]);

        $versionService = new DocumentVersionService;
        $versionService->createSnapshot($doc, $user, 'Version 1 baseline');

        // Update to version 2
        $doc->update([
            'company_name' => 'Updated Beta International',
            'subtotal' => 1200,
            'final_total' => 1200,
            'current_version' => 2,
        ]);

        $doc->items()->create([
            'item_code' => 'HOSE-200',
            'description' => 'Pressure Hose',
            'unit_amount' => 4,
            'unit_price' => 100,
            'total_amount' => 400,
        ]);

        $response = $this->actingAs($user)->get(route('documents.versions.show', [$doc, 1]));

        $response->assertStatus(200);
        $response->assertSee('git diff');
        $response->assertSee('Comparing Version 1 with Current Version 2');
        $response->assertSee('+1 additions');
        $response->assertSee('+ HOSE-200');
        $response->assertSee('Added in Current');
        $response->assertSee('Original Beta Trading');
        $response->assertSee('Updated Beta International');
    }
}
