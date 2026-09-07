<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Search across Documents, Shipment Orders, Order Reservations, and Price SKUs.
     */
    public function search(Request $request, GlobalSearchService $searchService): JsonResponse
    {
        $query = (string) $request->query('q', $request->query('query', ''));
        $category = $request->query('category');
        $limit = min(max((int) $request->query('limit', 8), 1), 50);

        $results = $searchService->search(
            rawQuery: $query,
            user: $request->user(),
            category: $category,
            limit: $limit
        );

        return response()->json($results);
    }
}
