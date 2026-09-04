<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Models\Document;
use App\Models\DocumentShipmentCost;
use App\Services\DocumentLockService;
use App\Services\DocumentTypeDetector;
use App\Services\DocumentVersionService;
use App\Services\FreightCalculationService;
use App\Services\OrderReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DocumentController extends Controller
{
    protected DocumentLockService $lockService;

    protected DocumentVersionService $versionService;

    protected OrderReservationService $reservationService;

    public function __construct(
        DocumentLockService $lockService,
        DocumentVersionService $versionService,
        OrderReservationService $reservationService
    ) {
        $this->lockService = $lockService;
        $this->versionService = $versionService;
        $this->reservationService = $reservationService;
    }

    /**
     * Shared workspace document listing with filters & live lock states.
     */
    public function index(Request $request): View
    {
        $query = Document::with(['creator', 'lock.user'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('document_type', $request->type);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $documents = $query->paginate(15)->withQueryString();

        // Summary counts for filter badges
        $counts = [
            'all' => Document::count(),
            Document::TYPE_PROFORMA => Document::where('document_type', Document::TYPE_PROFORMA)->count(),
            Document::TYPE_INVOICE => Document::where('document_type', Document::TYPE_INVOICE)->count(),
            Document::TYPE_PACKING_LIST => Document::where('document_type', Document::TYPE_PACKING_LIST)->count(),
            Document::TYPE_RESERVE => Document::where('document_type', Document::TYPE_RESERVE)->count(),
            Document::TYPE_CREDIT_NOTE => Document::where('document_type', Document::TYPE_CREDIT_NOTE)->count(),
            Document::TYPE_DELIVERY_NOTE => Document::where('document_type', Document::TYPE_DELIVERY_NOTE)->count(),
            Document::TYPE_CLEARING_INVOICE => Document::where('document_type', Document::TYPE_CLEARING_INVOICE)->count(),
            Document::TYPE_CASH_RECEIPT => Document::where('document_type', Document::TYPE_CASH_RECEIPT)->count(),
        ];

        $types = Document::documentTypes();

        return view('documents.index', compact('documents', 'types', 'counts'));
    }

    /**
     * Show form for creating a new document.
     */
    public function create(Request $request): View
    {
        $types = Document::documentTypes();
        $defaultDate = Carbon::now()->format('Y-m-d');

        $sourceDoc = null;
        if ($request->filled('source_document_id')) {
            $sourceDoc = Document::with(['items', 'packages', 'shipmentCosts'])->find($request->source_document_id);
        } elseif ($request->filled('source_document_number')) {
            $sourceDoc = Document::with(['items', 'packages', 'shipmentCosts'])->where('document_number', trim($request->source_document_number))->first();
        }

        $availableSourceDocs = Document::orderByDesc('id')
            ->limit(100)
            ->get(['id', 'document_number', 'document_type', 'company_name', 'country', 'currency', 'document_date']);

        $targetType = $request->query('type', '');

        return view('documents.create', compact('types', 'defaultDate', 'sourceDoc', 'availableSourceDocs', 'targetType'));
    }

    /**
     * API: Fetch source document data (company, items, packages, shipment costs) for live import with confirmation.
     */
    public function getSourceData(Request $request, string $identifier): JsonResponse
    {
        $identifier = trim($identifier);
        $doc = is_numeric($identifier)
            ? Document::with(['items', 'packages', 'shipmentCosts'])->find($identifier)
            : Document::with(['items', 'packages', 'shipmentCosts'])->where('document_number', $identifier)->first();

        if (! $doc) {
            return response()->json(['error' => "Source document '{$identifier}' was not found in the database."], 404);
        }

        $shipmentCosts = $doc->shipmentCosts->keyBy('method');

        return response()->json([
            'id' => $doc->id,
            'document_number' => $doc->document_number,
            'document_type' => $doc->document_type,
            'document_type_label' => $doc->formatted_type,
            'company_name' => $doc->company_name,
            'country' => $doc->country,
            'address' => $doc->address,
            'contact_details' => $doc->contact_details,
            'currency' => $doc->currency,
            'total_net_weight' => (float) $doc->total_net_weight,
            'total_gross_weight' => (float) $doc->total_gross_weight,
            'items' => $doc->items->map(function ($item) {
                return [
                    'item_code' => $item->item_code,
                    'description' => $item->description,
                    'unit_amount' => (float) $item->unit_amount,
                    'unit_price' => (float) $item->unit_price,
                    'unit_weight' => (float) $item->unit_weight,
                    'total_weight' => (float) $item->total_weight,
                    'total_amount' => (float) $item->total_amount,
                ];
            }),
            'packages' => $doc->packages->map(function ($pkg) {
                return [
                    'package_type' => $pkg->package_type,
                    'dimension_type' => $pkg->dimension_type,
                    'length_cm' => (float) $pkg->length_cm,
                    'width_cm' => (float) $pkg->width_cm,
                    'height_cm' => (float) $pkg->height_cm,
                    'diameter_cm' => (float) $pkg->diameter_cm,
                    'quantity' => (int) $pkg->quantity,
                    'gross_weight_per_pkg_kg' => (float) $pkg->gross_weight_per_pkg_kg,
                    'total_gross_weight_kg' => (float) $pkg->total_gross_weight_kg,
                    'volumetric_weight_kg' => (float) $pkg->volumetric_weight_kg,
                    'cbm' => (float) $pkg->cbm,
                ];
            }),
            'shipment_costs' => [
                'dhl' => $shipmentCosts->has('dhl') ? [
                    'checked_weight' => $shipmentCosts['dhl']->checked_weight !== null ? (float) $shipmentCosts['dhl']->checked_weight : null,
                    'rate_per_kg' => $shipmentCosts['dhl']->rate_per_kg !== null ? (float) $shipmentCosts['dhl']->rate_per_kg : null,
                    'chargeable_weight' => $shipmentCosts['dhl']->chargeable_weight !== null ? (float) $shipmentCosts['dhl']->chargeable_weight : null,
                    'system_amount' => $shipmentCosts['dhl']->system_amount !== null ? (float) $shipmentCosts['dhl']->system_amount : null,
                    'added_amount' => $shipmentCosts['dhl']->added_amount !== null ? (float) $shipmentCosts['dhl']->added_amount : null,
                    'given_amount' => $shipmentCosts['dhl']->given_amount !== null ? (float) $shipmentCosts['dhl']->given_amount : null,
                ] : null,
                'air_freight' => $shipmentCosts->has('air_freight') ? [
                    'checked_weight' => $shipmentCosts['air_freight']->checked_weight !== null ? (float) $shipmentCosts['air_freight']->checked_weight : null,
                    'rate_per_kg' => $shipmentCosts['air_freight']->rate_per_kg !== null ? (float) $shipmentCosts['air_freight']->rate_per_kg : null,
                    'chargeable_weight' => $shipmentCosts['air_freight']->chargeable_weight !== null ? (float) $shipmentCosts['air_freight']->chargeable_weight : null,
                    'system_amount' => $shipmentCosts['air_freight']->system_amount !== null ? (float) $shipmentCosts['air_freight']->system_amount : null,
                    'added_amount' => $shipmentCosts['air_freight']->added_amount !== null ? (float) $shipmentCosts['air_freight']->added_amount : null,
                    'given_amount' => $shipmentCosts['air_freight']->given_amount !== null ? (float) $shipmentCosts['air_freight']->given_amount : null,
                ] : null,
                'sea_freight' => $shipmentCosts->has('sea_freight') ? [
                    'checked_weight' => $shipmentCosts['sea_freight']->checked_weight !== null ? (float) $shipmentCosts['sea_freight']->checked_weight : null,
                    'rate_per_kg' => $shipmentCosts['sea_freight']->rate_per_kg !== null ? (float) $shipmentCosts['sea_freight']->rate_per_kg : null,
                    'chargeable_weight' => $shipmentCosts['sea_freight']->chargeable_weight !== null ? (float) $shipmentCosts['sea_freight']->chargeable_weight : null,
                    'system_amount' => $shipmentCosts['sea_freight']->system_amount !== null ? (float) $shipmentCosts['sea_freight']->system_amount : null,
                    'added_amount' => $shipmentCosts['sea_freight']->added_amount !== null ? (float) $shipmentCosts['sea_freight']->added_amount : null,
                    'given_amount' => $shipmentCosts['sea_freight']->given_amount !== null ? (float) $shipmentCosts['sea_freight']->given_amount : null,
                ] : null,
            ],
        ]);
    }

    /**
     * Store newly created document with line items & shipment costs.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDocumentRequest($request);

        $document = DB::transaction(function () use ($validated, $request) {
            $user = $request->user();

            // Auto-resolve source document
            if (! empty($validated['source_document_id']) && empty($validated['source_document_number'])) {
                $validated['source_document_number'] = Document::find($validated['source_document_id'])?->document_number;
            } elseif (! empty($validated['source_document_number']) && empty($validated['source_document_id'])) {
                $validated['source_document_id'] = Document::where('document_number', trim($validated['source_document_number']))->first()?->id;
            }

            // Calculate totals
            $itemsData = $this->prepareItemsData($request->input('items', []));
            $isWeightOnly = in_array($validated['document_type'], [Document::TYPE_PACKING_LIST, Document::TYPE_RESERVE]);

            if ($isWeightOnly) {
                $subtotal = 0;
                $finalTotal = 0;
                $calculatedNetWeight = collect($itemsData)->sum('total_weight');
                if (empty($validated['total_net_weight']) && $calculatedNetWeight > 0) {
                    $validated['total_net_weight'] = $calculatedNetWeight;
                }
            } else {
                $subtotal = collect($itemsData)->sum('total_amount');
                $userFinalTotal = isset($validated['final_total']) && $validated['final_total'] !== '' ? floatval($validated['final_total']) : null;
                $finalTotal = ($userFinalTotal !== null && ($userFinalTotal > 0 || $subtotal == 0))
                    ? $userFinalTotal
                    : $subtotal;
            }

            $doc = Document::create([
                'document_number' => strtoupper(trim($validated['document_number'])),
                'document_type' => $validated['document_type'],
                'source_document_id' => $validated['source_document_id'] ?? null,
                'source_document_number' => $validated['source_document_number'] ?? null,
                'company_name' => $validated['company_name'],
                'country' => $validated['country'],
                'address' => $validated['address'] ?? null,
                'contact_details' => $validated['contact_details'] ?? null,
                'document_date' => $validated['document_date'],
                'currency' => $validated['currency'] ?? 'USD',
                'total_net_weight' => $validated['total_net_weight'] ?? null,
                'total_gross_weight' => $validated['total_gross_weight'] ?? null,
                'subtotal' => $subtotal,
                'final_total' => $finalTotal,
                'current_version' => 1,
                'status' => $validated['status'] ?? 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Save line items
            foreach ($itemsData as $item) {
                $doc->items()->create($item);
            }

            // Save packages
            $this->savePackages($doc, $request->input('packages', []));

            // Save shipment method costs
            $this->saveShipmentCosts($doc, $request->input('shipment_costs', []));

            // Create initial Version 1 snapshot
            $this->versionService->createSnapshot($doc, $user, 'Initial document creation');

            // Auto-initialize Order Reservation if Reserve document
            if ($doc->isReserve()) {
                $this->reservationService->syncFromDocument($doc, $user);
            }

            return $doc;
        });

        return redirect()->route('documents.show', $document)
            ->with('success', "Document {$document->document_number} created successfully.");
    }

    /**
     * Display the document (View-Only / Show).
     */
    public function show(Document $document): View
    {
        $document->load([
            'items',
            'packages',
            'shipmentCosts',
            'shipmentOrders.milestones',
            'versions.creator',
            'creator',
            'updater',
            'lock.user',
            'sourceDocument',
            'derivedDocuments',
            'orderReservation.items',
            'orderReservation.confirmedBy',
        ]);

        if ($document->isReserve() && ! $document->orderReservation) {
            $this->reservationService->syncFromDocument($document);
            $document->load(['orderReservation.items', 'orderReservation.confirmedBy']);
        }

        $activeLock = $document->getActiveLock();
        $types = Document::documentTypes();

        return view('documents.show', compact('document', 'activeLock', 'types'));
    }

    /**
     * Show the edit form with pessimistic locking check.
     */
    public function edit(Request $request, Document $document): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->canEdit()) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'You have view-only access and cannot edit documents.');
        }

        // Try to acquire the lock
        $lockResult = $this->lockService->acquireLock($document, $user);

        if (! $lockResult['acquired']) {
            return redirect()->route('documents.show', $document)
                ->with('locked_alert', "Document {$document->document_number} is currently being edited by {$lockResult['locked_by']}. Opened in View-Only mode.");
        }

        $document->load(['items', 'packages', 'shipmentCosts']);
        $types = Document::documentTypes();

        // Key shipment costs by carrier method
        $shipmentCosts = $document->shipmentCosts->keyBy('method');

        return view('documents.edit', compact('document', 'types', 'shipmentCosts'));
    }

    /**
     * Update the document, create new version snapshot, release lock.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        if (! $user->canEdit()) {
            abort(403, 'Unauthorized');
        }

        // Ensure current user holds the lock
        if ($document->isLockedByOther($user)) {
            $lock = $document->getActiveLock();

            return redirect()->route('documents.show', $document)
                ->with('error', "Cannot save: Document is currently locked by {$lock->user?->name}.");
        }

        $validated = $this->validateDocumentRequest($request, $document->id);

        DB::transaction(function () use ($document, $validated, $request, $user) {
            $createNewVersion = $request->boolean('create_new_version', true);
            $newVersionNumber = $createNewVersion ? ($document->current_version + 1) : $document->current_version;

            // Auto-resolve source document
            if (! empty($validated['source_document_id']) && empty($validated['source_document_number'])) {
                $validated['source_document_number'] = Document::find($validated['source_document_id'])?->document_number;
            } elseif (! empty($validated['source_document_number']) && empty($validated['source_document_id'])) {
                $validated['source_document_id'] = Document::where('document_number', trim($validated['source_document_number']))->first()?->id;
            }

            $itemsData = $this->prepareItemsData($request->input('items', []));
            $isWeightOnly = in_array($validated['document_type'], [Document::TYPE_PACKING_LIST, Document::TYPE_RESERVE]);

            if ($isWeightOnly) {
                $subtotal = 0;
                $finalTotal = 0;
                $calculatedNetWeight = collect($itemsData)->sum('total_weight');
                if (empty($validated['total_net_weight']) && $calculatedNetWeight > 0) {
                    $validated['total_net_weight'] = $calculatedNetWeight;
                }
            } else {
                $subtotal = collect($itemsData)->sum('total_amount');
                $userFinalTotal = isset($validated['final_total']) && $validated['final_total'] !== '' ? floatval($validated['final_total']) : null;
                $finalTotal = ($userFinalTotal !== null && ($userFinalTotal > 0 || $subtotal == 0))
                    ? $userFinalTotal
                    : $subtotal;
            }

            $document->update([
                'document_type' => $validated['document_type'],
                'source_document_id' => $validated['source_document_id'] ?? $document->source_document_id,
                'source_document_number' => $validated['source_document_number'] ?? $document->source_document_number,
                'company_name' => $validated['company_name'],
                'country' => $validated['country'],
                'address' => $validated['address'] ?? null,
                'contact_details' => $validated['contact_details'] ?? null,
                'document_date' => $validated['document_date'],
                'currency' => $validated['currency'] ?? 'USD',
                'total_net_weight' => $validated['total_net_weight'] ?? null,
                'total_gross_weight' => $validated['total_gross_weight'] ?? null,
                'subtotal' => $subtotal,
                'final_total' => $finalTotal,
                'current_version' => $newVersionNumber,
                'status' => $validated['status'] ?? 'draft',
                'notes' => $validated['notes'] ?? null,
                'updated_by' => $user->id,
            ]);

            // Replace line items
            $document->items()->delete();
            foreach ($itemsData as $item) {
                $document->items()->create($item);
            }

            // Replace packages
            $this->savePackages($document, $request->input('packages', []));

            // Replace shipment costs
            $this->saveShipmentCosts($document, $request->input('shipment_costs', []));

            // Snapshot version
            $changeSummary = $request->input('change_summary') ?: "Updated to Version {$newVersionNumber}";
            $this->versionService->createSnapshot($document, $user, $changeSummary);

            // Re-sync reservation items if Reserve document
            if ($document->isReserve()) {
                $this->reservationService->syncFromDocument($document, $user);
            }

            // Release the lock
            $this->lockService->releaseLock($document, $user);
        });

        return redirect()->route('documents.show', $document)
            ->with('success', "Document {$document->document_number} updated to Version {$document->current_version}.");
    }

    /**
     * Delete document (Admin or creator).
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && $document->created_by !== $user->id) {
            return redirect()->route('documents.index')
                ->with('error', 'Only admins or the document creator can delete this document.');
        }

        $docNum = $document->document_number;
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', "Document {$docNum} deleted.");
    }

    /**
     * API: Real-time Document Number Type Detection + Checklists.
     */
    public function detectType(Request $request): JsonResponse
    {
        $number = $request->query('number', '');
        $detection = DocumentTypeDetector::detect($number);

        $checklists = [];
        if ($detection['type']) {
            $checklists = ChecklistTemplate::where('document_type', $detection['type'])
                ->active()
                ->get(['id', 'document_type', 'item_text', 'hint', 'is_required', 'sort_order']);
        }

        return response()->json([
            'detected' => (bool) $detection['type'],
            'type' => $detection['type'],
            'label' => $detection['label'],
            'rule_matched' => $detection['rule_matched'],
            'checklists' => $checklists,
        ]);
    }

    /**
     * Helper: Validate document form requests.
     */
    protected function validateDocumentRequest(Request $request, ?int $documentId = null): array
    {
        return $request->validate([
            'document_number' => 'required|string|max:60',
            'document_type' => 'required|string|max:50',
            'source_document_id' => 'nullable|exists:documents,id',
            'source_document_number' => 'nullable|string|max:60',
            'company_name' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'address' => 'nullable|string',
            'contact_details' => 'nullable|string',
            'document_date' => 'required|date',
            'currency' => 'required|in:USD,AED',
            'total_net_weight' => 'nullable|numeric|min:0',
            'total_gross_weight' => 'nullable|numeric|min:0',
            'final_total' => 'nullable|numeric',
            'status' => 'nullable|in:draft,active,final,cancelled',
            'notes' => 'nullable|string',
        ]);
    }

    /**
     * Helper: Format and sanitize line items array.
     */
    protected function prepareItemsData(array $rawItems): array
    {
        $formatted = [];
        $order = 1;

        foreach ($rawItems as $item) {
            if (empty($item['item_code']) && empty($item['description'])) {
                continue;
            }

            $rawQty = $item['unit_amount'] ?? null;
            $qty = ($rawQty !== null && $rawQty !== '' && is_numeric($rawQty)) ? floatval($rawQty) : 1;
            $unitPrice = isset($item['unit_price']) && $item['unit_price'] !== '' ? floatval($item['unit_price']) : 0;
            $total = round($qty * $unitPrice, 2);
            $unitWeight = isset($item['unit_weight']) && $item['unit_weight'] !== '' ? floatval($item['unit_weight']) : 0;
            $totalWeight = isset($item['total_weight']) && $item['total_weight'] !== '' ? floatval($item['total_weight']) : round($qty * $unitWeight, 3);

            $formatted[] = [
                'item_code' => trim($item['item_code'] ?? 'ITEM'),
                'description' => trim($item['description'] ?? ''),
                'unit_amount' => $qty,
                'unit_price' => $unitPrice,
                'unit_weight' => $unitWeight,
                'total_weight' => $totalWeight,
                'total_amount' => $total,
                'sort_order' => $order++,
            ];
        }

        return $formatted;
    }

    /**
     * Helper: Save package dimensions & diameters.
     */
    protected function savePackages(Document $document, array $rawPackages): void
    {
        $document->packages()->delete();
        $order = 1;

        foreach ($rawPackages as $pkg) {
            $qty = max(1, intval($pkg['quantity'] ?? 1));
            $dimType = ($pkg['dimension_type'] ?? 'standard') === 'diameter' ? 'diameter' : 'standard';
            $dia = isset($pkg['diameter_cm']) && $pkg['diameter_cm'] !== '' ? floatval($pkg['diameter_cm']) : null;
            $len = isset($pkg['length_cm']) && $pkg['length_cm'] !== '' ? floatval($pkg['length_cm']) : null;
            $wid = isset($pkg['width_cm']) && $pkg['width_cm'] !== '' ? floatval($pkg['width_cm']) : null;
            $hgt = isset($pkg['height_cm']) && $pkg['height_cm'] !== '' ? floatval($pkg['height_cm']) : null;
            $grossPerPkg = isset($pkg['gross_weight_per_pkg_kg']) && $pkg['gross_weight_per_pkg_kg'] !== '' ? floatval($pkg['gross_weight_per_pkg_kg']) : null;
            $totalGross = $grossPerPkg !== null ? round($grossPerPkg * $qty, 3) : null;

            if ($dimType === 'diameter' && (! $dia && ! $hgt)) {
                continue;
            }
            if ($dimType === 'standard' && (! $len && ! $wid && ! $hgt)) {
                continue;
            }

            $volWeight = FreightCalculationService::calculateVolumetricWeight($len, $wid, $hgt, $qty, $dimType, $dia);
            $cbm = FreightCalculationService::calculateCbm($len, $wid, $hgt, $qty, $dimType, $dia);

            $document->packages()->create([
                'package_type' => trim($pkg['package_type'] ?? 'Carton'),
                'dimension_type' => $dimType,
                'length_cm' => $len,
                'width_cm' => $wid,
                'height_cm' => $hgt,
                'diameter_cm' => $dia,
                'quantity' => $qty,
                'gross_weight_per_pkg_kg' => $grossPerPkg,
                'total_gross_weight_kg' => $totalGross,
                'volumetric_weight_kg' => $volWeight,
                'cbm' => $cbm,
                'sort_order' => $order++,
            ]);
        }
    }

    /**
     * Helper: Save carrier shipment costs (DHL, Air freight, Sea freight) with Rate per KG & Chargeable Weight.
     */
    protected function saveShipmentCosts(Document $document, array $rawCosts): void
    {
        $document->shipmentCosts()->delete();

        $supported = [
            DocumentShipmentCost::METHOD_DHL,
            DocumentShipmentCost::METHOD_AIR_FREIGHT,
            DocumentShipmentCost::METHOD_SEA_FREIGHT,
        ];

        // Total volumetric weight from saved packages
        $totalVolumetricWeight = (float) $document->packages()->sum('volumetric_weight_kg');

        foreach ($supported as $method) {
            $data = $rawCosts[$method] ?? [];

            $checkedWeight = isset($data['checked_weight']) && $data['checked_weight'] !== '' ? floatval($data['checked_weight']) : null;
            $ratePerKg = isset($data['rate_per_kg']) && $data['rate_per_kg'] !== '' ? floatval($data['rate_per_kg']) : null;
            $systemAmount = isset($data['system_amount']) && $data['system_amount'] !== '' ? floatval($data['system_amount']) : null;
            $addedAmount = isset($data['added_amount']) && $data['added_amount'] !== '' ? floatval($data['added_amount']) : null;
            $givenAmount = isset($data['given_amount']) && $data['given_amount'] !== '' ? floatval($data['given_amount']) : null;

            // Chargeable weight is the greater of actual/checked weight and package volumetric weight
            $effectiveWeight = $checkedWeight ?? floatval($document->total_gross_weight ?? 0);
            $chargeableWeight = FreightCalculationService::calculateChargeableWeight($effectiveWeight, $totalVolumetricWeight);

            // Auto-compute system amount if rate per kg is given and system amount is not explicitly overridden
            if ($ratePerKg !== null && $systemAmount === null) {
                $systemAmount = FreightCalculationService::calculateFreightAmount($chargeableWeight, $ratePerKg);
            }

            if ($checkedWeight !== null || $ratePerKg !== null || $systemAmount !== null || $addedAmount !== null || $givenAmount !== null) {
                $document->shipmentCosts()->create([
                    'method' => $method,
                    'checked_weight' => $checkedWeight,
                    'rate_per_kg' => $ratePerKg,
                    'chargeable_weight' => $chargeableWeight > 0 ? $chargeableWeight : null,
                    'system_amount' => $systemAmount,
                    'added_amount' => $addedAmount,
                    'given_amount' => $givenAmount,
                ]);
            }
        }
    }
}
