<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatestPiCustomerLookupTest extends TestCase
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

    public function test_create_view_receives_recent_customers_for_datalist(): void
    {
        Document::create([
            'document_number' => 'E26001',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Acme Corporation',
            'country' => 'United Arab Emirates',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'created_by' => $this->user->id,
        ]);

        Document::create([
            'document_number' => 'N10001',
            'document_type' => Document::TYPE_INVOICE,
            'company_name' => 'Apex Global Logistics',
            'country' => 'Saudi Arabia',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('documents.create'));

        $response->assertOk();
        $response->assertViewHas('recentCustomers');
        $recentCustomers = $response->viewData('recentCustomers');
        $this->assertTrue($recentCustomers->contains('Acme Corporation'));
        $this->assertTrue($recentCustomers->contains('Apex Global Logistics'));
        $response->assertSee('id="recentCustomersList"', false);
        $response->assertSee('value="Acme Corporation"', false);
    }

    public function test_latest_pi_api_returns_not_found_when_no_pi_exists(): void
    {
        // Only an Invoice exists for this customer, no PI
        Document::create([
            'document_number' => 'N10002',
            'document_type' => Document::TYPE_INVOICE,
            'company_name' => 'Delta Marine Solutions',
            'country' => 'Oman',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.documents.latestPi', [
            'company_name' => 'Delta Marine Solutions',
        ]));

        $response->assertOk();
        $response->assertJson([
            'found' => false,
        ]);
    }

    public function test_latest_pi_api_returns_latest_pi_for_matching_customer(): void
    {
        // Older PI
        Document::create([
            'document_number' => 'E26010',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'document_date' => Carbon::now()->subDays(10),
            'currency' => 'USD',
            'final_total' => 5000.00,
            'price_list' => 'Price List',
            'created_by' => $this->user->id,
        ]);

        // Newer PI
        $newerPi = Document::create([
            'document_number' => 'E26020',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Gulf Apex Global',
            'country' => 'United Arab Emirates',
            'document_date' => Carbon::now()->subDay(),
            'currency' => 'USD',
            'final_total' => 12500.00,
            'price_list' => 'Union',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.documents.latestPi', [
            'company_name' => 'Gulf Apex Global',
        ]));

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'document_number' => 'E26020',
            'company_name' => 'Gulf Apex Global',
            'price_list' => 'Union',
            'final_total' => 12500.00,
            'formatted_final_total' => '$12,500.00',
            'url' => route('documents.show', $newerPi),
        ]);
    }

    public function test_latest_pi_api_respects_given_price_list_filter(): void
    {
        // PI with Price List
        Document::create([
            'document_number' => 'E26030',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Global Marine Supplies',
            'country' => 'Kuwait',
            'document_date' => Carbon::now()->subDays(5),
            'currency' => 'USD',
            'final_total' => 7000.00,
            'price_list' => 'Price List',
            'created_by' => $this->user->id,
        ]);

        // PI with Union
        Document::create([
            'document_number' => 'E26035',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Global Marine Supplies',
            'country' => 'Kuwait',
            'document_date' => Carbon::now()->subDays(2),
            'currency' => 'USD',
            'final_total' => 9500.00,
            'price_list' => 'Union',
            'created_by' => $this->user->id,
        ]);

        // Search requesting 'Price List'
        $response = $this->actingAs($this->user)->getJson(route('api.documents.latestPi', [
            'company_name' => 'Global Marine Supplies',
            'price_list' => 'Price List',
        ]));

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'document_number' => 'E26030',
            'price_list' => 'Price List',
            'matched_requested_price_list' => true,
        ]);

        // Search requesting 'Union'
        $responseUnion = $this->actingAs($this->user)->getJson(route('api.documents.latestPi', [
            'company_name' => 'Global Marine Supplies',
            'price_list' => 'Union',
        ]));

        $responseUnion->assertOk();
        $responseUnion->assertJson([
            'found' => true,
            'document_number' => 'E26035',
            'price_list' => 'Union',
            'matched_requested_price_list' => true,
        ]);
    }

    public function test_latest_pi_api_falls_back_to_latest_pi_when_specific_price_list_has_no_match(): void
    {
        Document::create([
            'document_number' => 'E26040',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Pacific Oil Co',
            'country' => 'Bahrain',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'final_total' => 4500.00,
            'price_list' => 'Price List',
            'created_by' => $this->user->id,
        ]);

        // Query with 'Union Special' which this customer does not have
        $response = $this->actingAs($this->user)->getJson(route('api.documents.latestPi', [
            'company_name' => 'Pacific Oil Co',
            'price_list' => 'Union Special',
        ]));

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'document_number' => 'E26040',
            'price_list' => 'Price List',
            'matched_requested_price_list' => false,
            'requested_price_list' => 'Union Special',
        ]);
    }

    public function test_document_store_and_update_persists_price_list(): void
    {
        $storeData = [
            'document_number' => 'E26050',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Orient Logistics LLC',
            'country' => 'United Arab Emirates',
            'document_date' => Carbon::now()->format('Y-m-d'),
            'currency' => 'USD',
            'price_list' => 'Union Special',
            'items' => [
                [
                    'item_code' => 'TEST-ITEM-1',
                    'description' => 'Test Item',
                    'unit_amount' => 10,
                    'unit_price' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('documents.store'), $storeData);

        $response->assertRedirect();
        $document = Document::where('document_number', 'E26050')->first();
        $this->assertNotNull($document);
        $this->assertEquals('Union Special', $document->price_list);

        // Test update persists modified price list
        $updateData = array_merge($storeData, [
            'price_list' => 'Union',
        ]);

        $updateResponse = $this->actingAs($this->user)->put(route('documents.update', $document), $updateData);
        $updateResponse->assertRedirect();

        $document->refresh();
        $this->assertEquals('Union', $document->price_list);
    }

    public function test_document_effective_price_list_infers_from_catalogue_items(): void
    {
        // Create catalog items with prices in 'Union Special'
        $item = Item::create([
            'item_code' => 'UNION-ITEM-99',
            'description' => 'Union Spec Item',
        ]);

        ItemPrice::create([
            'item_id' => $item->id,
            'item_code' => 'UNION-ITEM-99',
            'price_list' => 'Union Special',
            'price_label' => 'USD 30%',
            'price' => 100.00,
            'currency' => 'USD',
        ]);

        // Document without explicit price_list
        $document = Document::create([
            'document_number' => 'E26060',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Legacy Import Corp',
            'country' => 'Qatar',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'price_list' => null,
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $document->id,
            'item_code' => 'UNION-ITEM-99',
            'description' => 'Union Spec Item',
            'unit_amount' => 5,
            'unit_price' => 100.00,
            'total_amount' => 500.00,
        ]);

        $document->load('items');
        $this->assertEquals('Union Special', $document->effective_price_list);
    }

    public function test_latest_pi_displays_and_returns_price_label_not_price_list(): void
    {
        Document::create([
            'document_number' => 'E26070',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Apex Tech Industries',
            'country' => 'UAE',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'price_list' => 'Price List',
            'price_label' => 'USD 40%',
            'final_total' => 8800.00,
            'created_by' => $this->user->id,
        ]);

        $apiResponse = $this->actingAs($this->user)->getJson(route('api.documents.latestPi', [
            'company_name' => 'Apex Tech Industries',
        ]));

        $apiResponse->assertOk();
        $apiResponse->assertJson([
            'found' => true,
            'document_number' => 'E26070',
            'country' => 'UAE',
            'currency' => 'USD',
            'price_label' => 'USD 40%',
        ]);

        $createResponse = $this->actingAs($this->user)->get(route('documents.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Price Label: <span class="ml-1" x-text="latestPiDoc.price_label">', false);
        $createResponse->assertDontSee('Price List: <span class="ml-1" x-text="latestPiDoc.price_list">', false);
        // Verify inline percentage/amount adjustment input markup is present
        $createResponse->assertSee("setCalcMode(item, 'percentage')", false);
        $createResponse->assertSee("setCalcMode(item, 'fixed')", false);
        $createResponse->assertSee('x-model.number="item.percentage"', false);
    }
}
