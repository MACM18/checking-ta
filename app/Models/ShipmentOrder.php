<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ShipmentOrder extends Model
{
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('shipment_companies_v2');
            Cache::forget('shipment_companies_list');
        });
        static::deleted(function () {
            Cache::forget('shipment_companies_v2');
            Cache::forget('shipment_companies_list');
        });
    }

    public const CATEGORY_AIR = 'Air Freight';

    public const CATEGORY_SEA = 'Sea Freight';

    public const CATEGORY_COURIER = 'Courier / Express';

    public const CATEGORY_ROAD = 'Road Freight';

    public const CATEGORY_STANDARD = 'Standard';

    public const CATEGORY_URGENT = 'Urgent / Priority';

    public const CATEGORIES = [
        self::CATEGORY_STANDARD,
        self::CATEGORY_AIR,
        self::CATEGORY_SEA,
        self::CATEGORY_COURIER,
        self::CATEGORY_ROAD,
        self::CATEGORY_URGENT,
    ];

    public const PAYMENT_STATUS_PENDING = 'pending';

    public const PAYMENT_STATUS_SUBMITTED = 'payment_submitted';

    public const PAYMENT_STATUS_ADVANCE = 'advance_received';

    public const PAYMENT_STATUS_FULLY_PAID = 'fully_paid';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_PENDING,
        self::PAYMENT_STATUS_SUBMITTED,
        self::PAYMENT_STATUS_ADVANCE,
        self::PAYMENT_STATUS_FULLY_PAID,
    ];

    protected $fillable = [
        'order_number',
        'document_id',
        'invoice_document_id',
        'packing_list_document_id',
        'document_reference',
        'proforma_invoice_no',
        'company_name',
        'country',
        'customer_po_number',
        'customer_po_date',
        'customer_po_notes',
        'payment_status',
        'payment_reference',
        'payment_amount',
        'payment_submitted_at',
        'payment_submission_ref',
        'payment_submission_notes',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'currency',
        'linked_invoice_no',
        'linked_packing_list_no',
        'draft_documents_sent',
        'draft_documents_notes',
        'carrier_method',
        'tracking_awb_no',
        'dispatch_date',
        'delivery_date',
        'shipment_category',
        'current_stage',
        'status',
        'custom_status_message',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'customer_po_date' => 'date',
            'dispatch_date' => 'date',
            'delivery_date' => 'date',
            'draft_documents_sent' => 'boolean',
            'payment_amount' => 'decimal:2',
            'payment_submitted_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'current_stage' => 'integer',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function invoiceDocument()
    {
        return $this->belongsTo(Document::class, 'invoice_document_id');
    }

    public function packingListDocument()
    {
        return $this->belongsTo(Document::class, 'packing_list_document_id');
    }

    public function getResolvedProformaDocumentAttribute(): ?Document
    {
        if ($this->relationLoaded('document') && $this->document) {
            return $this->document;
        }

        if ($this->document_id) {
            return $this->document;
        }

        if (! empty($this->proforma_invoice_no)) {
            return Document::where('document_number', $this->proforma_invoice_no)->first();
        }

        return null;
    }

    public function getResolvedInvoiceDocumentAttribute(): ?Document
    {
        if ($this->relationLoaded('invoiceDocument') && $this->invoiceDocument) {
            return $this->invoiceDocument;
        }

        if ($this->invoice_document_id) {
            return $this->invoiceDocument;
        }

        if (! empty($this->linked_invoice_no)) {
            return Document::where('document_number', $this->linked_invoice_no)->first();
        }

        return null;
    }

    public function getResolvedPackingListDocumentAttribute(): ?Document
    {
        if ($this->relationLoaded('packingListDocument') && $this->packingListDocument) {
            return $this->packingListDocument;
        }

        if ($this->packing_list_document_id) {
            return $this->packingListDocument;
        }

        if (! empty($this->linked_packing_list_no)) {
            return Document::where('document_number', $this->linked_packing_list_no)->first();
        }

        return null;
    }

    public function milestones()
    {
        return $this->hasMany(OrderMilestone::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentConfirmedBy()
    {
        return $this->belongsTo(User::class, 'payment_confirmed_by');
    }

    public function getCompletedMilestonesCountAttribute(): int
    {
        return $this->milestones->where('is_completed', true)->count();
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->milestones->count();
        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->completed_milestones_count / $total) * 100);
    }
}
