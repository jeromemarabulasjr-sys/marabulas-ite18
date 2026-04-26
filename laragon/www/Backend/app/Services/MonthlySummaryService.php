<?php

namespace App\Services;

use App\Models\MonthlySummary;
use App\Models\Products;
use App\Models\Sale;
use App\Models\Restock;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for calculating and storing monthly inventory summaries.
 * Automatically calculates in/out transactions from sales and restocks tables.
 */
class MonthlySummaryService
{
    /**
     * Calculate and store the summary for the previous month.
     * This is typically called at the start of a new month.
     * Uses the system/OS timezone for accurate date determination.
     *
     * @param int|null $userId User ID who triggered the calculation (null for automated)
     * @return MonthlySummary|null
     */
    public function calculatePreviousMonth(?int $userId = null): ?MonthlySummary
    {
        $timezone = config('app.timezone', date_default_timezone_get());
        $previousMonth = Carbon::now($timezone)->subMonth();
        return $this->calculateForMonth($previousMonth->year, $previousMonth->month, $userId);
    }

    /**
     * Calculate and store the summary for a specific month.
     * Uses the system/OS timezone for date boundaries:
     * - Start: 12:00 AM (00:00:00) on the 1st day of the month
     * - End: 11:59:59 PM on the last day of the month
     *
     * @param int $year
     * @param int $month
     * @param int|null $userId
     * @return MonthlySummary
     */
    public function calculateForMonth(int $year, int $month, ?int $userId = null): MonthlySummary
    {
        // Use system timezone for accurate local time boundaries
        $timezone = config('app.timezone', date_default_timezone_get());

        // Start: 12:00 AM (00:00:00) on the 1st day of the month
        $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);

        // End: 11:59:59 PM on the last day of the month
        $endDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)
            ->endOfMonth()
            ->setTime(23, 59, 59);

        Log::info("Calculating monthly summary for {$startDate->format('F Y')}", [
            'timezone' => $timezone,
            'start' => $startDate->toDateTimeString(),
            'end' => $endDate->toDateTimeString(),
        ]);

        // Get or create the summary record
        $summary = MonthlySummary::getOrCreateForMonth($year, $month);

        // Calculate current inventory state (as of end of month or now)
        $inventoryData = $this->calculateInventoryState();

        // Calculate sales for the month (OUT transactions)
        $salesData = $this->calculateSalesForPeriod($startDate, $endDate);

        // Calculate restocks for the month (IN transactions)
        $restockData = $this->calculateRestocksForPeriod($startDate, $endDate);

        // Calculate activity count
        $activityCount = $this->calculateActivityCount($startDate, $endDate);

        // Calculate net flow and profit
        $netItemFlow = $restockData['total_items_added'] - $salesData['total_items_sold'];
        $grossProfit = $salesData['total_revenue'] - $restockData['total_cost'];

        // Update the summary
        $summary->update([
            'recorded_by' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_products' => $inventoryData['total_products'],
            'total_quantity' => $inventoryData['total_quantity'],
            'total_value' => $inventoryData['total_value'],
            'low_stock_count' => $inventoryData['low_stock_count'],
            'sales_transactions' => $salesData['total_transactions'],
            'sales_items_sold' => $salesData['total_items_sold'],
            'sales_revenue' => $salesData['total_revenue'],
            'restock_transactions' => $restockData['total_transactions'],
            'restock_items_added' => $restockData['total_items_added'],
            'restock_cost' => $restockData['total_cost'],
            'net_item_flow' => $netItemFlow,
            'gross_profit' => $grossProfit,
            'total_activities' => $activityCount,
        ]);

        Log::info("Monthly summary calculated successfully for {$startDate->format('F Y')}", [
            'sales_transactions' => $salesData['total_transactions'],
            'restock_transactions' => $restockData['total_transactions'],
            'net_item_flow' => $netItemFlow,
            'gross_profit' => $grossProfit,
        ]);

        return $summary->fresh();
    }

    /**
     * Check if it's the start of the month (1st day) based on local timezone.
     *
     * @return bool
     */
    public function isStartOfMonth(): bool
    {
        $timezone = config('app.timezone', date_default_timezone_get());
        return Carbon::now($timezone)->day === 1;
    }

    /**
     * Check if the previous month's summary has been calculated.
     *
     * @return bool
     */
    public function isPreviousMonthCalculated(): bool
    {
        $timezone = config('app.timezone', date_default_timezone_get());
        $previousMonth = Carbon::now($timezone)->subMonth();
        $summary = MonthlySummary::getForMonth($previousMonth->year, $previousMonth->month);

        // Consider it calculated if it exists and has transaction data
        return $summary && ($summary->sales_transactions > 0 || $summary->restock_transactions > 0 || $summary->total_activities > 0);
    }

    /**
     * Auto-calculate if it's the start of the month and not yet calculated.
     *
     * @return MonthlySummary|null Returns the summary if calculated, null otherwise
     */
    public function autoCalculateIfNeeded(): ?MonthlySummary
    {
        if ($this->isStartOfMonth() && !$this->isPreviousMonthCalculated()) {
            Log::info("Auto-calculating monthly summary for previous month (start of month detected)");
            return $this->calculatePreviousMonth();
        }

        return null;
    }

    /**
     * Calculate current inventory state.
     */
    private function calculateInventoryState(): array
    {
        $products = Products::all();

        return [
            'total_products' => $products->count(),
            'total_quantity' => $products->sum('quantity'),
            'total_value' => $products->sum(function ($p) {
                return $p->quantity * $p->price;
            }),
            'low_stock_count' => $products->where('quantity', '<=', 5)->count(),
        ];
    }

    /**
     * Calculate sales summary for a period (OUT transactions).
     */
    private function calculateSalesForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total_transactions' => $sales->count(),
            'total_items_sold' => $sales->sum('quantity_sold'),
            'total_revenue' => $sales->sum('total_amount'),
        ];
    }

    /**
     * Calculate restocks summary for a period (IN transactions).
     */
    private function calculateRestocksForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $restocks = Restock::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total_transactions' => $restocks->count(),
            'total_items_added' => $restocks->sum('quantity_added'),
            'total_cost' => $restocks->sum('total_cost'),
        ];
    }

    /**
     * Calculate activity count for a period.
     */
    private function calculateActivityCount(Carbon $startDate, Carbon $endDate): int
    {
        return ActivityLog::whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Get summary data formatted for frontend.
     */
    public function getSummaryForMonth(int $year, int $month): ?array
    {
        $summary = MonthlySummary::getForMonth($year, $month);
        return $summary ? $summary->toApiResponse() : null;
    }

    /**
     * Get all available summaries.
     */
    public function getAllSummaries(): array
    {
        return MonthlySummary::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(fn($s) => $s->toApiResponse())
            ->toArray();
    }
}
