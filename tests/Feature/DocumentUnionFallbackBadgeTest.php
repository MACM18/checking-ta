<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentUnionFallbackBadgeTest extends TestCase
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

    public function test_document_item_is_union_fallback_helper(): void
    {
        $doc = Document::create([
            'document_number' => 'E26901',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Test Customer',
            'country' => 'UAE',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'price_list' => 'Price List',
            'created_by' => $this->user->id,
        ]);

        // Explicit is_fallback flag
        $itemFallback = DocumentItem::create([
            'document_id' => $doc->id,
            'item_code' => 'UNION-SKU-1',
            'description' => 'Union Item',
            'unit_amount' => 1,
            'unit_price' => 50.00,
            'price_list' => 'Union Special',
            'is_fallback' => true,
            'total_amount' => 50.00,
        ]);
        $this->assertTrue($itemFallback->isUnionFallback());

        // Price list has Union, document is 'Price List'
        $itemPriceListUnion = DocumentItem::create([
            'document_id' => $doc->id,
            'item_code' => 'UNION-SKU-2',
            'description' => 'Union Sourced',
            'unit_amount' => 1,
            'unit_price' => 75.00,
            'price_list' => 'Union',
            'is_fallback' => false,
            'total_amount' => 75.00,
        ]);
        $this->assertTrue($itemPriceListUnion->isUnionFallback());

        // Regular item in Price List
        $itemRegular = DocumentItem::create([
            'document_id' => $doc->id,
            'item_code' => 'REG-SKU-1',
            'description' => 'Regular Item',
            'unit_amount' => 1,
            'unit_price' => 100.00,
            'price_list' => 'Price List',
            'is_fallback' => false,
            'total_amount' => 100.00,
        ]);
        $this->assertFalse($itemRegular->isUnionFallback());
    }

    public function test_user_can_create_document_persisting_price_list_and_is_fallback(): void
    {
        $payload = [
            'document_number' => 'E26902',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Gulf Apex Trading',
            'country' => 'United Arab Emirates',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'price_list' => 'Price List',
            'final_total' => 350.00,
            'items' => [
                [
                    'item_code' => 'REG-1',
                    'description' => 'Regular Item',
                    'unit_amount' => 1,
                    'unit_price' => 100,
                    'price_list' => 'Price List',
                    'is_fallback' => 0,
                ],
                [
                    'item_code' => 'UNION-FB-1',
                    'description' => 'Fallback Item',
                    'unit_amount' => 2,
                    'unit_price' => 125,
                    'price_list' => 'Union Special',
                    'is_fallback' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/documents', $payload);
        $response->assertRedirect();

        $doc = Document::where('document_number', 'E26902')->first();
        $this->assertNotNull($doc);

        $regItem = $doc->items()->where('item_code', 'REG-1')->first();
        $this->assertNotNull($regItem);
        $this->assertEquals('Price List', $regItem->price_list);
        $this->assertFalse($regItem->is_fallback);

        $fbItem = $doc->items()->where('item_code', 'UNION-FB-1')->first();
        $this->assertNotNull($fbItem);
        $this->assertEquals('Union Special', $fbItem->price_list);
        $this->assertTrue($fbItem->is_fallback);
        $this->assertTrue($fbItem->isUnionFallback());
    }

    public function test_document_show_renders_union_badge_for_fallback_items(): void
    {
        $doc = Document::create([
            'document_number' => 'E26903',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Apex Industrial',
            'country' => 'UAE',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'price_list' => 'Price List',
            'final_total' => 150.00,
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $doc->id,
            'item_code' => 'UNION-FALLBACK-PART',
            'description' => 'Union Fallback Part',
            'unit_amount' => 1,
            'unit_price' => 150.00,
            'price_list' => 'Union Special',
            'is_fallback' => true,
            'total_amount' => 150.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('documents.show', $doc));

        $response->assertOk();
        $response->assertSee('UNION-FALLBACK-PART');
        $response->assertSee('Union');
        $response->assertSee('Price sourced from Union list as fallback');
    }

    public function test_get_source_data_endpoint_includes_price_list_and_is_fallback(): void
    {
        $doc = Document::create([
            'document_number' => 'E26904',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Source Testing Co',
            'country' => 'UAE',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $doc->id,
            'item_code' => 'SRC-ITEM-1',
            'description' => 'Source Item',
            'unit_amount' => 2,
            'unit_price' => 45.00,
            'price_list' => 'Union',
            'is_fallback' => true,
            'total_amount' => 90.00,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/documents/source-data/{$doc->document_number}");

        $response->assertOk();
        $response->assertJsonFragment([
            'item_code' => 'SRC-ITEM-1',
            'price_list' => 'Union',
            'is_fallback' => true,
        ]);
    }
}
