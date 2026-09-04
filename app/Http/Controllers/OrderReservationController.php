<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\OrderReservation;
use App\Services\OrderReservationService;
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

        // Metrics for top cards
        $metrics = [
            'total' => OrderReservation::count(),
            'pending' => OrderReservation::where('status', OrderReservation::STATUS_PENDING_CHECK)->count(),
            'available' => OrderReservation::where('status', OrderReservation::STATUS_ALL_AVAILABLE)->count(),
            'shortage' => OrderReservation::where('status', OrderReservation::STATUS_HAS_SHORTAGE)->count(),
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
     * One-click warehouse confirmation: all items are available.
     */
    public function confirmAll(Request $request, OrderReservation $orderReservation): RedirectResponse
    {
        $this->authorizeReservations();

        $location = $request->input('warehouse_location');
        $notes = $request->input('warehouse_notes');

        $this->reservationService->confirmAllAvailable($orderReservation, $request->user(), $notes, $location);

        return redirect()->route('order-reservations.show', $orderReservation)
            ->with('success', "Warehouse confirmed! All items for {$orderReservation->reserve_document_number} are verified available in warehouse.");
    }

    /**
     * Batch update item quantities, missing parts & shortage notes.
     */
    public function updateItems(Request $request, OrderReservation $orderReservation): RedirectResponse
    {
        $this->authorizeReservations();

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.available_qty' => ['required', 'numeric', 'min:0'],
            'items.*.bin_location' => ['nullable', 'string', 'max:100'],
            'items.*.supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'items.*.shortage_reason' => ['nullable', 'string'],
            'items.*.remarks' => ['nullable', 'string'],
            'warehouse_location' => ['nullable', 'string', 'max:100'],
            'warehouse_notes' => ['nullable', 'string'],
        ]);

        $this->reservationService->updateItemQuantities(
            $orderReservation,
            $validated['items'],
            $request->user(),
            $validated['warehouse_notes'] ?? null,
            $validated['warehouse_location'] ?? null
        );

        return redirect()->route('order-reservations.show', $orderReservation)
            ->with('success', "Warehouse stock audit saved for {$orderReservation->reserve_document_number}.");
    }

    /**
     * Add a custom missing item / short part.
     */
    public function addShortItem(Request $request, OrderReservation $orderReservation): RedirectResponse
    {
        $this->authorizeReservations();

        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'requested_qty' => ['required', 'numeric', 'min:0.001'],
            'available_qty' => ['required', 'numeric', 'min:0'],
            'bin_location' => ['nullable', 'string', 'max:100'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'shortage_reason' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        $this->reservationService->addShortItem($orderReservation, $validated, $request->user());

        return redirect()->route('order-reservations.show', $orderReservation)
            ->with('success', "Missing item {$validated['item_code']} recorded on reservation.");
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
