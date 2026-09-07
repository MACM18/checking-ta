<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\OrderReservation;
use App\Models\OrderReservationItem;
use App\Services\OrderReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderReservationController extends Controller
{
    public function __construct(
        protected OrderReservationService $reservationService
    ) {}

    protected function authorizeReservations(): void
    {
        if (! Auth::user()?->canManageReservations()) {
            abort(403, 'You do not have permission to manage order reservations.');
        }
    }

    /**
     * Display order reservations list with status tabs & search.
     */
    public function index(Request $request): View
    {
        $this->authorizeReservations();

        $query = OrderReservation::with(['confirmedBy', 'document'])
            ->withCount('items');

        // Status Filter
        $status = $request->input('status', 'all');
        if ($status !== 'all' && array_key_exists($status, OrderReservation::STATUSES)) {
            $query->where('status', $status);
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reservation_number', 'like', "%{$search}%")
                    ->orWhere('reserve_document_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($iq) use ($search) {
                        $iq->where('item_code', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        $reservations = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        // Metrics for top cards (single aggregated query)
        $metricsRaw = OrderReservation::selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN status = ? THEN 1 END) as pending,
            COUNT(CASE WHEN status = ? THEN 1 END) as available,
            COUNT(CASE WHEN status = ? THEN 1 END) as shortage
        ', [
            OrderReservation::STATUS_PENDING_CHECK,
            OrderReservation::STATUS_ALL_AVAILABLE,
            OrderReservation::STATUS_HAS_SHORTAGE,
        ])->first();

        $metrics = [
            'total' => (int) ($metricsRaw->total ?? 0),
            'pending' => (int) ($metricsRaw->pending ?? 0),
            'available' => (int) ($metricsRaw->available ?? 0),
            'shortage' => (int) ($metricsRaw->shortage ?? 0),
        ];

        return view('order_reservations.index', compact('reservations', 'metrics', 'status'));
    }

    /**
     * Show form to record old / external Reserve (R) document.
     */
    public function create(): View
    {
        $this->authorizeReservations();

        return view('order_reservations.create');
    }

    /**
     * Store old / external Reserve (R) document record.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeReservations();

        $validated = $request->validate([
            'reserve_document_number' => ['required', 'string', 'max:60'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'reservation_date' => ['nullable', 'date'],
            'warehouse_location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_code' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.requested_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.available_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.bin_location' => ['nullable', 'string', 'max:100'],
            'items.*.supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'items.*.shortage_reason' => ['nullable', 'string'],
            'items.*.remarks' => ['nullable', 'string'],
        ]);

        $reservation = $this->reservationService->createLegacyReservation($validated, $request->user());

        return redirect()->route('order-reservations.show', $reservation)
            ->with('success', "Order Reservation {$reservation->reserve_document_number} recorded successfully.");
    }

    /**
     * Display the warehouse reservation audit cockpit.
     */
    public function show(OrderReservation $orderReservation): View
    {
        $this->authorizeReservations();

        $orderReservation->load([
            'items',
            'document.items',
            'confirmedBy',
            'creator',
            'updater',
        ]);

        return view('order_reservations.show', compact('orderReservation'));
    }

    /**
     * Show form to edit an existing reservation and its line items.
     */
    public function edit(OrderReservation $orderReservation): View
    {
        $this->authorizeReservations();

        $orderReservation->load(['items', 'document']);

        $initialItems = $orderReservation->items->map(function ($i) {
            return [
                'id' => $i->id,
                'item_code' => $i->item_code,
                'description' => $i->description ?? '',
                'requested_qty' => (float) $i->requested_qty,
                'available_qty' => (float) $i->available_qty,
                'bin_location' => $i->bin_location ?? '',
                'supplier_invoice_no' => $i->supplier_invoice_no ?? '',
                'shortage_reason' => $i->shortage_reason ?? '',
            ];
        })->values();

        return view('order_reservations.edit', compact('orderReservation', 'initialItems'));
    }

    /**
     * Update reservation details and line items.
     */
    public function update(Request $request, OrderReservation $orderReservation): RedirectResponse
    {
        $this->authorizeReservations();

        $validated = $request->validate([
            'reserve_document_number' => ['required', 'string', 'max:60'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'reservation_date' => ['nullable', 'date'],
            'warehouse_location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.item_code' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.requested_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.available_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.bin_location' => ['nullable', 'string', 'max:100'],
            'items.*.supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'items.*.shortage_reason' => ['nullable', 'string'],
            'items.*.remarks' => ['nullable', 'string'],
        ]);

        $this->reservationService->updateReservation($orderReservation, $validated, $request->user());

        return redirect()->route('order-reservations.show', $orderReservation)
            ->with('success', "Order Reservation {$orderReservation->reserve_document_number} updated successfully.");
    }

    /**
     * One-click warehouse confirmation: all items are available.
     */
    public function confirmAll(Request $request, OrderReservation $orderReservation): JsonResponse|RedirectResponse
    {
        $this->authorizeReservations();

        $location = $request->input('warehouse_location');
        $notes = $request->input('warehouse_notes');

        $this->reservationService->confirmAllAvailable($orderReservation, $request->user(), $notes, $location);

        if ($request->wantsJson()) {
            $orderReservation->refresh();

            return response()->json([
                'success' => true,
                'message' => "Warehouse confirmed! All items for {$orderReservation->reserve_document_number} are verified available in warehouse.",
                'status' => $orderReservation->status,
                'status_label' => $orderReservation->status_label,
                'warehouse_confirmed_at' => $orderReservation->warehouse_confirmed_at ? $orderReservation->warehouse_confirmed_at->format('M d, Y H:i') : 'Just now',
                'warehouse_confirmed_by' => $request->user()->name,
                'total_short_qty' => 0,
                'short_items_count' => 0,
            ]);
        }

        return redirect()->route('order-reservations.show', $orderReservation)
            ->with('success', "Warehouse confirmed! All items for {$orderReservation->reserve_document_number} are verified available in warehouse.");
    }

    /**
     * Batch update item quantities, missing parts & shortage notes.
     */
    public function updateItems(Request $request, OrderReservation $orderReservation): JsonResponse|RedirectResponse
    {
        $this->authorizeReservations();

        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.requested_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.available_qty' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.bin_location' => ['nullable', 'string', 'max:100'],
            'items.*.supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'items.*.shortage_reason' => ['nullable', 'string'],
            'items.*.remarks' => ['nullable', 'string'],
            'new_items' => ['nullable', 'array'],
            'new_items.*.item_code' => ['nullable', 'string', 'max:100'],
            'new_items.*.description' => ['nullable', 'string'],
            'new_items.*.requested_qty' => ['nullable', 'numeric', 'min:0'],
            'new_items.*.available_qty' => ['nullable', 'numeric', 'min:0'],
            'new_items.*.bin_location' => ['nullable', 'string', 'max:100'],
            'new_items.*.supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'new_items.*.shortage_reason' => ['nullable', 'string'],
            'new_items.*.remarks' => ['nullable', 'string'],
            'warehouse_location' => ['nullable', 'string', 'max:100'],
            'warehouse_notes' => ['nullable', 'string'],
        ]);

        $this->reservationService->updateItemQuantities(
            $orderReservation,
            $validated['items'] ?? [],
            $request->user(),
            $validated['warehouse_notes'] ?? null,
            $validated['warehouse_location'] ?? null,
            $validated['new_items'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Warehouse stock audit saved for {$orderReservation->reserve_document_number}.",
            ]);
        }

        return redirect()->route('order-reservations.show', $orderReservation)
            ->with('success', "Warehouse stock audit saved for {$orderReservation->reserve_document_number}.");
    }

    /**
     * Add a custom missing item / short part.
     */
    public function addShortItem(Request $request, OrderReservation $orderReservation): JsonResponse|RedirectResponse
    {
        $this->authorizeReservations();

        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'requested_qty' => ['nullable', 'numeric', 'min:0'],
            'available_qty' => ['nullable', 'numeric', 'min:0'],
            'bin_location' => ['nullable', 'string', 'max:100'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'shortage_reason' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        $item = $this->reservationService->addShortItem($orderReservation, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Missing item {$validated['item_code']} recorded on reservation.",
                'item' => $item,
            ]);
        }

        return redirect()->route('order-reservations.show', $orderReservation)
            ->with('success', "Missing item {$validated['item_code']} recorded on reservation.");
    }

    /**
     * Remove an individual item from the order reservation.
     */
    public function destroyItem(Request $request, OrderReservation $orderReservation, OrderReservationItem $orderReservationItem): JsonResponse|RedirectResponse
    {
        $this->authorizeReservations();

        if ($orderReservationItem->order_reservation_id !== $orderReservation->id) {
            abort(404, 'Item does not belong to this order reservation.');
        }

        $itemCode = $orderReservationItem->item_code;
        $this->reservationService->removeItem($orderReservation, $orderReservationItem, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Item {$itemCode} removed from reservation.",
            ]);
        }

        return redirect()->route('order-reservations.show', $orderReservation)
            ->with('success', "Item {$itemCode} removed from reservation.");
    }

    /**
     * Print-optimized shortage / missing items report.
     */
    public function printShortage(OrderReservation $orderReservation): View
    {
        $this->authorizeReservations();

        $orderReservation->load(['items', 'confirmedBy']);

        return view('order_reservations.print-shortage', compact('orderReservation'));
    }

    /**
     * Delete reservation record.
     */
    public function destroy(OrderReservation $orderReservation): RedirectResponse
    {
        $this->authorizeReservations();

        $number = $orderReservation->reserve_document_number;
        $orderReservation->delete();

        return redirect()->route('order-reservations.index')
            ->with('success', "Reservation record {$number} deleted.");
    }
}
