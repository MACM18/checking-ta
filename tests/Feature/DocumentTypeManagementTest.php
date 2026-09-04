<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentTypeDetector;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DocumentTypeSeeder::class);
    }

    public function test_can_list_document_types(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($user)->get(route('document-types.index'));
        $response->assertOk();
        $response->assertSee('Proforma Invoice (E / EL)');
        $response->assertSee('Invoice (N)');
        $response->assertSee('Packing List (W)');
    }

    public function test_can_create_custom_document_type_and_auto_detect_it(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->post(route('document-types.store'), [
            'code' => 'quotation',
            'name' => 'Quotation (QT)',
            'prefix' => 'QT,QUOT',
            'suffix' => 'Q',
            'description' => 'Formal price quotation',
            'badge_color' => 'teal',
            'is_active' => '1',
            'sort_order' => 12,
        ]);

        $response->assertRedirect(route('document-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('document_types', [
            'code' => 'quotation',
            'name' => 'Quotation (QT)',
            'badge_color' => 'teal',
        ]);

        // Test real-time detection for QT prefix
        $detected = DocumentTypeDetector::detect('QT26001');
        $this->assertEquals('quotation', $detected['type']);
        $this->assertEquals('Quotation (QT)', $detected['label']);

        // Test real-time detection for Q suffix
        $detectedSuffix = DocumentTypeDetector::detect('REF-991Q');
        $this->assertEquals('quotation', $detectedSuffix['type']);
    }

    public function test_can_update_document_type(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $docType = DocumentType::where('code', 'proforma_invoice')->first();

        $response = $this->actingAs($user)->put(route('document-types.update', $docType), [
            'name' => 'Proforma Invoice / PI',
            'prefix' => 'E,EL,PI',
            'suffix' => null,
            'description' => 'Updated description',
            'badge_color' => 'indigo',
            'is_active' => '1',
            'sort_order' => 1,
        ]);

        $response->assertRedirect(route('document-types.index'));
        $this->assertDatabaseHas('document_types', [
            'code' => 'proforma_invoice',
            'name' => 'Proforma Invoice / PI',
            'prefix' => 'E,EL,PI',
        ]);

        // PI prefix should now be detected
        $detected = DocumentTypeDetector::detect('PI-9901');
        $this->assertEquals('proforma_invoice', $detected['type']);
    }

    public function test_cannot_delete_system_document_type(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $systemType = DocumentType::where('code', 'invoice')->first();

        $response = $this->actingAs($user)->delete(route('document-types.destroy', $systemType));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('document_types', ['code' => 'invoice']);
    }

    public function test_cannot_delete_type_with_associated_documents(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customType = DocumentType::create([
            'code' => 'custom_voucher',
            'name' => 'Voucher',
            'is_system' => false,
            'is_active' => true,
        ]);

        Document::create([
            'document_number' => 'VOUCH-001',
            'original_filename' => 'voucher.xlsx',
            'file_path' => 'documents/voucher.xlsx',
            'file_hash' => 'hash_voucher_1',
            'company_name' => 'Custom Logistics',
            'country' => 'United States',
            'document_date' => '2026-09-04',
            'document_type' => 'custom_voucher',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('document-types.destroy', $customType));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('document_types', ['code' => 'custom_voucher']);
    }

    public function test_can_delete_unused_custom_document_type(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customType = DocumentType::create([
            'code' => 'temporary_test_type',
            'name' => 'Temporary Test Type',
            'is_system' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('document-types.destroy', $customType));
        $response->assertRedirect(route('document-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('document_types', ['code' => 'temporary_test_type']);
    }
}
