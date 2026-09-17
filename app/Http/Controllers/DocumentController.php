<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Models\Currency;
use App\Models\Document;
use App\Models\DocumentShipmentCost;
use App\Services\DocumentLockService;
use App\Services\DocumentTypeDetector;
use App\Services\DocumentVersionService;
use App\Services\FreightCalculationService;
use App\Services\OrderReservationService;
use App\Services\SupplierOrderFulfillmentService;
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
        $query = Document::with(['creator', 'lock.user', 'items'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $cleanNumeric = preg_replace('/^(?:proforma\s+invoice|proforma|pi|invoice|inv)\s+/i', '', $search);
            $cleanNumeric = preg_replace('/^(?:USD|EUR|GBP|AED|\$|€|£|¥)\s*/i', '', $cleanNumeric);
            $cleanNumeric = preg_replace('/\s*(?:USD|EUR|GBP|AED)$/i', '', $cleanNumeric);
            $cleanNumeric = str_replace(',', '', trim($cleanNumeric));
            $amountQuery = (is_numeric($cleanNumeric) && (float) $cleanNumeric > 0) ? (float) $cleanNumeric : null;

            $query->where(function ($q) use ($search, $amountQuery, $cleanNumeric) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");

                if ($amountQuery !== null) {
                    $q->orWhere('final_total', $amountQuery)
                        ->orWhere('subtotal', $amountQuery)
                        ->orWhere('final_total', 'like', "{$cleanNumeric}%")
                        ->orWhere('subtotal', 'like', "{$cleanNumeric}%");
                }
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

        // Summary counts for filter badges (single aggregated query)
        $typeCounts = Document::selectRaw('document_type, count(*) as total')
            ->groupBy('document_type')
            ->pluck('total', 'document_type');

        $types = Document::documentTypes();

        $counts = ['all' => (int) $typeCounts->sum()];
        foreach (array_keys($types) as $typeKey) {
            $counts[$typeKey] = (int) ($typeCounts[$typeKey] ?? 0);
        }

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
        if ($request->filled('source_document_id') || $request->filled('source_order_id')) {
            $srcId = $request->input('source_document_id') ?: $request->input('source_order_id');
            $sourceDoc = Document::with(['items', 'packages', 'shipmentCosts'])
                ->where('uuid', $srcId)
                ->orWhere(function ($q) use ($srcId) {
                    if (is_numeric($srcId)) {
                        $q->where('id', $srcId);
                    }
                })
                ->first();
        } elseif ($request->filled('source_document_number')) {
            $sourceDoc = Document::with(['items', 'packages', 'shipmentCosts'])->where('document_number', trim($request->source_document_number))->first();
        }

        $availableSourceDocs = Document::orderByDesc('id')
            ->limit(100)
            ->get(['id', 'uuid', 'document_number', 'document_type', 'company_name', 'country', 'currency', 'document_date']);

        $targetType = $request->query('type', '');

        if ($sourceDoc && $targetType === Document::TYPE_FACTORY_INVOICE && $sourceDoc->isSupplierOrder()) {
            $fulfillmentService = app(SupplierOrderFulfillmentService::class);
            $summary = $fulfillmentService->getOrderSheetSummary($sourceDoc);
            $remainingMap = collect($summary['items'])->keyBy('item_code');
            foreach ($sourceDoc->items as $item) {
                if (isset($remainingMap[$item->item_code])) {
                    $item->unit_amount = $remainingMap[$item->item_code]['remaining_qty'];
                }
            }
        }

        $recentCustomers = Document::whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->distinct()
            ->orderBy('company_name')
            ->limit(200)
            ->pluck('company_name');

        $currencies = Currency::getAllActive();

        return view('documents.create', compact('types', 'defaultDate', 'sourceDoc', 'availableSourceDocs', 'targetType', 'recentCustomers', 'currencies'));
    }

    /**
     * API: Fetch source document data (company, items, packages, shipment costs) for live import with confirmation.
     */
    public function getSourceData(Request $request, string $identifier): JsonResponse
    {
        $identifier = trim($identifier);
        $doc = is_numeric($identifier)
            ? Document::with(['items', 'packages', 'shipmentCosts'])->find($identifier)
            : (Document::with(['items', 'packages', 'shipmentCosts'])->where('uuid', $identifier)->first()
                ?? Document::with(['items', 'packages', 'shipmentCosts'])->where('document_number', $identifier)->first());

        if (! $doc) {
            return response()->json(['error' => "Source document '{$identifier}' was not found in the database."], 404);
        }

        $shipmentCosts = $doc->shipmentCosts->keyBy('method');

        return response()->json([
            'id' => $doc->id,
            'uuid' => $doc->uuid,
            'document_number' => $doc->document_number,
            'document_type' => $doc->document_type,
            'document_type_label' => $doc->formatted_type,
            'company_name' => $doc->company_name,
            'country' => $doc->country,
            'address' => $doc->address,
            'contact_details' => $doc->contact_details,
            'currency' => $doc->currency,
            'price_list' => $doc->price_list ?? $doc->effective_price_list,
            'price_label' => $doc->price_label ?? $doc->effective_price_label,
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
                    'price_list' => $item->price_list,
                    'is_fallback' => (bool) $item->is_fallback,
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
            if (! empty($validated['source_document_id'])) {
                $src = Document::where('uuid', $validated['source_document_id'])
                    ->orWhere(function ($q) use ($validated) {
                        if (is_numeric($validated['source_document_id'])) {
                            $q->where('id', $validated['source_document_id']);
                        }
                    })->first();
                if ($src) {
                    $validated['source_document_id'] = $src->id;
                    if (empty($validated['source_document_number'])) {
                        $validated['source_document_number'] = $src->document_number;
                    }
                }
            } elseif (! empty($validated['source_document_number']) && empty($validated['source_document_id'])) {
                $validated['source_document_id'] = Document::where('document_number', trim($validated['source_document_number']))->first()?->id;
            }

            // Calculate totals
            $isWeightOnly = in_array($validated['document_type'], [Document::TYPE_PACKING_LIST, Document::TYPE_RESERVE, Document::TYPE_DELIVERY_NOTE]);
            $isQuantityOnly = in_array($validated['document_type'], [Document::TYPE_SUPPLIER_ORDER, Document::TYPE_FACTORY_INVOICE])
                || str_starts_with(strtoupper($validated['document_number']), 'B');
            $itemsData = $this->prepareItemsData($request->input('items', []), $isQuantityOnly, $isWeightOnly);
            $calculatedNetWeight = collect($itemsData)->sum('total_weight');
            if (empty($validated['total_net_weight']) && $calculatedNetWeight > 0) {
                $validated['total_net_weight'] = $calculatedNetWeight;
            }

            if ($isWeightOnly || $isQuantityOnly) {
                $subtotal = 0;
                $finalTotal = 0;
            } else {
                $subtotal = collect($itemsData)->sum('total_amount');
                $userFinalTotal = isset($validated['final_total']) && $validated['final_total'] !== '' ? floatval($validated['final_total']) : null;
                $selectedMethod = $request->input('selected_shipment_method');
                $shipmentCostsInput = $request->input('shipment_costs', []);
                $carrierFreight = 0;

                if ($selectedMethod && isset($shipmentCostsInput[$selectedMethod])) {
                    $cost = $shipmentCostsInput[$selectedMethod];
                    $sys = isset($cost['system_amount']) && $cost['system_amount'] !== '' ? floatval($cost['system_amount']) : 0;
                    $add = isset($cost['added_amount']) && $cost['added_amount'] !== '' ? floatval($cost['added_amount']) : 0;
                    if (isset($cost['given_amount']) && $cost['given_amount'] !== '' && floatval($cost['given_amount']) > 0) {
                        $carrierFreight = floatval($cost['given_amount']);
                    } elseif ($sys + $add > 0) {
                        $carrierFreight = round($sys + $add, 2);
                    }
                } elseif (! $selectedMethod) {
                    foreach (['dhl', 'air_freight', 'sea_freight'] as $m) {
                        if (isset($shipmentCostsInput[$m])) {
                            $cost = $shipmentCostsInput[$m];
                            $sys = isset($cost['system_amount']) && $cost['system_amount'] !== '' ? floatval($cost['system_amount']) : 0;
                            $add = isset($cost['added_amount']) && $cost['added_amount'] !== '' ? floatval($cost['added_amount']) : 0;
                            $amount = 0;
                            if (isset($cost['given_amount']) && $cost['given_amount'] !== '' && floatval($cost['given_amount']) > 0) {
                                $amount = floatval($cost['given_amount']);
                            } elseif ($sys + $add > 0) {
                                $amount = round($sys + $add, 2);
                            }
                            if ($amount > 0) {
                                $carrierFreight = $amount;
                                break;
                            }
                        }
                    }
                }

                $expectedTotal = round($subtotal + $carrierFreight, 2);
                if ($carrierFreight > 0) {
                    if ($userFinalTotal === null || abs($userFinalTotal - $subtotal) < 0.001 || abs($userFinalTotal - $expectedTotal) < 0.001) {
                        $finalTotal = $expectedTotal;
                    } else {
                        $finalTotal = $userFinalTotal;
                    }
                } else {
                    $finalTotal = ($userFinalTotal !== null && ($userFinalTotal > 0 || $subtotal == 0))
                        ? $userFinalTotal
                        : round($subtotal, 2);
                }
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
                'price_list' => $validated['price_list'] ?? null,
                'price_label' => $validated['price_label'] ?? null,
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

            // Save shipment method costs (only for financial documents)
            if (! $isWeightOnly && ! $isQuantityOnly) {
                $this->saveShipmentCosts($doc, $request->input('shipment_costs', []));
            }

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
        $checklists = ChecklistTemplate::where('document_type', $document->document_type)
            ->active()
            ->get();

        return view('documents.show', compact('document', 'activeLock', 'types', 'checklists'));
    }

    /**
     * Display a clean, full-page printable view of the document.
     */
    public function print(Document $document): View
    {
        $document->load([
            'items',
            'packages',
            'shipmentCosts',
            'creator',
            'sourceDocument',
            'orderReservation.items',
        ]);

        return view('documents.print', compact('document'));
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
        $currencies = Currency::getAllActive();

        return view('documents.edit', compact('document', 'types', 'shipmentCosts', 'currencies'));
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
            if (! empty($validated['source_document_id'])) {
                $src = Document::where('uuid', $validated['source_document_id'])
                    ->orWhere(function ($q) use ($validated) {
                        if (is_numeric($validated['source_document_id'])) {
                            $q->where('id', $validated['source_document_id']);
                        }
                    })->first();
                if ($src) {
                    $validated['source_document_id'] = $src->id;
                    if (empty($validated['source_document_number'])) {
                        $validated['source_document_number'] = $src->document_number;
                    }
                }
            } elseif (! empty($validated['source_document_number']) && empty($validated['source_document_id'])) {
                $validated['source_document_id'] = Document::where('document_number', trim($validated['source_document_number']))->first()?->id;
            }

            $isWeightOnly = in_array($validated['document_type'], [Document::TYPE_PACKING_LIST, Document::TYPE_RESERVE, Document::TYPE_DELIVERY_NOTE]);
            $isQuantityOnly = in_array($validated['document_type'], [Document::TYPE_SUPPLIER_ORDER, Document::TYPE_FACTORY_INVOICE])
                || str_starts_with(strtoupper($document->document_number), 'B');
            $itemsData = $this->prepareItemsData($request->input('items', []), $isQuantityOnly, $isWeightOnly);
            $calculatedNetWeight = collect($itemsData)->sum('total_weight');
            if (empty($validated['total_net_weight']) && $calculatedNetWeight > 0) {
                $validated['total_net_weight'] = $calculatedNetWeight;
            }

            if ($isWeightOnly || $isQuantityOnly) {
                $subtotal = 0;
                $finalTotal = 0;
            } else {
                $subtotal = collect($itemsData)->sum('total_amount');
                $userFinalTotal = isset($validated['final_total']) && $validated['final_total'] !== '' ? floatval($validated['final_total']) : null;
                $selectedMethod = $request->input('selected_shipment_method');
                $shipmentCostsInput = $request->input('shipment_costs', []);
                $carrierFreight = 0;

                if ($selectedMethod && isset($shipmentCostsInput[$selectedMethod])) {
                    $cost = $shipmentCostsInput[$selectedMethod];
                    $sys = isset($cost['system_amount']) && $cost['system_amount'] !== '' ? floatval($cost['system_amount']) : 0;
                    $add = isset($cost['added_amount']) && $cost['added_amount'] !== '' ? floatval($cost['added_amount']) : 0;
                    if (isset($cost['given_amount']) && $cost['given_amount'] !== '' && floatval($cost['given_amount']) > 0) {
                        $carrierFreight = floatval($cost['given_amount']);
                    } elseif ($sys + $add > 0) {
                        $carrierFreight = round($sys + $add, 2);
                    }
                } elseif (! $selectedMethod) {
                    foreach (['dhl', 'air_freight', 'sea_freight'] as $m) {
                        if (isset($shipmentCostsInput[$m])) {
                            $cost = $shipmentCostsInput[$m];
                            $sys = isset($cost['system_amount']) && $cost['system_amount'] !== '' ? floatval($cost['system_amount']) : 0;
                            $add = isset($cost['added_amount']) && $cost['added_amount'] !== '' ? floatval($cost['added_amount']) : 0;
                            $amount = 0;
                            if (isset($cost['given_amount']) && $cost['given_amount'] !== '' && floatval($cost['given_amount']) > 0) {
                                $amount = floatval($cost['given_amount']);
                            } elseif ($sys + $add > 0) {
                                $amount = round($sys + $add, 2);
                            }
                            if ($amount > 0) {
                                $carrierFreight = $amount;
                                break;
                            }
                        }
                    }
                }

                $expectedTotal = round($subtotal + $carrierFreight, 2);
                if ($carrierFreight > 0) {
                    if ($userFinalTotal === null || abs($userFinalTotal - $subtotal) < 0.001 || abs($userFinalTotal - $expectedTotal) < 0.001) {
                        $finalTotal = $expectedTotal;
                    } else {
                        $finalTotal = $userFinalTotal;
                    }
                } else {
                    $finalTotal = ($userFinalTotal !== null && ($userFinalTotal > 0 || $subtotal == 0))
                        ? $userFinalTotal
                        : round($subtotal, 2);
                }
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
                'price_list' => array_key_exists('price_list', $validated) ? $validated['price_list'] : $document->price_list,
                'price_label' => array_key_exists('price_label', $validated) ? $validated['price_label'] : $document->price_label,
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

            // Replace shipment costs (only for financial documents)
            if (! $isWeightOnly && ! $isQuantityOnly) {
                $this->saveShipmentCosts($document, $request->input('shipment_costs', []));
            } else {
                $document->shipmentCosts()->delete();
            }

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
            'source_document_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (! empty($value)) {
                        $exists = Document::where('uuid', $value)
                            ->orWhere(function ($q) use ($value) {
                                if (is_numeric($value)) {
                                    $q->where('id', $value);
                                }
                            })->exists();
                        if (! $exists) {
                            $fail("The selected {$attribute} is invalid.");
                        }
                    }
                },
            ],
            'source_document_number' => 'nullable|string|max:60',
            'company_name' => 'required|string|max:255',
            'country' => 'required|string|max:100',
            'address' => 'nullable|string',
            'contact_details' => 'nullable|string',
            'document_date' => 'required|date',
            'currency' => [
                'required',
                'string',
                'max:10',
                function ($attribute, $value, $fail) {
                    $code = strtoupper(trim((string) $value));
                    if (! in_array($code, ['USD', 'AED']) && ! Currency::where('code', $code)->where('is_active', true)->exists()) {
                        $fail("The selected currency '{$value}' is invalid or not active.");
                    }
                },
            ],
            'price_list' => 'nullable|string|max:50',
            'price_label' => 'nullable|string|max:50',
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
    protected function prepareItemsData(array $rawItems, bool $isQuantityOnly = false, bool $isWeightOnly = false): array
    {
        $formatted = [];
        $order = 1;

        foreach ($rawItems as $item) {
            if (empty($item['item_code']) && empty($item['description'])) {
                continue;
            }

            $rawQty = $item['unit_amount'] ?? null;
            $qty = ($rawQty !== null && $rawQty !== '' && is_numeric($rawQty)) ? floatval($rawQty) : 1;
            $unitPrice = (! $isWeightOnly && ! $isQuantityOnly && isset($item['unit_price']) && $item['unit_price'] !== '') ? floatval($item['unit_price']) : 0;
            $total = (! $isWeightOnly && ! $isQuantityOnly) ? round($qty * $unitPrice, 2) : 0;
            $unitWeight = (! $isQuantityOnly && isset($item['unit_weight']) && $item['unit_weight'] !== '') ? floatval($item['unit_weight']) : 0;
            $totalWeight = (! $isQuantityOnly && isset($item['total_weight']) && $item['total_weight'] !== '') ? floatval($item['total_weight']) : (! $isQuantityOnly ? round($qty * $unitWeight, 3) : 0);
            $isFallback = ! empty($item['is_fallback']) && filter_var($item['is_fallback'], FILTER_VALIDATE_BOOLEAN);
            $itemPriceList = ! empty($item['price_list']) ? trim($item['price_list']) : null;

            $formatted[] = [
                'item_code' => trim($item['item_code'] ?? 'ITEM'),
                'description' => trim($item['description'] ?? ''),
                'unit_amount' => $qty,
                'unit_price' => $unitPrice,
                'price_list' => $itemPriceList,
                'is_fallback' => $isFallback,
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

            $volWeight = isset($pkg['volumetric_weight_kg']) && $pkg['volumetric_weight_kg'] !== ''
                ? floatval($pkg['volumetric_weight_kg'])
                : FreightCalculationService::calculateVolumetricWeight($len, $wid, $hgt, $qty, $dimType, $dia);
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

            // Chargeable weight is the greater of actual/checked weight and package volumetric weight (if volumetric weight exists)
            $effectiveWeight = $checkedWeight ?? floatval($document->total_gross_weight ?? 0);
            $chargeableWeight = $totalVolumetricWeight > 0
                ? FreightCalculationService::calculateChargeableWeight($effectiveWeight, $totalVolumetricWeight)
                : $effectiveWeight;

            // Auto-compute system amount if rate per kg is given and system amount is not explicitly overridden
            if ($ratePerKg !== null && $systemAmount === null) {
                $systemAmount = FreightCalculationService::calculateFreightAmount($chargeableWeight, $ratePerKg);
            }

            // Auto-compute given amount if system amount or added amount is present and given amount is null
            if ($givenAmount === null && ($systemAmount !== null || $addedAmount !== null)) {
                $givenAmount = round(($systemAmount ?? 0) + ($addedAmount ?? 0), 2);
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

    /**
     * API: Get the latest Proforma Invoice (PI) created for a customer and optional price list filter.
     */
    public function latestPiForCustomer(Request $request): JsonResponse
    {
        $companyName = trim($request->input('company_name', ''));
        $priceList = trim($request->input('price_list', ''));

        if (strlen($companyName) < 2) {
            return response()->json(['found' => false]);
        }

        // Base query for PI documents matching the company name
        $query = Document::with(['items'])
            ->where(function ($q) use ($companyName) {
                $q->where('company_name', $companyName)
                    ->orWhere('company_name', 'LIKE', "{$companyName}%")
                    ->orWhere('company_name', 'LIKE', "%{$companyName}%");
            })
            ->where(function ($q) {
                $q->where('document_type', Document::TYPE_PROFORMA)
                    ->orWhere('document_type', 'like', '%proforma%')
                    ->orWhere('document_number', 'LIKE', 'E%')
                    ->orWhere('document_number', 'LIKE', 'EL%');
            });

        $matchedSpecificPriceList = false;
        $doc = null;

        // If price list filter is provided, try finding a PI matching this price list
        if (! empty($priceList) && strcasecmp($priceList, 'all') !== 0) {
            // 1. Check explicit price_list column
            $specificDoc = (clone $query)
                ->where('price_list', $priceList)
                ->orderByRaw('CASE WHEN LOWER(company_name) = ? THEN 0 ELSE 1 END', [strtolower($companyName)])
                ->orderByDesc('document_date')
                ->orderByDesc('id')
                ->first();

            // 2. Check effective_price_list if not set directly on column
            if (! $specificDoc) {
                $candidateDocs = (clone $query)
                    ->orderByRaw('CASE WHEN LOWER(company_name) = ? THEN 0 ELSE 1 END', [strtolower($companyName)])
                    ->orderByDesc('document_date')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get();

                foreach ($candidateDocs as $candidate) {
                    if (strcasecmp($candidate->effective_price_list ?? '', $priceList) === 0) {
                        $specificDoc = $candidate;
                        break;
                    }
                }
            }

            if ($specificDoc) {
                $doc = $specificDoc;
                $matchedSpecificPriceList = true;
            }
        }

        // Fallback to latest overall PI for this customer
        if (! $doc) {
            $doc = $query
                ->orderByRaw('CASE WHEN LOWER(company_name) = ? THEN 0 ELSE 1 END', [strtolower($companyName)])
                ->orderByDesc('document_date')
                ->orderByDesc('id')
                ->first();
        }

        if (! $doc) {
            return response()->json(['found' => false]);
        }

        $effectivePriceList = $doc->effective_price_list;

        return response()->json([
            'found' => true,
            'id' => $doc->id,
            'uuid' => $doc->uuid,
            'document_number' => $doc->document_number,
            'document_type' => $doc->document_type,
            'formatted_type' => $doc->formatted_type,
            'company_name' => $doc->company_name,
            'document_date' => $doc->document_date ? $doc->document_date->format('Y-m-d') : null,
            'formatted_date' => $doc->document_date ? $doc->document_date->format('d M Y') : null,
            'currency' => $doc->currency,
            'subtotal' => (float) $doc->subtotal,
            'final_total' => (float) $doc->final_total,
            'formatted_final_total' => (Currency::where('code', $doc->currency)->value('symbol') ?: ($doc->currency.' ')).number_format((float) $doc->final_total, 2),
            'items_count' => $doc->items->count(),
            'price_list' => $effectivePriceList,
            'price_label' => $doc->price_label ?? $doc->effective_price_label,
            'requested_price_list' => $priceList ?: null,
            'matched_requested_price_list' => $matchedSpecificPriceList,
            'url' => route('documents.show', $doc),
        ]);
    }
}
