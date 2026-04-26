<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\Sale;
use App\Models\Restock;
use App\Models\MonthlySummary;
use App\Models\ActivityLog;
use App\Services\MonthlySummaryService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InventoryReportController extends Controller
{
    /**
     * Generate a detailed inventory report for a specific date range.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @phpstan-ignore-next-line
     */
    #[\ReturnTypeWillChange]
    public function generate(Request $request): \Illuminate\Http\JsonResponse
    {
        // Parse date range
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now();

        // Get current inventory status
        $products = Products::orderBy('name')->get();
        $currentInventory = [
            'total_products' => $products->count(),
            'total_quantity' => $products->sum('quantity'),
            'total_value' => $products->sum(function ($p) {
                return $p->quantity * $p->price;
            }),
            'low_stock_count' => $products->where('quantity', '<=', 5)->count(),
        ];

        // Get sales within date range
        $sales = Sale::with(['product:id,name,sku'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $salesSummary = [
            'total_transactions' => $sales->count(),
            'total_items_sold' => $sales->sum('quantity_sold'),
            'total_revenue' => $sales->sum('total_amount'),
            'by_product' => $sales->groupBy('product_id')->map(function ($group) {
                $first = $group->first();
                return [
                    'product_name' => $first->product->name ?? 'Unknown Product',
                    'product_sku' => $first->product->sku ?? 'N/A',
                    'transactions' => $group->count(),
                    'quantity_sold' => $group->sum('quantity_sold'),
                    'revenue' => $group->sum('total_amount'),
                ];
            })->values()->sortByDesc('revenue')->values(),
        ];

        // Get restocks within date range
        $restocks = Restock::with(['product:id,name,sku'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $restockSummary = [
            'total_transactions' => $restocks->count(),
            'total_items_restocked' => $restocks->sum('quantity_added'),
            'total_cost' => $restocks->sum('total_cost'),
            'by_product' => $restocks->groupBy('product_id')->map(function ($group) {
                $first = $group->first();
                return [
                    'product_name' => $first->product->name ?? 'Unknown Product',
                    'product_sku' => $first->product->sku ?? 'N/A',
                    'transactions' => $group->count(),
                    'quantity_added' => $group->sum('quantity_added'),
                    'cost' => $group->sum('total_cost'),
                ];
            })->values()->sortByDesc('cost')->values(),
        ];

        // Get activity summary within date range
        $activities = ActivityLog::whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $activitySummary = [
            'total_activities' => $activities->count(),
            'by_type' => $activities->groupBy('type')->map(function ($group, $type) {
                return [
                    'type' => $type,
                    'count' => $group->count(),
                ];
            })->values(),
        ];

        // Try to get start-of-period summary
        $startSummary = null;
        $summaryMonth = MonthlySummary::where('year', $startDate->year)
            ->where('month', $startDate->month)
            ->first();

        if ($summaryMonth) {
            $startSummary = [
                'total_products' => $summaryMonth->total_products,
                'total_quantity' => $summaryMonth->total_quantity,
                'total_value' => $summaryMonth->total_value,
                'recorded_at' => $summaryMonth->updated_at,
            ];
        }

        // Calculate inventory products detail
        $productDetails = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'type' => $p->type,
                'quantity' => $p->quantity,
                'price' => $p->price,
                'value' => $p->quantity * $p->price,
                'low_stock' => $p->quantity <= 5,
            ];
        })->values();

        // Build the report
        $report = [
            'report_info' => [
                'title' => 'Inventory Summary Report',
                'generated_at' => Carbon::now()->toIso8601String(),
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                    'start_formatted' => $startDate->format('M d, Y'),
                    'end_formatted' => $endDate->format('M d, Y'),
                    'days' => $startDate->diffInDays($endDate) + 1,
                ],
            ],
            'summary_comparison' => [
                'period_start' => $startSummary,
                'current' => $currentInventory,
                'changes' => $startSummary ? [
                    'product_count' => $currentInventory['total_products'] - $startSummary['total_products'],
                    'quantity' => $currentInventory['total_quantity'] - $startSummary['total_quantity'],
                    'value' => $currentInventory['total_value'] - $startSummary['total_value'],
                ] : null,
            ],
            'current_inventory' => $currentInventory,
            'sales_summary' => $salesSummary,
            'restock_summary' => $restockSummary,
            'activity_summary' => $activitySummary,
            'net_inventory_flow' => [
                'items_in' => $restockSummary['total_items_restocked'],
                'items_out' => $salesSummary['total_items_sold'],
                'net_flow' => $restockSummary['total_items_restocked'] - $salesSummary['total_items_sold'],
                'revenue' => $salesSummary['total_revenue'],
                'restock_cost' => $restockSummary['total_cost'],
                'gross_profit' => $salesSummary['total_revenue'] - $restockSummary['total_cost'],
            ],
            'product_details' => $productDetails,
        ];

        return response()->json($report);
    }

    /**
     * Get available months for summaries (for dropdown).
     */
    public function availableMonths()
    {
        $summaries = MonthlySummary::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'year' => $s->year,
                    'month' => $s->month,
                    'label' => $s->period,
                ];
            });

        return response()->json($summaries);
    }

    /**
     * Get all monthly summaries.
     */
    public function getMonthlySummaries(MonthlySummaryService $service)
    {
        return response()->json($service->getAllSummaries());
    }

    /**
     * Get a specific monthly summary.
     */
    public function getMonthlySummary(MonthlySummaryService $service, int $year, int $month)
    {
        $summary = $service->getSummaryForMonth($year, $month);

        if (!$summary) {
            return response()->json(['message' => 'Summary not found for this period'], 404);
        }

        return response()->json($summary);
    }

    /**
     * Check if auto-calculation is needed (start of month detection).
     * Returns status and calculates if needed.
     */
    public function checkAutoCalculate(MonthlySummaryService $service)
    {
        $isStartOfMonth = $service->isStartOfMonth();
        $isPreviousCalculated = $service->isPreviousMonthCalculated();
        $previousMonth = Carbon::now()->subMonth();

        $response = [
            'is_start_of_month' => $isStartOfMonth,
            'current_day' => Carbon::now()->day,
            'previous_month' => [
                'year' => $previousMonth->year,
                'month' => $previousMonth->month,
                'name' => $previousMonth->format('F Y'),
            ],
            'previous_month_calculated' => $isPreviousCalculated,
            'needs_calculation' => $isStartOfMonth && !$isPreviousCalculated,
            'auto_calculated' => false,
            'summary' => null,
        ];

        // Auto-calculate if needed
        if ($response['needs_calculation']) {
            $summary = $service->autoCalculateIfNeeded();
            if ($summary) {
                $response['auto_calculated'] = true;
                $response['summary'] = $summary->toApiResponse();
                $response['previous_month_calculated'] = true;
                $response['needs_calculation'] = false;
            }
        }

        return response()->json($response);
    }

    /**
     * Manually calculate monthly summary for a specific month.
     */
    public function calculateMonthlySummary(Request $request, MonthlySummaryService $service)
    {
        $year = $request->input('year', Carbon::now()->subMonth()->year);
        $month = $request->input('month', Carbon::now()->subMonth()->month);
        $userId = $request->input('user_id');

        $summary = $service->calculateForMonth((int)$year, (int)$month, $userId);

        return response()->json([
            'message' => 'Monthly summary calculated successfully',
            'summary' => $summary->toApiResponse(),
        ]);
    }
}
