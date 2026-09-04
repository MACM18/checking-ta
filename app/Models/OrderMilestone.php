<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMilestone extends Model
{
    public const STAGE_PI_SENT = 'pi_sent';

    public const STAGE_PO_RECEIVED = 'po_received';

    public const STAGE_PAYMENT_SUBMITTED = 'payment_submitted';

    public const STAGE_PAYMENT_CONFIRMED = 'payment_confirmed';

    public const STAGE_DRAFT_DOCS = 'draft_docs_sent';

    public const STAGE_FINAL_INVOICE_PL = 'invoice_packing_list';

    public const STAGE_DISPATCHED = 'dispatched';

    public const STAGE_CUSTOMS = 'customs_clearance';

    public const STAGE_DELIVERED = 'delivered';

    public static function defaultStages(): array
    {
        return [
            self::STAGE_PI_SENT => [
                'name' => '1. Proforma Invoice (PI) Sent',
                'description' => 'Document (E/EL) prepared and sent to customer',
            ],
            self::STAGE_PO_RECEIVED => [
                'name' => '2. Customer Approval & PO Received',
                'description' => 'Customer approved PI and issued official Purchase Order',
            ],
            self::STAGE_PAYMENT_SUBMITTED => [
                'name' => '3. Payment Submitted / Advice Provided',
                'description' => 'Customer submitted payment proof, bank advice, or remittance slip',
            ],
            self::STAGE_PAYMENT_CONFIRMED => [
                'name' => '4. Payment Confirmed & Verified',
                'description' => 'Finance/Accounts verified and confirmed funds credited to bank',
            ],
            self::STAGE_DRAFT_DOCS => [
                'name' => '5. Draft Documents Prepared & Sent',
                'description' => 'Draft Invoice & Packing List sent to customer for review',
            ],
            self::STAGE_FINAL_INVOICE_PL => [
                'name' => '6. Final Commercial Invoice & Packing List Issued',
                'description' => 'Official N invoice and W packing list generated',
            ],
            self::STAGE_DISPATCHED => [
                'name' => '7. Shipment Dispatched / AWB Issued',
                'description' => 'Cargo handed to carrier (DHL/Air/Sea) with tracking number',
            ],
            self::STAGE_CUSTOMS => [
                'name' => '8. Customs Clearance / Port Handling',
                'description' => 'Export/Import clearance completed & customs duty settled',
            ],
            self::STAGE_DELIVERED => [
                'name' => '9. Cargo Delivered & Signed Off',
                'description' => 'Customer confirmed delivery with signed Delivery Note (D)',
            ],
        ];
    }

    protected $fillable = [
        'shipment_order_id',
        'stage_code',
        'stage_name',
        'is_completed',
        'completed_at',
        'completed_by',
        'reference_no',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function shipmentOrder()
    {
        return $this->belongsTo(ShipmentOrder::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
