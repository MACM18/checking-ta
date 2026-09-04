<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentPackage;
use App\Models\DocumentShipmentCost;
use App\Models\OrderReservation;
use App\Models\OrderReservationItem;
use App\Models\ShipmentOrder;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'editor']);
    }

    public function test_reports_hub_index_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Reports');
        $response->assertSee('Data Exports Center');
        $response->assertSee('Orders Log with Weights', false);
        $response->assertSee('Ongoing Orders Progress');
        $response->assertSee('Master Short Parts Report');
    }

    public function test_can_export_freight_weights_log_to_excel(): void
    {
        $doc = Document::create([
            'document_number' => 'E26901',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Gulf Industrial Tech',
            'country' => 'UAE',
            'document_date' => '2026-09-01',
            'currency' => 'USD',
            'total_net_weight' => 45.25,
            'total_gross_weight' => 52.00,
            'subtotal' => 2400.00,
            'final_total' => 2400.00,
            'current_version' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        DocumentPackage::create([
            'document_id' => $doc->id,
            'package_type' => 'Carton',
            'dimension_type' => 'standard',
            'quantity' => 3,
            'gross_weight_per_pkg_kg' => 17.33,
            'total_gross_weight_kg' => 52.00,
            'volumetric_weight_kg' => 48.00,
            'cbm' => 0.24,
        ]);

        DocumentShipmentCost::create([
            'document_id' => $doc->id,
            'method' => 'DHL',
            'rate_per_kg' => 8.50,
            'system_amount' => 442.00,
            'added_amount' => 58.00,
            'given_amount' => 500.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.freight-weights', ['format' => 'excel']));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Freight_Weights_Log_', $response->headers->get('Content-Disposition'));
    }

    public function test_can_export_freight_weights_log_to_pdf(): void
    {
        Document::create([
            'document_number' => 'E26902',
            'document_type' => Document::TYPE_INVOICE,
            'company_name' => 'Red Sea Marine',
            'country' => 'Saudi Arabia',
            'document_date' => '2026-09-02',
            'currency' => 'USD',
            'total_net_weight' => 12.00,
            'total_gross_weight' => 15.00,
            'subtotal' => 900.00,
            'final_total' => 900.00,
            'current_version' => 1,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.freight-weights', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Freight_Weights_Log_', $response->headers->get('Content-Disposition'));
    }

    public function test_can_export_ongoing_orders_progress_to_excel_and_pdf(): void
    {
        $order = ShipmentOrder::create([
            'order_number' => 'SO-2026-001',
            'company_name' => 'Al Futtaim Engineering',
            'country' => 'UAE',
            'customer_po_number' => 'PO-99120',
            'customer_po_date' => '2026-09-01',
            'payment_status' => 'payment_submitted',
            'carrier_method' => 'Air Freight',
            'tracking_awb_no' => '074-12345678',
            'current_stage' => 3,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $order->milestones()->create([
            'stage_number' => 1,
            'stage_code' => 'pi_sent',
            'stage_name' => 'PI Initialized',
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        // Excel Export
        $excelResponse = $this->actingAs($this->user)->get(route('reports.ongoing-orders', ['format' => 'excel', 'status' => 'active']));
        $excelResponse->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $excelResponse->headers->get('Content-Type'));

        // PDF Export
        $pdfResponse = $this->actingAs($this->user)->get(route('reports.ongoing-orders', ['format' => 'pdf', 'status' => 'active']));
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));
    }

    public function test_can_export_master_shortage_report_to_excel_and_pdf(): void
    {
        $res1 = OrderReservation::create([
            'reservation_number' => 'RES-E26101R',
            'reserve_document_number' => 'E26101R',
            'company_name' => 'Abu Dhabi Petro',
            'status' => OrderReservation::STATUS_HAS_SHORTAGE,
            'total_short_qty' => 5,
            'created_by' => $this->user->id,
        ]);

        $res1->items()->create([
            'item_code' => 'VALVE-90',
            'description' => 'Needle Valve 1/4 inch',
            'requested_qty' => 10,
            'available_qty' => 5,
            'short_qty' => 5,
            'status' => OrderReservationItem::STATUS_SHORT,
            'shortage_reason' => 'Warehouse bin empty',
        ]);

        $res2 = OrderReservation::create([
            'reservation_number' => 'RES-E26102R',
            'reserve_document_number' => 'E26102R',
            'company_name' => 'Dubai Drilling',
            'status' => OrderReservation::STATUS_HAS_SHORTAGE,
            'total_short_qty' => 3,
            'created_by' => $this->user->id,
        ]);

        $res2->items()->create([
            'item_code' => 'VALVE-90',
            'description' => 'Needle Valve 1/4 inch',
            'requested_qty' => 8,
            'available_qty' => 5,
            'short_qty' => 3,
            'status' => OrderReservationItem::STATUS_SHORT,
            'shortage_reason' => 'Supplier delayed',
        ]);

        // Excel Export
        $excelResponse = $this->actingAs($this->user)->get(route('reports.master-shortage', ['format' => 'excel']));
        $excelResponse->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $excelResponse->headers->get('Content-Type'));

        // PDF Export
        $pdfResponse = $this->actingAs($this->user)->get(route('reports.master-shortage', ['format' => 'pdf']));
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));
    }

    public function test_can_export_individual_reservation_shortage_to_excel_and_pdf(): void
    {
        $res = OrderReservation::create([
            'reservation_number' => 'RES-E26200R',
            'reserve_document_number' => 'E26200R',
            'company_name' => 'Sharjah Tech Marine',
            'status' => OrderReservation::STATUS_HAS_SHORTAGE,
            'total_short_qty' => 2,
            'created_by' => $this->user->id,
        ]);

        $res->items()->create([
            'item_code' => 'GASKET-77',
            'description' => 'Teflon Gasket Ring',
            'requested_qty' => 10,
            'available_qty' => 8,
            'short_qty' => 2,
            'status' => OrderReservationItem::STATUS_SHORT,
            'shortage_reason' => 'Damaged 2 pcs in box',
        ]);

        // Excel Export
        $excelResponse = $this->actingAs($this->user)->get(route('reports.reservation-shortage', ['orderReservation' => $res, 'format' => 'excel']));
        $excelResponse->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $excelResponse->headers->get('Content-Type'));

        // PDF Export
        $pdfResponse = $this->actingAs($this->user)->get(route('reports.reservation-shortage', ['orderReservation' => $res, 'format' => 'pdf']));
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));
    }

    public function test_master_shortage_excel_groups_items_omits_duplicate_cells_and_inserts_separator_rows(): void
    {
        $res1 = OrderReservation::create([
            'reservation_number' => 'RES-E26107R',
            'reserve_document_number' => 'E26107R',
            'company_name' => 'Pakarab',
            'reservation_date' => '2026-05-13',
            'status' => OrderReservation::STATUS_HAS_SHORTAGE,
            'total_short_qty' => 26,
            'created_by' => $this->user->id,
        ]);

        $res1->items()->create([
            'item_code' => '101042',
            'requested_qty' => 10,
            'available_qty' => 0,
            'short_qty' => 10,
            'status' => OrderReservationItem::STATUS_SHORT,
        ]);

        $res1->items()->create([
            'item_code' => '101101',
            'requested_qty' => 10,
            'available_qty' => 0,
            'short_qty' => 10,
            'supplier_invoice_no' => '26FZ12',
            'status' => OrderReservationItem::STATUS_SHORT,
        ]);

        $res1->items()->create([
            'item_code' => '104191',
            'requested_qty' => 6,
            'available_qty' => 0,
            'short_qty' => 6,
            'supplier_invoice_no' => '26FZ12',
            'shortage_reason' => 'Supplier delay',
            'status' => OrderReservationItem::STATUS_SHORT,
        ]);

        $res2 = OrderReservation::create([
            'reservation_number' => 'RES-EL26019R',
            'reserve_document_number' => 'EL26019R',
            'company_name' => 'Polly Propelin Bags Ltd.',
            'reservation_date' => '2026-05-13',
            'status' => OrderReservation::STATUS_HAS_SHORTAGE,
            'total_short_qty' => 5,
            'created_by' => $this->user->id,
        ]);

        $res2->items()->create([
            'item_code' => '51110D',
            'requested_qty' => 5,
            'available_qty' => 0,
            'short_qty' => 5,
            'status' => OrderReservationItem::STATUS_SHORT,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.master-shortage', ['format' => 'excel']));
        $response->assertOk();

        // Capture streamed response content
        ob_start();
        $response->sendContent();
        $excelContent = ob_get_clean();

        $tempFile = tempnam(sys_get_temp_dir(), 'excel_test');
        file_put_contents($tempFile, $excelContent);

        $reader = new Xlsx;
        $spreadsheet = $reader->load($tempFile);
        unlink($tempFile);

        $sheet = $spreadsheet->getSheetByName('Short Parts List');
        $this->assertNotNull($sheet);

        // Row 1: Banner
        $this->assertStringContainsString('SHORT PARTS', (string) $sheet->getCell('A1')->getValue());
        $bannerColor = $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB();
        $this->assertEquals('4474A0', strtoupper($bannerColor));

        // Row 2: Headers
        $this->assertEquals('RESERVED DATE', $sheet->getCell('A2')->getValue());
        $this->assertEquals('PROFORMA / RESERVE NO.', $sheet->getCell('B2')->getValue());
        $this->assertEquals('COMPANY NAME', $sheet->getCell('C2')->getValue());
        $this->assertEquals('PART NO.', $sheet->getCell('D2')->getValue());
        $this->assertEquals('QTY', $sheet->getCell('E2')->getValue());
        $this->assertEquals('SUPPLIER / INVOICE NO.', $sheet->getCell('F2')->getValue());
        $this->assertEquals('REMARKS', $sheet->getCell('G2')->getValue());

        $headerColor = $sheet->getStyle('A2')->getFill()->getStartColor()->getRGB();
        $this->assertEquals('FFDA66', strtoupper($headerColor));

        // Row 3: First Item of Pakarab
        $this->assertEquals('5/13/26', $sheet->getCell('A3')->getValue());
        $this->assertEquals('E26107R', $sheet->getCell('B3')->getValue());
        $this->assertEquals('Pakarab', $sheet->getCell('C3')->getValue());
        $this->assertEquals('101042', $sheet->getCell('D3')->getValue());
        $this->assertEquals(10, (float) $sheet->getCell('E3')->getValue());

        // Row 4: Second Item of same reservation (Date, Doc, Company omitted!)
        $this->assertEquals('', (string) $sheet->getCell('A4')->getValue());
        $this->assertEquals('', (string) $sheet->getCell('B4')->getValue());
        $this->assertEquals('', (string) $sheet->getCell('C4')->getValue());
        $this->assertEquals('101101', $sheet->getCell('D4')->getValue());
        $this->assertEquals(10, (float) $sheet->getCell('E4')->getValue());
        $this->assertEquals('26FZ12', $sheet->getCell('F4')->getValue());

        // Row 5: Third Item of same reservation (Date, Doc, Company omitted!)
        $this->assertEquals('', (string) $sheet->getCell('A5')->getValue());
        $this->assertEquals('', (string) $sheet->getCell('B5')->getValue());
        $this->assertEquals('', (string) $sheet->getCell('C5')->getValue());
        $this->assertEquals('104191', $sheet->getCell('D5')->getValue());
        $this->assertEquals(6, (float) $sheet->getCell('E5')->getValue());
        $this->assertEquals('26FZ12', $sheet->getCell('F5')->getValue());
        $this->assertEquals('Supplier delay', $sheet->getCell('G5')->getValue());

        // Row 6: Separator Bar between distinct orders
        $separatorColor = $sheet->getStyle('A6')->getFill()->getStartColor()->getRGB();
        $this->assertEquals('B15E26', strtoupper($separatorColor));

        // Row 7: Next Reservation (Polly Propelin Bags Ltd.)
        $this->assertEquals('5/13/26', $sheet->getCell('A7')->getValue());
        $this->assertEquals('EL26019R', $sheet->getCell('B7')->getValue());
        $this->assertEquals('Polly Propelin Bags Ltd.', $sheet->getCell('C7')->getValue());
        $this->assertEquals('51110D', $sheet->getCell('D7')->getValue());
        $this->assertEquals(5, (float) $sheet->getCell('E7')->getValue());
    }

    public function test_freight_weights_consolidates_order_family_into_single_record_with_pkl_weight_and_shipping_costs(): void
    {
        $pi = Document::create([
            'document_number' => 'E26300',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Saudi Tech Arabian',
            'country' => 'Saudi Arabia',
            'document_date' => '2026-09-01',
            'currency' => 'USD',
            'total_net_weight' => 10.00,
            'total_gross_weight' => 12.00,
            'final_total' => 1200.00,
            'created_by' => $this->user->id,
        ]);

        $invoice = Document::create([
            'document_number' => 'N26300',
            'document_type' => Document::TYPE_INVOICE,
            'source_document_id' => $pi->id,
            'source_document_number' => 'E26300',
            'company_name' => 'Saudi Tech Arabian',
            'country' => 'Saudi Arabia',
            'document_date' => '2026-09-02',
            'currency' => 'USD',
            'total_net_weight' => 10.00,
            'total_gross_weight' => 12.00,
            'final_total' => 1500.00,
            'created_by' => $this->user->id,
        ]);

        $pkl = Document::create([
            'document_number' => 'W26300',
            'document_type' => Document::TYPE_PACKING_LIST,
            'source_document_id' => $pi->id,
            'source_document_number' => 'E26300',
            'company_name' => 'Saudi Tech Arabian',
            'country' => 'Saudi Arabia',
            'document_date' => '2026-09-03',
            'currency' => 'USD',
            'total_net_weight' => 14.50,
            'total_gross_weight' => 18.00,
            'final_total' => 0.00,
            'created_by' => $this->user->id,
        ]);

        DocumentPackage::create([
            'document_id' => $pkl->id,
            'package_type' => 'Wooden Box',
            'dimension_type' => 'standard',
            'quantity' => 2,
            'total_gross_weight_kg' => 18.00,
            'cbm' => 0.35,
        ]);

        $reserve = Document::create([
            'document_number' => 'E26300R',
            'document_type' => Document::TYPE_RESERVE,
            'source_document_id' => $pi->id,
            'source_document_number' => 'E26300',
            'company_name' => 'Saudi Tech Arabian',
            'country' => 'Saudi Arabia',
            'document_date' => '2026-09-01',
            'currency' => 'USD',
            'total_net_weight' => 11.00,
            'total_gross_weight' => 11.00,
            'final_total' => 0.00,
            'created_by' => $this->user->id,
        ]);

        DocumentShipmentCost::create([
            'document_id' => $pi->id,
            'method' => 'air_freight',
            'rate_per_kg' => 7.50,
            'system_amount' => 300.00,
            'added_amount' => 50.00,
            'given_amount' => 350.00,
        ]);

        $shipmentOrder = ShipmentOrder::create([
            'order_number' => 'SO-2026-CONSOL-1',
            'document_id' => $pi->id,
            'invoice_document_id' => $invoice->id,
            'packing_list_document_id' => $pkl->id,
            'proforma_invoice_no' => 'E26300',
            'linked_invoice_no' => 'N26300',
            'linked_packing_list_no' => 'W26300',
            'company_name' => 'Saudi Tech Arabian',
            'country' => 'Saudi Arabia',
            'customer_po_number' => 'PO-9988',
            'carrier_method' => 'Air Freight',
            'tracking_awb_no' => 'AWB-7788',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $service = app(ReportExportService::class);
        $records = $service->buildConsolidatedFreightWeightsRecords([]);

        // 4 separate document records + 1 ShipmentOrder must yield EXACTLY ONE consolidated order row
        $this->assertCount(1, $records);

        $rec = $records->first();
        $this->assertEquals('SO-2026-CONSOL-1', $rec->order_number);
        $this->assertEquals('E26300', $rec->pi_number);
        $this->assertEquals('N26300', $rec->invoice_number);
        $this->assertEquals('W26300', $rec->pkl_number);
        $this->assertStringContainsString('PO: PO-9988', $rec->other_docs_refs);
        $this->assertStringContainsString('Reserve: E26300R', $rec->other_docs_refs);

        // Precedence: PKL weight MUST be used over PI/Invoice/Reserve
        $this->assertEquals(14.50, (float) $rec->net_weight);
        $this->assertEquals(18.00, (float) $rec->gross_weight);
        $this->assertEquals('PKL', $rec->weight_source);
        $this->assertEquals(2, $rec->packages_count);
        $this->assertEquals(0.35, (float) $rec->total_cbm);

        // Shipping cost must be included
        $this->assertEquals(350.00, (float) $rec->shipping_cost);
        $this->assertEquals(7.50, (float) $rec->rate_per_kg);
        $this->assertEquals('Air Freight', $rec->carrier_method);
        $this->assertEquals('AWB-7788', $rec->tracking_awb);
        $this->assertEquals(1500.00, (float) $rec->order_total);
    }

    public function test_freight_weights_uses_reserve_net_weight_when_packing_list_is_not_available(): void
    {
        $pi = Document::create([
            'document_number' => 'E26301',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Bahrain Petro',
            'country' => 'Bahrain',
            'document_date' => '2026-09-02',
            'currency' => 'USD',
            'total_net_weight' => 0.00,
            'total_gross_weight' => 0.00,
            'final_total' => 800.00,
            'created_by' => $this->user->id,
        ]);

        $reserve = Document::create([
            'document_number' => 'E26301R',
            'document_type' => Document::TYPE_RESERVE,
            'source_document_id' => $pi->id,
            'source_document_number' => 'E26301',
            'company_name' => 'Bahrain Petro',
            'country' => 'Bahrain',
            'document_date' => '2026-09-02',
            'currency' => 'USD',
            'total_net_weight' => 22.75,
            'total_gross_weight' => 22.75,
            'final_total' => 0.00,
            'created_by' => $this->user->id,
        ]);

        $service = app(ReportExportService::class);
        $records = $service->buildConsolidatedFreightWeightsRecords([]);

        $this->assertCount(1, $records);
        $rec = $records->first();

        $this->assertEquals('E26301', $rec->pi_number);
        $this->assertEquals('-', $rec->pkl_number);
        $this->assertStringContainsString('Reserve: E26301R', $rec->other_docs_refs);

        // Precedence: Reserve net weight must be used when no PKL is available
        $this->assertEquals(22.75, (float) $rec->net_weight);
        $this->assertEquals('Reserve', $rec->weight_source);
    }

    public function test_freight_weights_excel_export_has_one_row_per_order_and_correct_totals(): void
    {
        $pi = Document::create([
            'document_number' => 'E26302',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Kuwait Marine Logistics',
            'country' => 'Kuwait',
            'document_date' => '2026-09-03',
            'currency' => 'USD',
            'total_net_weight' => 5.00,
            'total_gross_weight' => 6.00,
            'final_total' => 500.00,
            'created_by' => $this->user->id,
        ]);

        $pkl = Document::create([
            'document_number' => 'W26302',
            'document_type' => Document::TYPE_PACKING_LIST,
            'source_document_id' => $pi->id,
            'source_document_number' => 'E26302',
            'company_name' => 'Kuwait Marine Logistics',
            'country' => 'Kuwait',
            'document_date' => '2026-09-03',
            'currency' => 'USD',
            'total_net_weight' => 8.50,
            'total_gross_weight' => 10.00,
            'created_by' => $this->user->id,
        ]);

        DocumentShipmentCost::create([
            'document_id' => $pi->id,
            'method' => 'dhl',
            'rate_per_kg' => 12.00,
            'system_amount' => 120.00,
            'added_amount' => 30.00,
            'given_amount' => 150.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.freight-weights', ['format' => 'excel']));
        $response->assertOk();

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $tempFile = tempnam(sys_get_temp_dir(), 'fw_test_');
        file_put_contents($tempFile, $content);

        $reader = new Xlsx;
        $spreadsheet = $reader->load($tempFile);
        @unlink($tempFile);

        $sheet = $spreadsheet->getActiveSheet();
        $this->assertEquals('Freight & Weights Log', $sheet->getTitle());

        // Header
        $this->assertEquals('Order #', $sheet->getCell('A1')->getValue());
        $this->assertEquals('PI No.', $sheet->getCell('E1')->getValue());
        $this->assertEquals('PKL No.', $sheet->getCell('G1')->getValue());
        $this->assertEquals('Net Weight (KG)', $sheet->getCell('I1')->getValue());
        $this->assertEquals('Weight Source', $sheet->getCell('K1')->getValue());
        $this->assertEquals('Shipping Cost', $sheet->getCell('Q1')->getValue());

        // Exactly ONE order data row (Row 2)
        $this->assertEquals('E26302', $sheet->getCell('A2')->getValue());
        $this->assertEquals('E26302', $sheet->getCell('E2')->getValue());
        $this->assertEquals('W26302', $sheet->getCell('G2')->getValue());
        $this->assertEquals(8.50, (float) $sheet->getCell('I2')->getValue());
        $this->assertEquals(10.00, (float) $sheet->getCell('J2')->getValue());
        $this->assertEquals('PKL', $sheet->getCell('K2')->getValue());
        $this->assertEquals(150.00, (float) $sheet->getCell('Q2')->getValue());

        // Totals row (Row 3)
        $this->assertEquals('TOTALS', $sheet->getCell('A3')->getValue());
    }
}
