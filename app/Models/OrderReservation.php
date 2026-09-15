<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderReservation extends Model
{
    public const STATUS_PENDING_CHECK = 'pending_check';

    public const STATUS_ALL_AVAILABLE = 'all_available';

    public const STATUS_HAS_SHORTAGE = 'has_shortage';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING_CHECK => 'Pending Warehouse Check',
        self::STATUS_ALL_AVAILABLE => 'All Items Available',
        self::STATUS_HAS_SHORTAGE => 'Shortage / Missing Parts',
        self::STATUS_FULFILLED => 'Fulfilled / Dispatched',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'uuid',
        'document_id',
        'reservation_number',
        'reserve_document_number',
        'company_name',
        'country',
        'reservation_date',
        'status',
        'total_requested_qty',
        'total_available_qty',
        'total_short_qty',
        'total_items_count',
        'short_items_count',
        'warehouse_location',
        'warehouse_confirmed_at',
        'warehouse_confirmed_by',
        'warehouse_notes',
        'notes',
        'is_legacy_record',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'warehouse_confirmed_at' => 'datetime',
            'total_requested_qty' => 'decimal:3',
            'total_available_qty' => 'decimal:3',
            'total_short_qty' => 'decimal:3',
            'total_items_count' => 'integer',
            'short_items_count' => 'integer',
            'is_legacy_record' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OrderReservation $reservation) {
            if (empty($reservation->uuid)) {
                $reservation->uuid = (string) Str::uuid();
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

        if (is_numeric($value)) {
            return $this->where('id', $value)->firstOrFail();
        }

        if (! is_string($value) || ! Str::isUuid($value)) {
            abort(404);
        }

        return $this->where('uuid', $value)->firstOrFail();
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function items()
    {
        return $this->hasMany(OrderReservationItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function shortItems()
    {
        return $this->hasMany(OrderReservationItem::class)->where('short_qty', '>', 0)->orderBy('sort_order')->orderBy('id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'warehouse_confirmed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recalculateTotals(): self
    {
        $items = $this->items()->get();

        $totalRequested = (float) $items->sum('requested_qty');
        $totalAvailable = (float) $items->sum('available_qty');
        $totalShort = (float) $items->sum('short_qty');
        $totalCount = $items->count();
        $shortCount = $items->filter(fn ($item) => (float) $item->short_qty > 0 || $item->status === OrderReservationItem::STATUS_MISSING || $item->status === OrderReservationItem::STATUS_SHORT)->count();

        $this->total_requested_qty = $totalRequested;
        $this->total_available_qty = $totalAvailable;
        $this->total_short_qty = $totalShort;
        $this->total_items_count = $totalCount;
        $this->short_items_count = $shortCount;

        if ($this->status !== self::STATUS_FULFILLED && $this->status !== self::STATUS_CANCELLED) {
            if ($shortCount > 0 || $totalShort > 0) {
                $this->status = self::STATUS_HAS_SHORTAGE;
            } elseif ($this->warehouse_confirmed_at !== null || ($totalCount > 0 && $totalAvailable >= $totalRequested)) {
                $this->status = self::STATUS_ALL_AVAILABLE;
            } else {
                $this->status = self::STATUS_PENDING_CHECK;
            }
        }

        $this->save();

        return $this;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }

    public function getStatusBadgeClassesAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ALL_AVAILABLE => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::STATUS_HAS_SHORTAGE => 'bg-amber-100 text-amber-800 border-amber-300',
            self::STATUS_FULFILLED => 'bg-blue-100 text-blue-800 border-blue-300',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-700 border-gray-300',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}
