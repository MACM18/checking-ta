<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\OrderMilestone;
use App\Models\ShipmentOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShipmentOrderController extends Controller
{
    /**
     * Display listing of shipment orders with stage progress.
     */
    public function index(Request $request): View
    {
        $query = ShipmentOrder::with(['document', 'milestones', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('customer_po_number', 'like', "%{$search}%")
                    ->orWhere('document_reference', 'like', "%{$search}%")
                    ->orWhere('proforma_invoice_no', 'like', "%{$search}%")
                    ->orWhere('linked_invoice_no', 'like', "%{$search}%")
                    ->orWhere('tracking_awb_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company_name')) {
            $query->where('company_name', $request->company_name);
        }

        if ($request->filled('category')) {
            $query->where('shipment_category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(12)->withQueryString();

        $companies = ShipmentOrder::distinct()->whereNotNull('company_name')->pluck('company_name')->sort()->values();
        $categories = ShipmentOrder::CATEGORIES;

        $statsRaw = ShipmentOrder::selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN customer_po_number IS NULL AND status = 'active' THEN 1 END) as awaiting_po,
            COUNT(CASE WHEN dispatch_date IS NOT NULL AND status = 'active' THEN 1 END) as dispatched
        ")->first();

        $stats = [
            'total' => (int) ($statsRaw->total ?? 0),
            'active' => (int) ($statsRaw->active ?? 0),
            'completed' => (int) ($statsRaw->completed ?? 0),
            'awaiting_po' => (int) ($statsRaw->awaiting_po ?? 0),
            'dispatched' => (int) ($statsRaw->dispatched ?? 0),
        ];

        return view('shipment_orders.index', compact('orders', 'companies', 'categories', 'stats'));
    }

    /**
     * Show form for creating a new shipment order tracker.
     */
    public function create(Request $request): View
    {
        $sourceDoc = null;
        if ($request->filled('document_id')) {
            $sourceDoc = Document::find($request->document_id);
        }

        $autoOrderNumber = 'ORD-'.date('Y').'-'.str_pad(ShipmentOrder::count() + 1, 4, '0', STR_PAD_LEFT);
        $categories = ShipmentOrder::CATEGORIES;

        $systemPIs = Document::whereIn('document_type', ['proforma_invoice'])
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'document_number', 'company_name', 'country']);

        $systemInvoices = Document::whereIn('document_type', ['invoice', 'commercial_invoice'])
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'document_number', 'company_name', 'country']);

        $systemPackingLists = Document::whereIn('document_type', ['packing_list', 'packing'])
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'document_number', 'company_name', 'country']);

        return view('shipment_orders.create', compact('sourceDoc', 'autoOrderNumber', 'categories', 'systemPIs', 'systemInvoices', 'systemPackingLists'));
    }

    /**
     * Store a newly created shipment order with initialized milestones.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:50|unique:shipment_orders',
            'document_id' => 'nullable|exists:documents,id',
            'invoice_document_id' => 'nullable|exists:documents,id',
            'packing_list_document_id' => 'nullable|exists:documents,id',
            'document_reference' => 'nullable|string|max:100',
            'proforma_invoice_no' => 'nullable|string|max:100',
            'company_name' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'shipment_category' => 'nullable|string|in:'.implode(',', ShipmentOrder::CATEGORIES),
            'customer_po_number' => 'nullable|string|max:100',
            'customer_po_date' => 'nullable|date',
            'customer_po_notes' => 'nullable|string',
            'payment_status' => 'required|in:'.implode(',', ShipmentOrder::PAYMENT_STATUSES),
            'payment_reference' => 'nullable|string|max:100',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_submission_ref' => 'nullable|string|max:100',
            'payment_submission_notes' => 'nullable|string',
            'payment_submitted_at' => 'nullable|date',
            'currency' => 'required|in:USD,AED',
            'linked_invoice_no' => 'nullable|string|max:60',
            'linked_packing_list_no' => 'nullable|string|max:60',
            'carrier_method' => 'nullable|string|max:50',
            'tracking_awb_no' => 'nullable|string|max:100',
            'dispatch_date' => 'nullable|date',
            'custom_status_message' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['shipment_category'] = $validated['shipment_category'] ?? ShipmentOrder::CATEGORY_STANDARD;

        // Auto-resolve PI
        if (! empty($validated['document_id'])) {
            $doc = Document::find($validated['document_id']);
            if ($doc && empty($validated['proforma_invoice_no'])) {
                $validated['proforma_invoice_no'] = $doc->document_number;
            }
        } elseif (! empty($validated['proforma_invoice_no'])) {
            $doc = Document::where('document_number', trim($validated['proforma_invoice_no']))->first();
            if ($doc) {
                $validated['document_id'] = $doc->id;
            }
        }

        // Auto-resolve Commercial Invoice
        if (! empty($validated['invoice_document_id'])) {
            $invDoc = Document::find($validated['invoice_document_id']);
            if ($invDoc && empty($validated['linked_invoice_no'])) {
                $validated['linked_invoice_no'] = $invDoc->document_number;
            }
        } elseif (! empty($validated['linked_invoice_no'])) {
            $invDoc = Document::where('document_number', trim($validated['linked_invoice_no']))->first();
            if ($invDoc) {
                $validated['invoice_document_id'] = $invDoc->id;
            }
        }

        // Auto-resolve Packing List
        if (! empty($validated['packing_list_document_id'])) {
            $plDoc = Document::find($validated['packing_list_document_id']);
            if ($plDoc && empty($validated['linked_packing_list_no'])) {
                $validated['linked_packing_list_no'] = $plDoc->document_number;
            }
        } elseif (! empty($validated['linked_packing_list_no'])) {
            $plDoc = Document::where('document_number', trim($validated['linked_packing_list_no']))->first();
            if ($plDoc) {
                $validated['packing_list_document_id'] = $plDoc->id;
            }
        }

        $order = DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $validated['created_by'] = $user->id;
            $validated['current_stage'] = 1;

            if ($validated['payment_status'] === ShipmentOrder::PAYMENT_STATUS_SUBMITTED && empty($validated['payment_submitted_at'])) {
                $validated['payment_submitted_at'] = Carbon::now();
            }
            if (in_array($validated['payment_status'], [ShipmentOrder::PAYMENT_STATUS_ADVANCE, ShipmentOrder::PAYMENT_STATUS_FULLY_PAID])) {
                if (empty($validated['payment_submitted_at'])) {
                    $validated['payment_submitted_at'] = Carbon::now();
                }
                $validated['payment_confirmed_at'] = Carbon::now();
                $validated['payment_confirmed_by'] = $user->id;
            }

            $order = ShipmentOrder::create($validated);

            // Initialize standard milestones
            $stages = OrderMilestone::defaultStages();
            $sort = 1;
            foreach ($stages as $code => $meta) {
                $isCompleted = false;
                $completedAt = null;
                $completedBy = null;
                $ref = null;

                // Stage 1: PI sent
                if ($code === OrderMilestone::STAGE_PI_SENT && ($order->document_id || $order->proforma_invoice_no || $order->document_reference)) {
                    $isCompleted = true;
                    $completedAt = Carbon::now();
                    $completedBy = $user->id;
                    $ref = $order->document?->document_number ?? $order->proforma_invoice_no ?? $order->document_reference;
                }

                // Stage 2: PO received
                if ($code === OrderMilestone::STAGE_PO_RECEIVED && ! empty($order->customer_po_number)) {
                    $isCompleted = true;
                    $completedAt = Carbon::now();
                    $completedBy = $user->id;
                    $ref = $order->customer_po_number;
                }

                // Stage 3: Payment Submitted
                if ($code === OrderMilestone::STAGE_PAYMENT_SUBMITTED && (
                    $order->payment_status !== ShipmentOrder::PAYMENT_STATUS_PENDING ||
                    ! empty($order->payment_submission_ref) ||
                    $order->payment_submitted_at
                )) {
                    $isCompleted = true;
                    $completedAt = $order->payment_submitted_at ?? Carbon::now();
                    $completedBy = $user->id;
                    $ref = $order->payment_submission_ref ?? $order->payment_reference ?? 'Submitted';
                }

                // Stage 4: Payment Confirmed
                if ($code === OrderMilestone::STAGE_PAYMENT_CONFIRMED && (
                    in_array($order->payment_status, [ShipmentOrder::PAYMENT_STATUS_ADVANCE, ShipmentOrder::PAYMENT_STATUS_FULLY_PAID]) ||
                    $order->payment_confirmed_at
                )) {
                    $isCompleted = true;
                    $completedAt = $order->payment_confirmed_at ?? Carbon::now();
                    $completedBy = $order->payment_confirmed_by ?? $user->id;
                    $ref = $order->payment_reference ?? 'Confirmed';
                }

                // Stage 6: Invoice / Packing list
                if ($code === OrderMilestone::STAGE_FINAL_INVOICE_PL && ! empty($order->linked_invoice_no)) {
                    $isCompleted = true;
                    $completedAt = Carbon::now();
                    $completedBy = $user->id;
                    $ref = $order->linked_invoice_no;
                }

                $order->milestones()->create([
                    'stage_code' => $code,
                    'stage_name' => $meta['name'],
                    'notes' => $meta['description'],
                    'is_completed' => $isCompleted,
                    'completed_at' => $completedAt,
                    'completed_by' => $completedBy,
                    'reference_no' => $ref,
                    'sort_order' => $sort++,
                ]);
            }

            return $order;
        });

        return redirect()->route('shipment-orders.show', $order)
            ->with('success', "Shipment Order Tracker {$order->order_number} initialized successfully.");
    }

    /**
     * Display interactive order tracking cockpit.
     */
    public function show(ShipmentOrder $shipmentOrder): View
    {
        $shipmentOrder->load([
            'document.items',
            'document.packages',
            'invoiceDocument',
            'packingListDocument',
            'milestones.completedBy',
            'creator',
            'paymentConfirmedBy',
        ]);

        return view('shipment_orders.show', compact('shipmentOrder'));
    }

    /**
     * Show form for editing a shipment order and its linked documents.
     */
    public function edit(ShipmentOrder $shipmentOrder): View
    {
        $shipmentOrder->load(['document', 'invoiceDocument', 'packingListDocument']);
        $categories = ShipmentOrder::CATEGORIES;

        $systemPIs = Document::whereIn('document_type', ['proforma_invoice'])
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'document_number', 'company_name', 'country']);

        $systemInvoices = Document::whereIn('document_type', ['invoice', 'commercial_invoice'])
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'document_number', 'company_name', 'country']);

        $systemPackingLists = Document::whereIn('document_type', ['packing_list', 'packing'])
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'document_number', 'company_name', 'country']);

        return view('shipment_orders.edit', compact('shipmentOrder', 'categories', 'systemPIs', 'systemInvoices', 'systemPackingLists'));
    }

    /**
     * Update order metadata (PO number, carrier, tracking no, linked docs, etc.).
     */
    public function update(Request $request, ShipmentOrder $shipmentOrder): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'shipment_category' => 'nullable|string|in:'.implode(',', ShipmentOrder::CATEGORIES),
            'document_id' => 'nullable|exists:documents,id',
            'invoice_document_id' => 'nullable|exists:documents,id',
            'packing_list_document_id' => 'nullable|exists:documents,id',
            'proforma_invoice_no' => 'nullable|string|max:100',
            'customer_po_number' => 'nullable|string|max:100',
            'customer_po_date' => 'nullable|date',
            'customer_po_notes' => 'nullable|string',
            'payment_status' => 'required|in:'.implode(',', ShipmentOrder::PAYMENT_STATUSES),
            'payment_reference' => 'nullable|string|max:100',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_submission_ref' => 'nullable|string|max:100',
            'payment_submission_notes' => 'nullable|string',
            'payment_submitted_at' => 'nullable|date',
            'payment_confirmed_at' => 'nullable|date',
            'currency' => 'required|in:USD,AED',
            'linked_invoice_no' => 'nullable|string|max:60',
            'linked_packing_list_no' => 'nullable|string|max:60',
            'carrier_method' => 'nullable|string|max:50',
            'tracking_awb_no' => 'nullable|string|max:100',
            'dispatch_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'status' => 'required|in:active,completed,cancelled',
            'custom_status_message' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Auto-resolve PI
        if (! empty($validated['document_id'])) {
            $doc = Document::find($validated['document_id']);
            if ($doc && empty($validated['proforma_invoice_no'])) {
                $validated['proforma_invoice_no'] = $doc->document_number;
            }
        } elseif (! empty($validated['proforma_invoice_no'])) {
            $doc = Document::where('document_number', trim($validated['proforma_invoice_no']))->first();
            $validated['document_id'] = $doc?->id;
        }

        // Auto-resolve Commercial Invoice
        if (! empty($validated['invoice_document_id'])) {
            $invDoc = Document::find($validated['invoice_document_id']);
            if ($invDoc && empty($validated['linked_invoice_no'])) {
                $validated['linked_invoice_no'] = $invDoc->document_number;
            }
        } elseif (! empty($validated['linked_invoice_no'])) {
            $invDoc = Document::where('document_number', trim($validated['linked_invoice_no']))->first();
            $validated['invoice_document_id'] = $invDoc?->id;
        }

        // Auto-resolve Packing List
        if (! empty($validated['packing_list_document_id'])) {
            $plDoc = Document::find($validated['packing_list_document_id']);
            if ($plDoc && empty($validated['linked_packing_list_no'])) {
                $validated['linked_packing_list_no'] = $plDoc->document_number;
            }
        } elseif (! empty($validated['linked_packing_list_no'])) {
            $plDoc = Document::where('document_number', trim($validated['linked_packing_list_no']))->first();
            $validated['packing_list_document_id'] = $plDoc?->id;
        }

        if ($validated['payment_status'] === ShipmentOrder::PAYMENT_STATUS_SUBMITTED && empty($validated['payment_submitted_at'])) {
            $validated['payment_submitted_at'] = Carbon::now();
        }
        if (in_array($validated['payment_status'], [ShipmentOrder::PAYMENT_STATUS_ADVANCE, ShipmentOrder::PAYMENT_STATUS_FULLY_PAID])) {
            if (empty($validated['payment_submitted_at'])) {
                $validated['payment_submitted_at'] = Carbon::now();
            }
            if (empty($validated['payment_confirmed_at'])) {
                $validated['payment_confirmed_at'] = Carbon::now();
                $validated['payment_confirmed_by'] = $request->user()->id;
            }
        }

        $shipmentOrder->update($validated);

        // Auto-complete invoice/packing list milestone if linked
        if (! empty($shipmentOrder->linked_invoice_no)) {
            $invMilestone = $shipmentOrder->milestones()->where('stage_code', OrderMilestone::STAGE_FINAL_INVOICE_PL)->first();
            if ($invMilestone && ! $invMilestone->is_completed) {
                $invMilestone->update([
                    'is_completed' => true,
                    'completed_at' => Carbon::now(),
                    'completed_by' => $request->user()->id,
                    'reference_no' => $shipmentOrder->linked_invoice_no,
                ]);
            }
        }

        return redirect()->route('shipment-orders.show', $shipmentOrder)
            ->with('success', "Order {$shipmentOrder->order_number} details and linked documents updated.");
    }

    /**
     * AJAX: Live toggle milestone completion status.
     */
    public function toggleMilestone(Request $request, ShipmentOrder $shipmentOrder, OrderMilestone $milestone): JsonResponse
    {
        $user = $request->user();

        $isCompleted = ! $milestone->is_completed;
        $refNo = $request->input('reference_no', $milestone->reference_no);
        $notes = $request->input('notes', $milestone->notes);

        $milestone->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? Carbon::now() : null,
            'completed_by' => $isCompleted ? $user->id : null,
            'reference_no' => $refNo,
            'notes' => $notes,
        ]);

        // Auto-sync order fields based on milestone
        if ($isCompleted) {
            if ($milestone->stage_code === OrderMilestone::STAGE_PO_RECEIVED && $refNo) {
                $shipmentOrder->update(['customer_po_number' => $refNo]);
            }
            if ($milestone->stage_code === OrderMilestone::STAGE_PAYMENT_SUBMITTED) {
                $updateData = ['payment_submitted_at' => Carbon::now()];
                if ($refNo) {
                    $updateData['payment_submission_ref'] = $refNo;
                }
                if ($shipmentOrder->payment_status === ShipmentOrder::PAYMENT_STATUS_PENDING) {
                    $updateData['payment_status'] = ShipmentOrder::PAYMENT_STATUS_SUBMITTED;
                }
                $shipmentOrder->update($updateData);
            }
            if ($milestone->stage_code === OrderMilestone::STAGE_PAYMENT_CONFIRMED) {
                $updateData = [
                    'payment_confirmed_at' => Carbon::now(),
                    'payment_confirmed_by' => $user->id,
                ];
                if ($refNo) {
                    $updateData['payment_reference'] = $refNo;
                }
                if (in_array($shipmentOrder->payment_status, [ShipmentOrder::PAYMENT_STATUS_PENDING, ShipmentOrder::PAYMENT_STATUS_SUBMITTED])) {
                    $updateData['payment_status'] = ShipmentOrder::PAYMENT_STATUS_FULLY_PAID;
                }
                $shipmentOrder->update($updateData);
            }
            if ($milestone->stage_code === OrderMilestone::STAGE_DISPATCHED && $refNo) {
                $shipmentOrder->update(['tracking_awb_no' => $refNo, 'dispatch_date' => Carbon::now()]);
            }
            if ($milestone->stage_code === OrderMilestone::STAGE_DELIVERED) {
                $shipmentOrder->update(['delivery_date' => Carbon::now(), 'status' => 'completed']);
            }
        }

        $shipmentOrder->refresh();

        return response()->json([
            'success' => true,
            'is_completed' => $milestone->is_completed,
            'completed_at' => $milestone->completed_at ? $milestone->completed_at->format('M d, Y H:i') : null,
            'completed_by_name' => $user->name,
            'progress_percent' => $shipmentOrder->progress_percent,
            'completed_count' => $shipmentOrder->completed_milestones_count,
            'total_count' => $shipmentOrder->milestones()->count(),
        ]);
    }

    /**
     * One-click mark shipment order as completed and verify all remaining milestones.
     */
    public function markCompleted(Request $request, ShipmentOrder $shipmentOrder): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($request, $shipmentOrder) {
            $user = $request->user();
            $now = Carbon::now();

            $shipmentOrder->update([
                'status' => 'completed',
                'delivery_date' => $shipmentOrder->delivery_date ?? $now,
            ]);

            foreach ($shipmentOrder->milestones as $milestone) {
                if (! $milestone->is_completed) {
                    $milestone->update([
                        'is_completed' => true,
                        'completed_at' => $now,
                        'completed_by' => $user->id,
                        'notes' => $milestone->notes ?: 'Verified via quick completion',
                    ]);
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Shipment Order {$shipmentOrder->order_number} marked as completed successfully.",
                'status' => 'completed',
                'progress_percent' => 100,
                'completed_count' => $shipmentOrder->milestones()->count(),
                'total_count' => $shipmentOrder->milestones()->count(),
            ]);
        }

        return redirect()->route('shipment-orders.show', $shipmentOrder)
            ->with('success', "Shipment Order {$shipmentOrder->order_number} marked as completed successfully.");
    }

    /**
     * Update custom status message on shipment order.
     */
    public function updateCustomStatus(Request $request, ShipmentOrder $shipmentOrder): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'custom_status_message' => 'nullable|string|max:255',
        ]);

        $customMessage = ! empty($validated['custom_status_message']) ? trim($validated['custom_status_message']) : null;

        $shipmentOrder->update([
            'custom_status_message' => $customMessage,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'custom_status_message' => $shipmentOrder->custom_status_message,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Custom status message updated successfully.');
    }
}
