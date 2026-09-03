<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Services\DocumentTypeDetector;
use PHPUnit\Framework\TestCase;

class DocumentTypeDetectorTest extends TestCase
{
    public function test_it_detects_proforma_invoice_starting_with_e_or_el(): void
    {
        $result1 = DocumentTypeDetector::detect('E26211');
        $this->assertEquals(Document::TYPE_PROFORMA, $result1['type']);

        $result2 = DocumentTypeDetector::detect('EL9901');
        $this->assertEquals(Document::TYPE_PROFORMA, $result2['type']);

        $result3 = DocumentTypeDetector::detect('e26211');
        $this->assertEquals(Document::TYPE_PROFORMA, $result3['type']);
    }

    public function test_it_detects_invoice_starting_with_n(): void
    {
        $result = DocumentTypeDetector::detect('N10045');
        $this->assertEquals(Document::TYPE_INVOICE, $result['type']);
    }

    public function test_it_detects_packing_list_starting_with_w(): void
    {
        $result = DocumentTypeDetector::detect('W30012');
        $this->assertEquals(Document::TYPE_PACKING_LIST, $result['type']);
    }

    public function test_it_detects_reserve_ending_with_r(): void
    {
        $result1 = DocumentTypeDetector::detect('E26211R');
        $this->assertEquals(Document::TYPE_RESERVE, $result1['type']);

        $result2 = DocumentTypeDetector::detect('N10045R');
        $this->assertEquals(Document::TYPE_RESERVE, $result2['type']);
    }

    public function test_it_detects_credit_note_ending_with_cr(): void
    {
        $result1 = DocumentTypeDetector::detect('CR500');
        $this->assertEquals(Document::TYPE_CREDIT_NOTE, $result1['type']);

        $result2 = DocumentTypeDetector::detect('INV-26211CR');
        $this->assertEquals(Document::TYPE_CREDIT_NOTE, $result2['type']);
    }

    public function test_it_detects_delivery_note_ending_with_d_or_starting_with_dn(): void
    {
        $result1 = DocumentTypeDetector::detect('E26211D');
        $this->assertEquals(Document::TYPE_DELIVERY_NOTE, $result1['type']);

        $result2 = DocumentTypeDetector::detect('DN-8812');
        $this->assertEquals(Document::TYPE_DELIVERY_NOTE, $result2['type']);
    }

    public function test_it_detects_clearing_invoice_ending_with_c(): void
    {
        $result = DocumentTypeDetector::detect('E26211C');
        $this->assertEquals(Document::TYPE_CLEARING_INVOICE, $result['type']);
    }

    public function test_it_detects_cash_receipt_starting_with_rec_or_rcp(): void
    {
        $result1 = DocumentTypeDetector::detect('REC-1004');
        $this->assertEquals(Document::TYPE_CASH_RECEIPT, $result1['type']);

        $result2 = DocumentTypeDetector::detect('RCP-550');
        $this->assertEquals(Document::TYPE_CASH_RECEIPT, $result2['type']);
    }
}
