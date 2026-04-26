<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restock;
use Illuminate\Http\Request;

class RestockController extends Controller
{
    /**
     * Get all restock records.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 100);
        $limit = min($limit, 500);

        $restocks = Restock::with(['product:id,name,sku', 'user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($restocks);
    }

    /**
     * Get restocks for a specific product.
     */
    public function getByProduct($productId)
    {
        $restocks = Restock::with(['user:id,name,email'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($restocks);
    }

    /**
     * Get restock statistics.
     */
    public function statistics(Request $request)
    {
        $days = $request->input('days', 30);

        $restocks = Restock::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(quantity_added) as total_items_restocked,
                SUM(total_cost) as total_cost
            ')
            ->first();

        return response()->json($restocks);
    }

    /**
     * Get restock statistics for a specific month.
     */
    public function monthlyStatistics(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        $restocks = Restock::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(quantity_added) as total_items_restocked,
                SUM(total_cost) as total_cost
            ')
            ->first();

        return response()->json([
            'year' => (int) $year,
            'month' => (int) $month,
            'period' => $startDate->format('F Y'),
            'total_transactions' => $restocks->total_transactions ?? 0,
            'total_items_restocked' => $restocks->total_items_restocked ?? 0,
            'total_cost' => $restocks->total_cost ?? 0,
        ]);
    }
}
