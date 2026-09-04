<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReservationItem extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_SHORT = 'short';

    public const STATUS_MISSING = 'missing';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending Check',
        self::STATUS_AVAILABLE => 'Available',
        self::STATUS_SHORT => 'Shortage',
        self::STATUS_MISSING => 'Missing / Nil',
    ];

    protected $fillable = [
        'order_reservation_id',
        'document_item_id',
        'item_code',
        'description',
        'requested_qty',
        'available_qty',
        'short_qty',
        'supplier_invoice_no',
        'bin_location',
        'status',
        'shortage_reason',
        'remarks',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requested_qty' => 'decimal:3',
            'available_qty' => 'decimal:3',
            'short_qty' => 'decimal:3',
            'sort_order' => 'integer',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(OrderReservation::class, 'order_reservation_id');
    }

    public function documentItem()
    {
        return $this->belongsTo(DocumentItem::class, 'document_item_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucwords($this->status);
    }

    public function getStatusBadgeClassesAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::STATUS_SHORT => 'bg-amber-50 text-amber-700 border-amber-200',
            self::STATUS_MISSING => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}
