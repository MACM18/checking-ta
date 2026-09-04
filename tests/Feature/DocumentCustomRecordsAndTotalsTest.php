<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentCustomRecordsAndTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_creation_saves_correct_final_total_and_not_zero(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $payload = [
            'document_number' => 'PI-2026-001',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Acme Corporation',
            'country' => 'United States',
            'document_date' => '2026-09-04',
            'currency' => 'USD',
            'items' => [
                [
                    'item_code' => 'SKU-A',
                    'description' => 'Precision Bearing',
                    'unit_amount' => '3',
                    'unit_price' => '120.00',
                ],
                [
                    'item_code' => 'SKU-B',
                    'description' => 'Rotary Shaft',
                    'unit_amount' => '2',
                    'unit_price' => '50.00',
                ],
            ],
            'final_total' => '460.00',
        ];

        $response = $this->actingAs($user)->post(route('documents.store'), $payload);

        $document = Document::where('document_number', 'PI-2026-001')->first();
        $this->assertNotNull($document);
        $response->assertRedirect(route('documents.show', $document));

        // Must NOT be zero
        $this->assertEquals(460.00, (float) $document->subtotal);
        $this->assertEquals(460.00, (float) $document->final_total);

        // Verify show view displays the correct currency and non-zero final total
        $showResponse = $this->actingAs($user)->get(route('documents.show', $document));
        $showResponse->assertOk();
        $showResponse->assertSee('USD 460.00');
        $showResponse->assertDontSee('USD 0.00');
    }

    public function test_invoice_supports_custom_records_discounts_as_minus_and_additions_as_plus(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $payload = [
            'document_number' => 'CI-2026-002',
            'document_type' => Document::TYPE_INVOICE,
            'company_name' => 'Global Logistics Ltd',
            'country' => 'Germany',
            'document_date' => '2026-09-04',
            'currency' => 'USD',
            'items' => [
                [
                    'item_code' => 'PART-100',
                    'description' => 'High Voltage Motor',
                    'unit_amount' => '2',
                    'unit_price' => '500.00', // Total: 1000.00
                ],
                [
                    'item_code' => 'DISCOUNT',
                    'description' => 'VIP Customer 10% Discount',
                    'unit_amount' => '1',
                    'unit_price' => '-100.00', // Discount minus
                ],
                [
                    'item_code' => 'ADDITION',
                    'description' => 'Express Air Freight Surcharge',
                    'unit_amount' => '1',
                    'unit_price' => '150.00', // Addition plus
                ],
            ],
            // final_total calculated: 1000 - 100 + 150 = 1050.00
            'final_total' => '1050.00',
        ];

        $response = $this->actingAs($user)->post(route('documents.store'), $payload);

        $document = Document::where('document_number', 'CI-2026-002')->first();
        $this->assertNotNull($document);
        $response->assertRedirect(route('documents.show', $document));

        $this->assertEquals(1050.00, (float) $document->subtotal);
        $this->assertEquals(1050.00, (float) $document->final_total);

        // Verify line items saved
        $discountItem = $document->items()->where('item_code', 'DISCOUNT')->first();
        $this->assertNotNull($discountItem);
        $this->assertEquals(-100.00, (float) $discountItem->total_amount);

        $additionItem = $document->items()->where('item_code', 'ADDITION')->first();
        $this->assertNotNull($additionItem);
        $this->assertEquals(150.00, (float) $additionItem->total_amount);

        // Verify show view displays the breakdown badges and amounts
        $showResponse = $this->actingAs($user)->get(route('documents.show', $document));
        $showResponse->assertOk();
        $showResponse->assertSee('Discount (-)');
        $showResponse->assertSee('Addition (+)');
        $showResponse->assertSee('Discounts: -USD 100.00');
        $showResponse->assertSee('Additions: +USD 150.00');
        $showResponse->assertSee('USD 1,050.00');
    }

    public function test_document_update_recalculates_and_preserves_adjustments(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $document = Document::create([
            'document_number' => 'PI-2026-003',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Original Corp',
            'country' => 'Japan',
            'document_date' => '2026-09-04',
            'currency' => 'USD',
            'subtotal' => 200.00,
            'final_total' => 200.00,
            'current_version' => 1,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $updatePayload = [
            'document_number' => 'PI-2026-003',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Original Corp',
            'country' => 'Japan',
            'document_date' => '2026-09-04',
            'currency' => 'USD',
            'items' => [
                [
                    'item_code' => 'SKU-NEW',
                    'description' => 'Updated Hardware',
                    'unit_amount' => '4',
                    'unit_price' => '75.00', // 300.00
                ],
                [
                    'item_code' => 'DISCOUNT',
                    'description' => 'Volume Discount',
                    'unit_amount' => '1',
                    'unit_price' => '-30.00', // -30.00
                ],
            ],
            // final_total: 300 - 30 = 270.00
            'final_total' => '270.00',
        ];

        $response = $this->actingAs($user)->put(route('documents.update', $document), $updatePayload);

        $document->refresh();
        $response->assertRedirect(route('documents.show', $document));

        $this->assertEquals(270.00, (float) $document->subtotal);
        $this->assertEquals(270.00, (float) $document->final_total);
    }
}
