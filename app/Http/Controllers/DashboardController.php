<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ItemPrice;
use App\Models\OrderReservation;
use App\Models\OrderReservationItem;
use App\Models\ShipmentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Executive Opening Dashboard.
     */
    public function index(Request $request): View
    {
        // 1. Document KPIs
        $totalDocuments = Document::count();
        $totalFinancialValue = (float) Document::whereNotIn('document_type', [
            Document::TYPE_PACKING_LIST,
            Document::TYPE_RESERVE,
            Document::TYPE_DELIVERY_NOTE,
        ])->sum('final_total');

        $docTypeCounts = Document::selectRaw('document_type, count(*) as count')
            ->groupBy('document_type')
            ->pluck('count', 'document_type')
            ->toArray();

        $draftDocsCount = Document::where('status', 'draft')->count();
        $issuedDocsCount = Document::where('status', '!=', 'draft')->count();

        $recentDocuments = Document::with(['creator', 'items'])
            ->latest('id')
            ->take(6)
            ->get();

        // 2. Shipment Order KPIs
        $activeShipmentsCount = ShipmentOrder::where('status', 'active')->count();
        $completedShipmentsCount = ShipmentOrder::where('status', 'completed')->count();
        $pendingPaymentShipmentsCount = ShipmentOrder::where('status', 'active')
            ->whereIn('payment_status', [
                ShipmentOrder::PAYMENT_STATUS_PENDING,
                ShipmentOrder::PAYMENT_STATUS_SUBMITTED,
            ])->count();

        $recentShipments = ShipmentOrder::with(['document', 'milestones'])
            ->latest('id')
            ->take(5)
            ->get();

        // 3. Order Reservations & Warehouse Shortage KPIs
        $totalReservations = OrderReservation::count();
        $shortageReservationsCount = OrderReservation::where('status', OrderReservation::STATUS_HAS_SHORTAGE)->count();
        $pendingReservationsCount = OrderReservation::where('status', OrderReservation::STATUS_PENDING_CHECK)->count();

        $totalShortParts = (float) OrderReservationItem::where('short_qty', '>', 0)->sum('short_qty');
        $shortItemsCount = OrderReservationItem::where('short_qty', '>', 0)->count();

        $urgentShortages = OrderReservation::with(['items' => function ($q) {
            $q->where('short_qty', '>', 0);
        }])
            ->where(function ($q) {
                $q->where('status', OrderReservation::STATUS_HAS_SHORTAGE)
                    ->orWhere('short_items_count', '>', 0);
            })
            ->latest('id')
            ->take(5)
            ->get();

        // 4. Price Tracker KPIs
        $priceTrackerCount = 0;
        $priceListsCount = 0;
        $latestPriceUpdate = null;

        if (Schema::hasTable('item_prices')) {
            $priceTrackerCount = ItemPrice::count();
            $priceListsCount = ItemPrice::distinct('price_list')->count('price_list');
            $latestPriceUpdate = ItemPrice::latest('updated_at')->value('updated_at');
        }

        // 5. Centralized Export Data Options
        $documentTypes = Document::documentTypes();
        $carrierMethods = [
            'DHL' => 'DHL Express',
            'Air Freight' => 'Air Freight',
            'Sea Freight' => 'Sea Freight',
            'Road Freight' => 'Road Freight',
            'Courier' => 'Courier / Other',
        ];

        return view('dashboard', compact(
            'totalDocuments',
            'totalFinancialValue',
            'docTypeCounts',
            'draftDocsCount',
            'issuedDocsCount',
            'recentDocuments',
            'activeShipmentsCount',
            'completedShipmentsCount',
            'pendingPaymentShipmentsCount',
            'recentShipments',
            'totalReservations',
            'shortageReservationsCount',
            'pendingReservationsCount',
            'totalShortParts',
            'shortItemsCount',
            'urgentShortages',
            'priceTrackerCount',
            'priceListsCount',
            'latestPriceUpdate',
            'documentTypes',
            'carrierMethods'
        ));
    }
}
