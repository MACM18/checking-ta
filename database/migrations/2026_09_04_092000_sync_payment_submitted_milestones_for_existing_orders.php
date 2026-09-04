<?php

use App\Models\OrderMilestone;
use App\Models\ShipmentOrder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaultStages = OrderMilestone::defaultStages();

        $orders = ShipmentOrder::with('milestones')->get();

        foreach ($orders as $order) {
            $hasSubmitted = $order->milestones->contains('stage_code', OrderMilestone::STAGE_PAYMENT_SUBMITTED);
            if ($hasSubmitted) {
                continue;
            }

            // Check if payment was already confirmed
            $confirmedMilestone = $order->milestones->firstWhere('stage_code', OrderMilestone::STAGE_PAYMENT_CONFIRMED);
            $isSubmittedAlready = $confirmedMilestone?->is_completed ?? false;

            // Delete existing milestones and rebuild to ensure exact order and naming
            $existingData = $order->milestones->keyBy('stage_code');
            $order->milestones()->delete();

            $sort = 1;
            foreach ($defaultStages as $code => $meta) {
                $isCompleted = false;
                $completedAt = null;
                $completedBy = null;
                $ref = null;
                $notes = $meta['description'];

                if ($code === OrderMilestone::STAGE_PAYMENT_SUBMITTED) {
                    if ($isSubmittedAlready) {
                        $isCompleted = true;
                        $completedAt = $confirmedMilestone->completed_at;
                        $completedBy = $confirmedMilestone->completed_by;
                        $ref = $order->payment_reference ?? 'Transferred';
                    } elseif ($order->payment_status === ShipmentOrder::PAYMENT_STATUS_SUBMITTED) {
                        $isCompleted = true;
                        $completedAt = $order->payment_submitted_at ?? now();
                        $ref = $order->payment_submission_ref;
                    }
                } elseif (isset($existingData[$code])) {
                    $old = $existingData[$code];
                    $isCompleted = $old->is_completed;
                    $completedAt = $old->completed_at;
                    $completedBy = $old->completed_by;
                    $ref = $old->reference_no;
                    $notes = $old->notes ?: $meta['description'];
                }

                $order->milestones()->create([
                    'stage_code' => $code,
                    'stage_name' => $meta['name'],
                    'notes' => $notes,
                    'is_completed' => $isCompleted,
                    'completed_at' => $completedAt,
                    'completed_by' => $completedBy,
                    'reference_no' => $ref,
                    'sort_order' => $sort++,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Safe no-op
    }
};
