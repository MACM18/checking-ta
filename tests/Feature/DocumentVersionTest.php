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
}
