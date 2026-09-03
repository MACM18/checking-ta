<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentOrder extends Model
{
    protected $fillable = [
        'order_number',
        'document_id',
        'company_name',
        'country',
        'customer_po_number',
        'customer_po_date',
        'customer_po_notes',
        'payment_status',
        'payment_reference',
        'payment_amount',
        'currency',
        'linked_invoice_no',
        'linked_packing_list_no',
        'draft_documents_sent',
        'draft_documents_notes',
        'carrier_method',
        'tracking_awb_no',
        'dispatch_date',
        'delivery_date',
        'current_stage',
        'status',
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
            'current_stage' => 'integer',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function milestones()
    {
        return $this->hasMany(OrderMilestone::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
