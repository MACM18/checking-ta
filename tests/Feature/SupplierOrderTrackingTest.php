<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\User;
use App\Services\DocumentTypeDetector;
use App\Services\SupplierOrderFulfillmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected SupplierOrderFulfillmentService $fulfillmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->fulfillmentService = app(SupplierOrderFulfillmentService::class);
    }

    public function test_document_type_detector_identifies_b_order_and_factory_invoice(): void
    {
        // B prefix -> supplier_order
        $this->assertEquals(Document::TYPE_SUPPLIER_ORDER, DocumentTypeDetector::detect('B26001')['type']);
        $this->assertEquals(Document::TYPE_SUPPLIER_ORDER, DocumentTypeDetector::detect('B-902')['type']);

        // F prefix -> factory_invoice
        $this->assertEquals(Document::TYPE_FACTORY_INVOICE, DocumentTypeDetector::detect('F26001')['type']);
        $this->assertEquals(Document::TYPE_FACTORY_INVOICE, DocumentTypeDetector::detect('FAC-1002')['type']);
        $this->assertEquals(Document::TYPE_FACTORY_INVOICE, DocumentTypeDetector::detect('INV-F882')['type']);
    }

    public function test_can_create_supplier_order_as_quantity_only_document(): void
    {
        $payload = [
            'document_number' => 'B26001',
            'document_type' => Document::TYPE_SUPPLIER_ORDER,
            'company_name' => 'Apex Factory Ltd',
            'country' => 'China',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'items' => [
                [
                    'item_code' => 'PUMP-100',
                    'description' => 'Industrial Hydraulic Pump',
                    'unit_amount' => 50,
                    'unit_price' => 0,
                ],
                [
                    'item_code' => 'VALVE-200',
                    'description' => 'Pressure Relief Valve',
                    'unit_amount' => 100,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/documents', $payload);
        $response->assertRedirect();

        $order = Document::where('document_number', 'B26001')->first();
        $this->assertNotNull($order);
        $this->assertEquals(Document::TYPE_SUPPLIER_ORDER, $order->document_type);
        $this->assertTrue($order->isQuantityOnly());
        $this->assertTrue($order->isSupplierOrder());
        $this->assertFalse($order->hasPrices());
        $this->assertEquals(0, (float) $order->subtotal);
        $this->assertEquals(0, (float) $order->final_total);
        $this->assertCount(2, $order->items);

        $pump = $order->items()->where('item_code', 'PUMP-100')->first();
        $this->assertEquals(50, (float) $pump->unit_amount);
        $this->assertEquals(0, (float) $pump->unit_price);
        $this->assertEquals(0, (float) $pump->total_amount);
    }

    public function test_can_create_supplier_order_with_optional_pricing(): void
    {
        $payload = [
            'document_number' => 'B26001-P',
            'document_type' => Document::TYPE_SUPPLIER_ORDER,
            'company_name' => 'Apex Factory Ltd',
            'country' => 'China',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'items' => [
                [
                    'item_code' => 'PUMP-100',
                    'description' => 'Industrial Hydraulic Pump',
                    'unit_amount' => 10,
                    'unit_price' => 150.00,
                ],
                [
                    'item_code' => 'VALVE-200',
                    'description' => 'Pressure Relief Valve',
                    'unit_amount' => 20,
                    'unit_price' => 25.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/documents', $payload);
        $response->assertRedirect();

        $order = Document::where('document_number', 'B26001-P')->first();
        $this->assertNotNull($order);
        $this->assertEquals(Document::TYPE_SUPPLIER_ORDER, $order->document_type);
        $this->assertTrue($order->isQuantityOnly());
        $this->assertTrue($order->hasPrices());
        $this->assertEquals(2000.00, (float) $order->subtotal);
        $this->assertEquals(2000.00, (float) $order->final_total);

        $pump = $order->items()->where('item_code', 'PUMP-100')->first();
        $this->assertEquals(150.00, (float) $pump->unit_price);
        $this->assertEquals(1500.00, (float) $pump->total_amount);
    }

    public function test_can_create_factory_invoice_linked_to_supplier_order(): void
    {
        // 1. Create Supplier Order
        $order = Document::create([
            'document_number' => 'B26002',
            'document_type' => Document::TYPE_SUPPLIER_ORDER,
            'company_name' => 'Apex Factory Ltd',
            'country' => 'China',
            'document_date' => Carbon::now(),
            'currency' => 'USD',
            'subtotal' => 0,
            'final_total' => 0,
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $order->id,
            'item_code' => 'BEARING-1',
            'description' => 'High Speed Bearing',
            'unit_amount' => 200,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        // 2. Create Factory Invoice linking to B26002
        $payload = [
            'document_number' => 'F26001',
            'document_type' => Document::TYPE_FACTORY_INVOICE,
            'source_document_number' => 'B26002',
            'company_name' => 'Apex Factory Ltd',
            'country' => 'China',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'items' => [
                [
                    'item_code' => 'BEARING-1',
                    'description' => 'High Speed Bearing (First Batch)',
                    'unit_amount' => 120,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/documents', $payload);
        $response->assertRedirect();

        $invoice = Document::where('document_number', 'F26001')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(Document::TYPE_FACTORY_INVOICE, $invoice->document_type);
        $this->assertEquals('B26002', $invoice->source_document_number);
        $this->assertEquals($order->id, $invoice->source_document_id);
        $this->assertTrue($invoice->isFactoryInvoice());
        $this->assertTrue($invoice->isQuantityOnly());
    }

    public function test_fulfillment_service_calculates_line_item_and_overall_reconciliation(): void
    {
        // Create B order: 100 PUMP, 50 VALVE
        $order = Document::create([
            'document_number' => 'B26003',
            'document_type' => Document::TYPE_SUPPLIER_ORDER,
            'company_name' => 'Mega Supplier Corp',
            'country' => 'Germany',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $order->id,
            'item_code' => 'PUMP-A',
            'description' => 'Pump Model A',
            'unit_amount' => 100,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        DocumentItem::create([
            'document_id' => $order->id,
            'item_code' => 'VALVE-B',
            'description' => 'Valve Model B',
            'unit_amount' => 50,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        // Invoice 1: 60 PUMP-A
        $inv1 = Document::create([
            'document_number' => 'F26002',
            'document_type' => Document::TYPE_FACTORY_INVOICE,
            'source_document_number' => 'B26003',
            'source_document_id' => $order->id,
            'company_name' => 'Mega Supplier Corp',
            'country' => 'Germany',
            'document_date' => Carbon::now()->subDays(3),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $inv1->id,
            'item_code' => 'PUMP-A',
            'description' => 'Pump Model A',
            'unit_amount' => 60,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        // Partial fulfillment check
        $summary1 = $this->fulfillmentService->getOrderSheetSummary($order);
        $this->assertEquals('partially_received', $summary1['overall_status']);
        $this->assertEquals(150, $summary1['total_ordered_qty']);
        $this->assertEquals(60, $summary1['total_received_qty']);
        $this->assertEquals(90, $summary1['total_remaining_qty']);
        $this->assertEquals(40.0, $summary1['fulfillment_percentage']);

        // Check line items
        $pumpItem = collect($summary1['items'])->firstWhere('item_code', 'PUMP-A');
        $this->assertEquals(100, $pumpItem['ordered_qty']);
        $this->assertEquals(60, $pumpItem['received_qty']);
        $this->assertEquals(40, $pumpItem['remaining_qty']);
        $this->assertEquals('partially_received', $pumpItem['status']);

        $valveItem = collect($summary1['items'])->firstWhere('item_code', 'VALVE-B');
        $this->assertEquals(50, $valveItem['ordered_qty']);
        $this->assertEquals(0, $valveItem['received_qty']);
        $this->assertEquals(50, $valveItem['remaining_qty']);
        $this->assertEquals('pending', $valveItem['status']);

        // Invoice 2: 40 PUMP-A and 50 VALVE-B (Full completion)
        $inv2 = Document::create([
            'document_number' => 'F26003',
            'document_type' => Document::TYPE_FACTORY_INVOICE,
            'source_document_number' => 'B26003',
            'source_document_id' => $order->id,
            'company_name' => 'Mega Supplier Corp',
            'country' => 'Germany',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $inv2->id,
            'item_code' => 'PUMP-A',
            'description' => 'Pump Model A final',
            'unit_amount' => 40,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        DocumentItem::create([
            'document_id' => $inv2->id,
            'item_code' => 'VALVE-B',
            'description' => 'Valve Model B full',
            'unit_amount' => 50,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        $summary2 = $this->fulfillmentService->getOrderSheetSummary($order);
        $this->assertEquals('completed', $summary2['overall_status']);
        $this->assertEquals(150, $summary2['total_received_qty']);
        $this->assertEquals(0, $summary2['total_remaining_qty']);
        $this->assertEquals(100.0, $summary2['fulfillment_percentage']);
    }

    public function test_supplier_order_tracker_routes(): void
    {
        $order = Document::create([
            'document_number' => 'B26004',
            'document_type' => Document::TYPE_SUPPLIER_ORDER,
            'company_name' => 'Tokyo Tech Supplies',
            'country' => 'Japan',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $order->id,
            'item_code' => 'SENSOR-X',
            'description' => 'Laser Range Sensor',
            'unit_amount' => 30,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        // 1. Dashboard index
        $indexRes = $this->actingAs($this->user)->get('/supplier-orders');
        $indexRes->assertOk();
        $indexRes->assertSee('Supplier Orders &amp; Shipment Tracker', false);
        $indexRes->assertSee('B26004');
        $indexRes->assertSee('Tokyo Tech Supplies');
        $indexRes->assertSee('Remaining Items Summary');

        // 2. Order Sheet Detail reconciliation
        $showRes = $this->actingAs($this->user)->get(route('supplier-orders.show', $order));
        $showRes->assertOk();
        $showRes->assertSee('B26004');
        $showRes->assertSee('Supplier Order Sheet');
        $showRes->assertSee('SENSOR-X');
        $showRes->assertSee('Receive Shipment');

        // 3. Print verification sheet
        $printRes = $this->actingAs($this->user)->get(route('supplier-orders.print-sheet', $order));
        $printRes->assertOk();
        $printRes->assertSee('PURCHASE ORDER &amp; FACTORY SHIPMENT RECONCILIATION SHEET', false);
        $printRes->assertSee('B26004');
        $printRes->assertSee('SENSOR-X');
    }

    public function test_document_create_prefills_remaining_items_from_source_b_order(): void
    {
        $order = Document::create([
            'document_number' => 'B26005',
            'document_type' => Document::TYPE_SUPPLIER_ORDER,
            'company_name' => 'Precision Tools GMBH',
            'country' => 'Germany',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $order->id,
            'item_code' => 'DRILL-BIT-10',
            'description' => '10mm Drill Bit',
            'unit_amount' => 500,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        // Create partial invoice of 200
        $inv = Document::create([
            'document_number' => 'F26005',
            'document_type' => Document::TYPE_FACTORY_INVOICE,
            'source_document_number' => 'B26005',
            'source_document_id' => $order->id,
            'company_name' => 'Precision Tools GMBH',
            'country' => 'Germany',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $inv->id,
            'item_code' => 'DRILL-BIT-10',
            'description' => '10mm Drill Bit',
            'unit_amount' => 200,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        // Access create form to record next shipment: should pre-fill remaining 300
        $response = $this->actingAs($this->user)->get("/documents/create?source_order_id={$order->id}&type=factory_invoice");
        $response->assertOk();
        $response->assertSee('B26005');
        $response->assertSee('DRILL-BIT-10');
        $response->assertSee('300'); // Remaining quantity
    }

    public function test_create_factory_invoice_page_loads_without_source(): void
    {
        $response = $this->actingAs($this->user)->get('/documents/create?type=factory_invoice');
        $response->assertOk();
        $response->assertSee('Import from Order Sheet');
    }

    public function test_get_source_data_api_calculates_remaining_quantities_for_supplier_order(): void
    {
        $order = Document::create([
            'document_number' => 'B26006',
            'document_type' => Document::TYPE_SUPPLIER_ORDER,
            'company_name' => 'Supplier AG',
            'country' => 'Switzerland',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $order->id,
            'item_code' => 'VALVE-50',
            'description' => '50mm High Pressure Valve',
            'unit_amount' => 100,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        // Partial factory invoice with 40 received
        $inv = Document::create([
            'document_number' => 'F26006',
            'document_type' => Document::TYPE_FACTORY_INVOICE,
            'source_document_number' => 'B26006',
            'source_document_id' => $order->id,
            'company_name' => 'Supplier AG',
            'country' => 'Switzerland',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $inv->id,
            'item_code' => 'VALVE-50',
            'description' => '50mm High Pressure Valve',
            'unit_amount' => 40,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/documents/source-data/{$order->id}");
        $response->assertOk();
        $response->assertJsonPath('items.0.unit_amount', 60);
        $response->assertJsonPath('items.0.remaining_qty', 60);
        $response->assertJsonPath('items.0.ordered_qty', 100);
    }

    public function test_factory_invoice_can_fulfill_multiple_order_sheets(): void
    {
        $order1 = Document::create([
            'document_number' => 'B26010',
            'document_type' => Document::TYPE_SUPPLIER_ORDER,
            'company_name' => 'Multi Supplier Co',
            'country' => 'Germany',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $order1->id,
            'item_code' => 'PART-A',
            'description' => 'Part A',
            'unit_amount' => 50,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        $order2 = Document::create([
            'document_number' => 'B26011',
            'document_type' => Document::TYPE_SUPPLIER_ORDER,
            'company_name' => 'Multi Supplier Co',
            'country' => 'Germany',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $order2->id,
            'item_code' => 'PART-B',
            'description' => 'Part B',
            'unit_amount' => 80,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        // 1 Factory Invoice fulfilling items from BOTH Order Sheets
        $inv = Document::create([
            'document_number' => 'F26010',
            'document_type' => Document::TYPE_FACTORY_INVOICE,
            'source_document_number' => 'B26010, B26011',
            'company_name' => 'Multi Supplier Co',
            'country' => 'Germany',
            'document_date' => Carbon::now(),
            'created_by' => $this->user->id,
        ]);

        DocumentItem::create([
            'document_id' => $inv->id,
            'item_code' => 'PART-A',
            'description' => 'Part A',
            'unit_amount' => 50,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        DocumentItem::create([
            'document_id' => $inv->id,
            'item_code' => 'PART-B',
            'description' => 'Part B',
            'unit_amount' => 80,
            'unit_price' => 0,
            'total_amount' => 0,
        ]);

        $service = app(SupplierOrderFulfillmentService::class);
        $summary1 = $service->getOrderSheetSummary($order1);
        $summary2 = $service->getOrderSheetSummary($order2);

        $this->assertEquals('completed', $summary1['overall_status']);
        $this->assertEquals(50, $summary1['total_received_qty']);
        $this->assertEquals(1, count($summary1['factory_invoices']));

        $this->assertEquals('completed', $summary2['overall_status']);
        $this->assertEquals(80, $summary2['total_received_qty']);
        $this->assertEquals(1, count($summary2['factory_invoices']));
    }

    public function test_factory_invoice_with_order_sheet_references_and_grouping(): void
    {
        $payload = [
            'document_number' => 'F26099',
            'document_type' => Document::TYPE_FACTORY_INVOICE,
            'source_document_number' => 'B26001, B26002',
            'company_name' => 'Apex Factory Ltd',
            'country' => 'China',
            'document_date' => now()->format('Y-m-d'),
            'currency' => 'USD',
            'items' => [
                [
                    'item_code' => 'ITEM-A',
                    'description' => 'Item A from B26001',
                    'unit_amount' => 15,
                    'unit_price' => 100.00,
                    'order_sheet_reference' => 'B26001',
                ],
                [
                    'item_code' => 'ITEM-B',
                    'description' => 'Item B from B26001',
                    'unit_amount' => 25,
                    'unit_price' => 50.00,
                    'order_sheet_reference' => 'B26001',
                ],
                [
                    'item_code' => 'ITEM-C',
                    'description' => 'Item C from B26002',
                    'unit_amount' => 40,
                    'unit_price' => 30.00,
                    'order_sheet_reference' => 'B26002',
                ],
                [
                    'item_code' => 'ITEM-D',
                    'description' => 'Direct Factory Item without Order Sheet',
                    'unit_amount' => 10,
                    'unit_price' => 20.00,
                    'order_sheet_reference' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/documents', $payload);
        $response->assertRedirect();

        $invoice = Document::where('document_number', 'F26099')->first();
        $this->assertNotNull($invoice);
        $this->assertTrue($invoice->isFactoryInvoice());
        $this->assertTrue($invoice->hasPrices());
        $this->assertTrue($invoice->hasOrderSheetGroups());
        // 15*100 (1500) + 25*50 (1250) + 40*30 (1200) + 10*20 (200) = 4150
        $this->assertEquals(4150.00, (float) $invoice->final_total);

        // Test itemsGroupedByOrderSheet helper
        $grouped = $invoice->itemsGroupedByOrderSheet();
        $this->assertArrayHasKey('B26001', $grouped);
        $this->assertArrayHasKey('B26002', $grouped);
        $this->assertArrayHasKey('Direct / Unassigned', $grouped);

        $this->assertCount(2, $grouped['B26001']);
        $this->assertEquals(40, $grouped['B26001']->sum('unit_amount'));
        $this->assertEquals(2750.00, $grouped['B26001']->sum('total_amount'));

        $this->assertCount(1, $grouped['B26002']);
        $this->assertEquals(40, $grouped['B26002']->sum('unit_amount'));
        $this->assertEquals(1200.00, $grouped['B26002']->sum('total_amount'));

        $this->assertCount(1, $grouped['Direct / Unassigned']);
        $this->assertEquals(10, $grouped['Direct / Unassigned']->sum('unit_amount'));

        // Test show and print views render successfully with grouped data
        $showResponse = $this->actingAs($this->user)->get(route('documents.show', $invoice));
        $showResponse->assertOk();
        $showResponse->assertSee('B26001');
        $showResponse->assertSee('B26002');
        $showResponse->assertSee('Order Sheet:');
        $showResponse->assertSee('Grouped by Order Sheet');

        $printResponse = $this->actingAs($this->user)->get(route('documents.print', $invoice));
        $printResponse->assertOk();
        $printResponse->assertSee('Order Sheet Group Breakdown');
        $printResponse->assertSee('B26001');
        $printResponse->assertSee('B26002');
    }
}
