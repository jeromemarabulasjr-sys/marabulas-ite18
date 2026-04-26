<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Get all sales records.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 100);
        $limit = min($limit, 500);

        $sales = Sale::with(['product:id,name,sku', 'user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($sales);
    }

    /**
     * Get sales for a specific product.
     */
    public function getByProduct($productId)
    {
        $sales = Sale::with(['user:id,name,email'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($sales);
    }

    /**
     * Get sales statistics.
     */
    public function statistics(Request $request)
    {
        $days = $request->input('days', 30);

        $sales = Sale::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(quantity_sold) as total_items_sold,
                SUM(total_amount) as total_revenue
            ')
            ->first();

        return response()->json($sales);
    }

    /**
     * Get sales statistics for a specific month.
     */
    public function monthlyStatistics(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(quantity_sold) as total_items_sold,
                SUM(total_amount) as total_revenue
            ')
            ->first();

        return response()->json([
            'year' => (int) $year,
            'month' => (int) $month,
            'period' => $startDate->format('F Y'),
            'total_transactions' => $sales->total_transactions ?? 0,
            'total_items_sold' => $sales->total_items_sold ?? 0,
            'total_revenue' => $sales->total_revenue ?? 0,
        ]);
    }
}
