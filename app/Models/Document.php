<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

    public const TYPE_SUPPLIER_ORDER = 'supplier_order';

    public const TYPE_FACTORY_INVOICE = 'factory_invoice';

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
                self::TYPE_SUPPLIER_ORDER => 'Supplier Order (B)',
                self::TYPE_FACTORY_INVOICE => 'Factory Invoice',
                self::TYPE_OTHER => 'Other Document',
            ];
        });
    }

    protected $fillable = [
        'uuid',
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
        'price_list',
        'price_label',
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

    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getRouteKey(): mixed
    {
        return $this->getAttribute($this->getRouteKeyName()) ?? (string) $this->getKey();
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (! is_string($value) || ! Str::isUuid($value)) {
            abort(404);
        }

        return $this->where('uuid', $value)->firstOrFail();
    }

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
        return in_array($this->document_type, [self::TYPE_PACKING_LIST, self::TYPE_RESERVE, self::TYPE_DELIVERY_NOTE]);
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

    public function isDeliveryNote(): bool
    {
        return $this->document_type === self::TYPE_DELIVERY_NOTE || str_ends_with(strtoupper($this->document_number), 'D');
    }

    public function isQuantityOnly(): bool
    {
        return in_array($this->document_type, [self::TYPE_SUPPLIER_ORDER, self::TYPE_FACTORY_INVOICE])
            || str_starts_with(strtoupper($this->document_number), 'B');
    }

    public function isSupplierOrder(): bool
    {
        return $this->document_type === self::TYPE_SUPPLIER_ORDER || str_starts_with(strtoupper($this->document_number), 'B');
    }

    public function isFactoryInvoice(): bool
    {
        return $this->document_type === self::TYPE_FACTORY_INVOICE;
    }

    public function factoryInvoices()
    {
        return $this->hasMany(Document::class, 'source_document_id')
            ->where(function ($q) {
                $q->where('document_type', self::TYPE_FACTORY_INVOICE)
                    ->orWhere('document_type', 'like', '%factory%');
            });
    }

    public function supplierOrder()
    {
        return $this->belongsTo(Document::class, 'source_document_id');
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

    /**
     * Get the total item quantity on the document (excluding discounts, taxes, and adjustment items).
     */
    public function getTotalQuantityAttribute(): float
    {
        return (float) $this->items->reject(function ($it) {
            $code = strtoupper(trim($it->item_code ?? ''));

            return in_array($code, ['TAX', 'VAT', 'TAX / VAT', 'TAX/VAT', 'DISCOUNT', 'DISC', 'ADDITION', 'ADD', 'SURCHARGE']) || (float) $it->total_amount < 0;
        })->sum(function ($it) {
            return (float) ($it->unit_amount ?? 0);
        });
    }

    /**
     * Get formatted total quantity representation (integers without decimals, decimals formatted cleanly).
     */
    public function getFormattedTotalQuantityAttribute(): string
    {
        $qty = $this->total_quantity;

        return (floor($qty) == $qty) ? number_format($qty, 0) : number_format($qty, 2);
    }

    /**
     * Get effective price list name from stored column, catalogue matching, or notes.
     */
    public function getEffectivePriceListAttribute(): ?string
    {
        if (! empty($this->price_list)) {
            return $this->price_list;
        }

        // Check if items match catalogue in item_prices
        if ($this->relationLoaded('items') ? $this->items->isNotEmpty() : $this->items()->exists()) {
            $itemCodes = $this->items->pluck('item_code')->filter()->take(20)->all();
            if (! empty($itemCodes)) {
                $matchedList = DB::table('item_prices')
                    ->whereIn('item_code', $itemCodes)
                    ->whereNotNull('price_list')
                    ->where('price_list', '!=', '')
                    ->groupBy('price_list')
                    ->orderByRaw('COUNT(*) DESC')
                    ->value('price_list');

                if ($matchedList) {
                    return $matchedList;
                }
            }
        }

        // Check notes for any 'Price List: XYZ' mention
        if (! empty($this->notes) && preg_match('/price\s*list[:\s]+([a-zA-Z0-9\s]+)/i', $this->notes, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Get effective price label / tier (e.g. 'USD 30%', 'USD 40%') from stored column or item prices.
     */
    public function getEffectivePriceLabelAttribute(): ?string
    {
        if ($this->price_label !== null && $this->price_label !== '') {
            return $this->price_label;
        }

        // Check if items match catalogue tiers in item_prices
        $hasItems = $this->relationLoaded('items') ? $this->items->isNotEmpty() : $this->items()->exists();
        if ($hasItems) {
            $currency = $this->currency ?: 'USD';
            $itemsWithPrice = $this->items->filter(fn ($it) => (float) $it->unit_price > 0 && ! empty($it->item_code));

            if ($itemsWithPrice->isNotEmpty()) {
                $itemCodes = $itemsWithPrice->pluck('item_code')->take(15)->all();

                $matched = DB::table('item_prices')
                    ->whereIn('item_code', $itemCodes)
                    ->whereNotNull('price_label')
                    ->where('price_label', '!=', '')
                    ->where('price_label', 'like', "{$currency}%")
                    ->get(['item_code', 'price_label', 'price']);

                if ($matched->isNotEmpty()) {
                    $labelScores = [];
                    foreach ($itemsWithPrice as $it) {
                        $itemMatches = $matched->where('item_code', $it->item_code);
                        foreach ($itemMatches as $m) {
                            if (abs((float) $m->price - (float) $it->unit_price) < 0.01) {
                                $labelScores[$m->price_label] = ($labelScores[$m->price_label] ?? 0) + 1;
                            }
                        }
                    }

                    if (! empty($labelScores)) {
                        arsort($labelScores);

                        return array_key_first($labelScores);
                    }
                }
            }
        }

        return ($this->currency === 'AED') ? 'AED 30%' : 'USD 30%';
    }
}
