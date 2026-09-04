<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\ShipmentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentOrderDocumentLinkageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'editor']);
    }

    public function test_can_link_pi_invoice_and_packing_list_to_shipment_order(): void
    {
        $pi = Document::create([
            'document_number' => 'E26201',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Gulf Trading LLC',
            'country' => 'United Arab Emirates',
            'currency' => 'USD',
            'document_date' => '2026-09-01',
            'created_by' => $this->user->id,
        ]);

        $invoice = Document::create([
            'document_number' => 'N26001',
            'document_type' => 'invoice',
            'company_name' => 'Gulf Trading LLC',
            'country' => 'United Arab Emirates',
            'currency' => 'USD',
            'document_date' => '2026-09-02',
            'created_by' => $this->user->id,
        ]);

        $packingList = Document::create([
            'document_number' => 'W26001',
            'document_type' => 'packing_list',
            'company_name' => 'Gulf Trading LLC',
            'country' => 'United Arab Emirates',
            'currency' => 'USD',
            'document_date' => '2026-09-02',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('shipment-orders.store'), [
            'order_number' => 'ORD-LINK-001',
            'document_id' => $pi->id,
            'invoice_document_id' => $invoice->id,
            'packing_list_document_id' => $packingList->id,
            'company_name' => 'Gulf Trading LLC',
            'country' => 'United Arab Emirates',
            'payment_status' => 'pending',
            'currency' => 'USD',
        ]);

        $response->assertRedirect();

        $order = ShipmentOrder::where('order_number', 'ORD-LINK-001')->first();
        $this->assertNotNull($order);
        $this->assertEquals($pi->id, $order->document_id);
        $this->assertEquals($invoice->id, $order->invoice_document_id);
        $this->assertEquals($packingList->id, $order->packing_list_document_id);
        $this->assertEquals('N26001', $order->linked_invoice_no);
        $this->assertEquals('W26001', $order->linked_packing_list_no);
        $this->assertEquals('E26201', $order->proforma_invoice_no);

        // Test resolvers
        $this->assertEquals($pi->id, $order->resolved_proforma_document?->id);
        $this->assertEquals($invoice->id, $order->resolved_invoice_document?->id);
        $this->assertEquals($packingList->id, $order->resolved_packing_list_document?->id);
    }

    public function test_auto_resolves_documents_when_typed_as_strings(): void
    {
        $invoice = Document::create([
            'document_number' => 'N26099',
            'document_type' => 'invoice',
            'company_name' => 'Al Hilal Enterprises',
            'country' => 'Saudi Arabia',
            'currency' => 'USD',
            'document_date' => '2026-09-01',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('shipment-orders.store'), [
            'order_number' => 'ORD-TYPED-001',
            'company_name' => 'Al Hilal Enterprises',
            'country' => 'Saudi Arabia',
            'linked_invoice_no' => 'N26099',
            'payment_status' => 'pending',
            'currency' => 'USD',
        ]);

        $response->assertRedirect();

        $order = ShipmentOrder::where('order_number', 'ORD-TYPED-001')->first();
        $this->assertNotNull($order);
        $this->assertEquals($invoice->id, $order->invoice_document_id);
        $this->assertEquals($invoice->id, $order->resolved_invoice_document?->id);
    }

    public function test_bidirectional_visibility_from_invoice_and_packing_list_views(): void
    {
        $invoice = Document::create([
            'document_number' => 'N26777',
            'document_type' => 'invoice',
            'company_name' => 'Emirates Global',
            'country' => 'UAE',
            'currency' => 'USD',
            'document_date' => '2026-09-02',
            'created_by' => $this->user->id,
        ]);

        $packingList = Document::create([
            'document_number' => 'W26777',
            'document_type' => 'packing_list',
            'company_name' => 'Emirates Global',
            'country' => 'UAE',
            'currency' => 'USD',
            'document_date' => '2026-09-02',
            'created_by' => $this->user->id,
        ]);

        $order = ShipmentOrder::create([
            'order_number' => 'ORD-BIDI-001',
            'company_name' => 'Emirates Global',
            'country' => 'UAE',
            'invoice_document_id' => $invoice->id,
            'packing_list_document_id' => $packingList->id,
            'linked_invoice_no' => 'N26777',
            'linked_packing_list_no' => 'W26777',
            'payment_status' => 'pending',
            'currency' => 'USD',
            'created_by' => $this->user->id,
        ]);

        // Verify all_connected_shipment_orders on invoice
        $connectedToInvoice = $invoice->all_connected_shipment_orders;
        $this->assertTrue($connectedToInvoice->contains('id', $order->id));

        // Verify all_connected_shipment_orders on packing list
        $connectedToPL = $packingList->all_connected_shipment_orders;
        $this->assertTrue($connectedToPL->contains('id', $order->id));

        // View invoice page and verify it sees the order tracker
        $responseInv = $this->actingAs($this->user)->get(route('documents.show', $invoice));
        $responseInv->assertOk();
        $responseInv->assertSee('ORD-BIDI-001');

        // View packing list page and verify it sees the order tracker
        $responsePL = $this->actingAs($this->user)->get(route('documents.show', $packingList));
        $responsePL->assertOk();
        $responsePL->assertSee('ORD-BIDI-001');
    }

    public function test_can_view_edit_page_and_update_linked_documents(): void
    {
        $order = ShipmentOrder::create([
            'order_number' => 'ORD-EDIT-001',
            'company_name' => 'Initial Company',
            'country' => 'Oman',
            'payment_status' => 'pending',
            'currency' => 'USD',
            'created_by' => $this->user->id,
        ]);

        $newInvoice = Document::create([
            'document_number' => 'N26888',
            'document_type' => 'invoice',
            'company_name' => 'Initial Company',
            'country' => 'Oman',
            'currency' => 'USD',
            'document_date' => '2026-09-03',
            'created_by' => $this->user->id,
        ]);

        // Edit page
        $editResponse = $this->actingAs($this->user)->get(route('shipment-orders.edit', $order));
        $editResponse->assertOk();
        $editResponse->assertSee('Edit Shipment Order');

        // Update with new invoice
        $updateResponse = $this->actingAs($this->user)->put(route('shipment-orders.update', $order), [
            'company_name' => 'Initial Company',
            'country' => 'Oman',
            'status' => 'active',
            'payment_status' => 'pending',
            'currency' => 'USD',
            'invoice_document_id' => $newInvoice->id,
        ]);

        $updateResponse->assertRedirect(route('shipment-orders.show', $order));

        $order->refresh();
        $this->assertEquals($newInvoice->id, $order->invoice_document_id);
        $this->assertEquals('N26888', $order->linked_invoice_no);

        // Cockpit page should display the linked invoice with a view link
        $showResponse = $this->actingAs($this->user)->get(route('shipment-orders.show', $order));
        $showResponse->assertOk();
        $showResponse->assertSee('N26888');
        $showResponse->assertSee('View Invoice &rarr;', false);
    }
}
