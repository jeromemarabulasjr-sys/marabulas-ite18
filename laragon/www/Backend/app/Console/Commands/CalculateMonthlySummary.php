<?php

namespace App\Console\Commands;

use App\Services\MonthlySummaryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Command to calculate and store monthly inventory summary.
 *
 * This command calculates the in/out transactions (sales/restocks)
 * for a specific month and stores them in the monthly_summary table.
 *
 * Usage:
 *   php artisan inventory:calculate-monthly           # Calculate previous month
 *   php artisan inventory:calculate-monthly --auto    # Only calculate if start of month
 *   php artisan inventory:calculate-monthly --month=12 --year=2025  # Specific month
 */
class CalculateMonthlySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:calculate-monthly
                            {--auto : Only calculate if it\'s the start of the month}
                            {--month= : Specific month (1-12)}
                            {--year= : Specific year}
                            {--user= : User ID who triggered the calculation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and store monthly inventory in/out transactions summary';

    /**
     * Execute the console command.
     */
    public function handle(MonthlySummaryService $service): int
    {
        // Auto mode: only calculate if it's the start of the month
        if ($this->option('auto')) {
            $this->info('Running in auto mode...');

            if (!$service->isStartOfMonth()) {
                $this->info('Not the start of the month. Skipping calculation.');
                return self::SUCCESS;
            }

            if ($service->isPreviousMonthCalculated()) {
                $this->info('Previous month summary already calculated. Skipping.');
                return self::SUCCESS;
            }

            $this->info('Start of month detected. Calculating previous month summary...');
            $summary = $service->calculatePreviousMonth($this->option('user'));

            $this->displaySummary($summary);
            return self::SUCCESS;
        }

        // Specific month/year mode
        $month = $this->option('month');
        $year = $this->option('year');

        if ($month && $year) {
            $this->info("Calculating summary for {$month}/{$year}...");
            $summary = $service->calculateForMonth((int)$year, (int)$month, $this->option('user'));
            $this->displaySummary($summary);
            return self::SUCCESS;
        }

        // Default: calculate previous month
        $previousMonth = Carbon::now()->subMonth();
        $this->info("Calculating summary for {$previousMonth->format('F Y')}...");

        $summary = $service->calculatePreviousMonth($this->option('user'));
        $this->displaySummary($summary);

        return self::SUCCESS;
    }

    /**
     * Display the calculated summary.
     */
    private function displaySummary($summary): void
    {
        $this->newLine();
        $this->info("✅ Monthly Summary Calculated Successfully!");
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Period', $summary->period],
                ['Total Products', $summary->total_products],
                ['Total Quantity', $summary->total_quantity],
                ['Total Value', '$' . number_format($summary->total_value, 2)],
                ['Low Stock Count', $summary->low_stock_count],
                ['─────────────────', '─────────────────'],
                ['Sales Transactions (OUT)', $summary->sales_transactions],
                ['Items Sold', $summary->sales_items_sold],
                ['Sales Revenue', '$' . number_format($summary->sales_revenue, 2)],
                ['─────────────────', '─────────────────'],
                ['Restock Transactions (IN)', $summary->restock_transactions],
                ['Items Added', $summary->restock_items_added],
                ['Restock Cost', '$' . number_format($summary->restock_cost, 2)],
                ['─────────────────', '─────────────────'],
                ['Net Item Flow', $summary->net_item_flow],
                ['Gross Profit', '$' . number_format($summary->gross_profit, 2)],
                ['Total Activities', $summary->total_activities],
            ]
        );
    }
}
