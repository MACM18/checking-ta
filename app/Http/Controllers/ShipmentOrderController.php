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

        $stats = [
            'total' => ShipmentOrder::count(),
            'active' => ShipmentOrder::where('status', 'active')->count(),
            'completed' => ShipmentOrder::where('status', 'completed')->count(),
            'awaiting_po' => ShipmentOrder::where('customer_po_number', null)->where('status', 'active')->count(),
            'dispatched' => ShipmentOrder::whereNotNull('dispatch_date')->where('status', 'active')->count(),
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
            ->limit(50)
            ->get(['id', 'document_number', 'company_name', 'country']);

        $systemInvoices = Document::where('document_type', 'commercial_invoice')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'document_number', 'company_name', 'country']);

        return view('shipment_orders.create', compact('sourceDoc', 'autoOrderNumber', 'categories', 'systemPIs', 'systemInvoices'));
    }

    /**
     * Store a newly created shipment order with initialized milestones.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:50|unique:shipment_orders',
            'document_id' => 'nullable|exists:documents,id',
            'document_reference' => 'nullable|string|max:100',
            'proforma_invoice_no' => 'nullable|string|max:100',
            'company_name' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'shipment_category' => 'nullable|string|in:'.implode(',', ShipmentOrder::CATEGORIES),
            'customer_po_number' => 'nullable|string|max:100',
            'customer_po_date' => 'nullable|date',
            'customer_po_notes' => 'nullable|string',
            'payment_status' => 'required|in:pending,advance_received,fully_paid',
            'payment_reference' => 'nullable|string|max:100',
            'payment_amount' => 'nullable|numeric|min:0',
            'currency' => 'required|in:USD,AED',
            'linked_invoice_no' => 'nullable|string|max:60',
            'linked_packing_list_no' => 'nullable|string|max:60',
            'carrier_method' => 'nullable|string|max:50',
            'tracking_awb_no' => 'nullable|string|max:100',
            'dispatch_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['shipment_category'] = $validated['shipment_category'] ?? ShipmentOrder::CATEGORY_STANDARD;

        $order = DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $validated['created_by'] = $user->id;
            $validated['current_stage'] = 1;

            $order = ShipmentOrder::create($validated);

            // Initialize 8 standard milestones
            $stages = OrderMilestone::defaultStages();
            $sort = 1;
            foreach ($stages as $code => $meta) {
                $isCompleted = false;
                $completedAt = null;
                $completedBy = null;
                $ref = null;

                // Stage 1: PI sent (auto-complete if source document, explicit PI or manual reference is given)
                if ($code === OrderMilestone::STAGE_PI_SENT && ($order->document_id || $order->proforma_invoice_no || $order->document_reference)) {
                    $isCompleted = true;
                    $completedAt = Carbon::now();
                    $completedBy = $user->id;
                    $ref = $order->document?->document_number ?? $order->proforma_invoice_no ?? $order->document_reference;
                }

                // Stage 2: PO received (auto-complete if PO number is provided)
                if ($code === OrderMilestone::STAGE_PO_RECEIVED && ! empty($order->customer_po_number)) {
                    $isCompleted = true;
                    $completedAt = Carbon::now();
                    $completedBy = $user->id;
                    $ref = $order->customer_po_number;
                }

                // Stage 5: Invoice / Packing list (auto-complete if commercial invoice is linked)
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
        $shipmentOrder->load(['document.items', 'document.packages', 'milestones.completedBy', 'creator']);

        return view('shipment_orders.show', compact('shipmentOrder'));
    }

    /**
     * Update order metadata (PO number, carrier, tracking no, etc.).
     */
    public function update(Request $request, ShipmentOrder $shipmentOrder): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'customer_po_number' => 'nullable|string|max:100',
            'customer_po_date' => 'nullable|date',
            'customer_po_notes' => 'nullable|string',
            'payment_status' => 'required|in:pending,advance_received,fully_paid',
            'payment_reference' => 'nullable|string|max:100',
            'payment_amount' => 'nullable|numeric|min:0',
            'currency' => 'required|in:USD,AED',
            'linked_invoice_no' => 'nullable|string|max:60',
            'linked_packing_list_no' => 'nullable|string|max:60',
            'carrier_method' => 'nullable|string|max:50',
            'tracking_awb_no' => 'nullable|string|max:100',
            'dispatch_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'status' => 'required|in:active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $shipmentOrder->update($validated);

        return redirect()->route('shipment-orders.show', $shipmentOrder)
            ->with('success', "Order {$shipmentOrder->order_number} details updated.");
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
}
