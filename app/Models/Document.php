<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Document extends Model
{
    public const TYPE_PROFORMA = 'proforma_invoice';

    public const TYPE_PROFORMA_INVOICE = 'proforma_invoice';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_PACKING_LIST = 'packing_list';

    public const TYPE_RESERVE = 'reserve';

    public const TYPE_CREDIT_NOTE = 'credit_note';

    public const TYPE_DELIVERY_NOTE = 'delivery_note';

    public const TYPE_CLEARING_INVOICE = 'clearing_invoice';

    public const TYPE_CASH_RECEIPT = 'cash_receipt';

    public const TYPE_OTHER = 'other';

    public static function documentTypes(): array
    {
        return Cache::remember('active_document_types_map', 3600, function () {
            try {
                if (Schema::hasTable('document_types')) {
                    $dbTypes = DocumentType::active()->ordered()->pluck('name', 'code')->toArray();
                    if (! empty($dbTypes)) {
                        return $dbTypes;
                    }
                }
            } catch (\Throwable $e) {
                // Graceful fallback during tests or bootstrapping
            }

            return [
                self::TYPE_PROFORMA => 'Proforma Invoice (E / EL)',
                self::TYPE_INVOICE => 'Invoice (N)',
                self::TYPE_PACKING_LIST => 'Packing List (W)',
                self::TYPE_RESERVE => 'Reserve (ends with R)',
                self::TYPE_CREDIT_NOTE => 'Credit Note (ends with CR)',
                self::TYPE_DELIVERY_NOTE => 'Delivery Note (ends with D)',
                self::TYPE_CLEARING_INVOICE => 'Clearing Invoice (ends with C)',
                self::TYPE_CASH_RECEIPT => 'Cash Receipt (Custom / CR)',
                self::TYPE_OTHER => 'Other Document',
            ];
        });
    }

    protected $fillable = [
        'document_number',
        'document_type',
        'source_document_id',
        'source_document_number',
        'company_name',
        'country',
        'address',
        'contact_details',
        'document_date',
        'currency',
        'total_net_weight',
        'total_gross_weight',
        'subtotal',
        'final_total',
        'current_version',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'total_net_weight' => 'decimal:3',
            'total_gross_weight' => 'decimal:3',
            'subtotal' => 'decimal:2',
            'final_total' => 'decimal:2',
            'current_version' => 'integer',
        ];
    }

    public function items()
    {
        return $this->hasMany(DocumentItem::class)->orderBy('sort_order');
    }

    public function packages()
    {
        return $this->hasMany(DocumentPackage::class)->orderBy('sort_order');
    }

    public function shipmentCosts()
    {
        return $this->hasMany(DocumentShipmentCost::class);
    }

    public function shipmentOrders()
    {
        return $this->hasMany(ShipmentOrder::class);
    }

    public function invoiceShipmentOrders()
    {
        return $this->hasMany(ShipmentOrder::class, 'invoice_document_id');
    }

    public function packingListShipmentOrders()
    {
        return $this->hasMany(ShipmentOrder::class, 'packing_list_document_id');
    }

    /**
     * Get all shipment orders connected to this document (as PI, Commercial Invoice, or Packing List).
     */
    public function getAllConnectedShipmentOrdersAttribute()
    {
        $docId = $this->id;
        $docNum = $this->document_number;

        return ShipmentOrder::where('document_id', $docId)
            ->orWhere('invoice_document_id', $docId)
            ->orWhere('packing_list_document_id', $docId)
            ->when(! empty($docNum), function ($q) use ($docNum) {
                $q->orWhere('proforma_invoice_no', $docNum)
                    ->orWhere('linked_invoice_no', $docNum)
                    ->orWhere('linked_packing_list_no', $docNum);
            })
            ->with(['milestones', 'creator'])
            ->orderByDesc('id')
            ->get();
    }

    public function sourceDocument()
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    public function derivedDocuments()
    {
        return $this->hasMany(Document::class, 'source_document_id');
    }

    public function isWeightOnly(): bool
    {
        return in_array($this->document_type, [self::TYPE_PACKING_LIST, self::TYPE_RESERVE]);
    }

    public function isPackingList(): bool
    {
        return $this->document_type === self::TYPE_PACKING_LIST;
    }

    public function isProformaInvoice(): bool
    {
        return $this->document_type === self::TYPE_PROFORMA;
    }

    public function isCommercialInvoice(): bool
    {
        return in_array($this->document_type, [self::TYPE_INVOICE, 'commercial_invoice']);
    }

    public function isReserve(): bool
    {
        return $this->document_type === self::TYPE_RESERVE || str_ends_with(strtoupper($this->document_number), 'R');
    }

    public function orderReservation()
    {
        return $this->hasOne(OrderReservation::class);
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    public function lock()
    {
        return $this->hasOne(DocumentLock::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getFormattedTypeAttribute(): string
    {
        return self::documentTypes()[$this->document_type] ?? ucwords(str_replace('_', ' ', $this->document_type));
    }

    public function getActiveLock()
    {
        $lock = $this->lock;
        if ($lock && $lock->expires_at && $lock->expires_at->isFuture()) {
            return $lock;
        }

        return null;
    }

    public function isLockedByOther(?User $user): bool
    {
        if (! $user) {
            return true;
        }
        $activeLock = $this->getActiveLock();

        return $activeLock && $activeLock->user_id !== $user->id;
    }
}
