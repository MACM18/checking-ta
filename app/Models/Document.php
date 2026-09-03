<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    public const TYPE_PROFORMA = 'proforma_invoice';

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
    }

    protected $fillable = [
        'document_number',
        'document_type',
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

    public function shipmentCosts()
    {
        return $this->hasMany(DocumentShipmentCost::class);
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
