<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\OrderMilestone;
use App\Models\ShipmentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentOrderPaymentStagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_milestones_contain_payment_submitted_and_payment_confirmed(): void
    {
        $stages = OrderMilestone::defaultStages();

        $this->assertArrayHasKey(OrderMilestone::STAGE_PAYMENT_SUBMITTED, $stages);
        $this->assertArrayHasKey(OrderMilestone::STAGE_PAYMENT_CONFIRMED, $stages);

        $keys = array_keys($stages);
        $this->assertEquals(OrderMilestone::STAGE_PI_SENT, $keys[0]);
        $this->assertEquals(OrderMilestone::STAGE_PO_RECEIVED, $keys[1]);
        $this->assertEquals(OrderMilestone::STAGE_PAYMENT_SUBMITTED, $keys[2]);
        $this->assertEquals(OrderMilestone::STAGE_PAYMENT_CONFIRMED, $keys[3]);
        $this->assertCount(9, $stages);
    }

    public function test_creating_order_initializes_9_milestones_and_syncs_submitted_payment(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $doc = Document::create([
            'document_number' => 'E26299',
            'original_filename' => 'pi-doc.xlsx',
            'file_path' => 'documents/pi-doc.xlsx',
            'file_hash' => 'hash_pi_stages',
            'company_name' => 'Gulf Trading LLC',
            'country' => 'United Arab Emirates',
            'document_date' => '2026-09-04',
            'po_number' => 'PO-9911',
            'document_type' => 'proforma_invoice',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('shipment-orders.store'), [
            'order_number' => 'ORD-2026-PAY-1',
            'document_id' => $doc->id,
            'company_name' => 'Gulf Trading LLC',
            'country' => 'United Arab Emirates',
            'payment_status' => ShipmentOrder::PAYMENT_STATUS_SUBMITTED,
            'payment_submission_ref' => 'REMIT-99124',
            'payment_amount' => 12500.00,
            'currency' => 'USD',
        ]);

        $response->assertRedirect();

        $order = ShipmentOrder::where('order_number', 'ORD-2026-PAY-1')->first();
        $this->assertNotNull($order);
        $this->assertEquals(9, $order->milestones()->count());

        $submittedMilestone = $order->milestones()->where('stage_code', OrderMilestone::STAGE_PAYMENT_SUBMITTED)->first();
        $this->assertTrue($submittedMilestone->is_completed);
        $this->assertEquals('REMIT-99124', $submittedMilestone->reference_no);

        $confirmedMilestone = $order->milestones()->where('stage_code', OrderMilestone::STAGE_PAYMENT_CONFIRMED)->first();
        $this->assertFalse($confirmedMilestone->is_completed);
    }

    public function test_toggling_payment_submitted_milestone_updates_order_status_and_ref(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $order = ShipmentOrder::create([
            'order_number' => 'ORD-TEST-TOGGLE-1',
            'company_name' => 'Arabian Supplies',
            'country' => 'UAE',
            'payment_status' => ShipmentOrder::PAYMENT_STATUS_PENDING,
            'currency' => 'USD',
            'created_by' => $user->id,
        ]);

        foreach (OrderMilestone::defaultStages() as $code => $meta) {
            $order->milestones()->create([
                'stage_code' => $code,
                'stage_name' => $meta['name'],
                'is_completed' => false,
                'sort_order' => 1,
            ]);
        }

        $submittedMilestone = $order->milestones()->where('stage_code', OrderMilestone::STAGE_PAYMENT_SUBMITTED)->first();

        // Toggle Payment Submitted with slip reference
        $response = $this->actingAs($user)->postJson(route('shipment-orders.milestones.toggle', [
            'shipmentOrder' => $order,
            'milestone' => $submittedMilestone,
        ]), [
            'reference_no' => 'SLIP-8877',
        ]);

        $response->assertOk();
        $response->assertJson(['is_completed' => true]);

        $order->refresh();
        $this->assertEquals(ShipmentOrder::PAYMENT_STATUS_SUBMITTED, $order->payment_status);
        $this->assertEquals('SLIP-8877', $order->payment_submission_ref);
        $this->assertNotNull($order->payment_submitted_at);

        // Now toggle Payment Confirmed
        $confirmedMilestone = $order->milestones()->where('stage_code', OrderMilestone::STAGE_PAYMENT_CONFIRMED)->first();

        $response2 = $this->actingAs($user)->postJson(route('shipment-orders.milestones.toggle', [
            'shipmentOrder' => $order,
            'milestone' => $confirmedMilestone,
        ]), [
            'reference_no' => 'BANK-CONFIRM-5544',
        ]);

        $response2->assertOk();
        $response2->assertJson(['is_completed' => true]);

        $order->refresh();
        $this->assertEquals(ShipmentOrder::PAYMENT_STATUS_FULLY_PAID, $order->payment_status);
        $this->assertEquals('BANK-CONFIRM-5544', $order->payment_reference);
        $this->assertNotNull($order->payment_confirmed_at);
        $this->assertEquals($user->id, $order->payment_confirmed_by);
    }
}
