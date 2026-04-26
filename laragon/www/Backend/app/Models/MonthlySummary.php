<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MonthlySummary - Records monthly inventory in/out transactions
 *
 * @property int $id
 * @property int $year
 * @property int $month
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property int|null $recorded_by
 * @property int $total_products
 * @property int $total_quantity
 * @property float $total_value
 * @property int $low_stock_count
 * @property int $sales_transactions
 * @property int $sales_items_sold
 * @property float $sales_revenue
 * @property int $restock_transactions
 * @property int $restock_items_added
 * @property float $restock_cost
 * @property int $net_item_flow
 * @property float $gross_profit
 * @property int $total_activities
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read string $month_name
 * @property-read string $period
 * @property-read User|null $recorder
 */
class MonthlySummary extends Model
{
    protected $table = 'monthly_summary';

    protected $fillable = [
        'year',
        'month',
        'start_date',
        'end_date',
        'recorded_by',
        'total_products',
        'total_quantity',
        'total_value',
        'low_stock_count',
        'sales_transactions',
        'sales_items_sold',
        'sales_revenue',
        'restock_transactions',
        'restock_items_added',
        'restock_cost',
        'net_item_flow',
        'gross_profit',
        'total_activities',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'recorded_by' => 'integer',
        'total_products' => 'integer',
        'total_quantity' => 'integer',
        'total_value' => 'decimal:2',
        'low_stock_count' => 'integer',
        'sales_transactions' => 'integer',
        'sales_items_sold' => 'integer',
        'sales_revenue' => 'decimal:2',
        'restock_transactions' => 'integer',
        'restock_items_added' => 'integer',
        'restock_cost' => 'decimal:2',
        'net_item_flow' => 'integer',
        'gross_profit' => 'decimal:2',
        'total_activities' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who recorded this summary.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the summary for a specific month.
     */
    public static function getForMonth(int $year, int $month): ?self
    {
        return self::where('year', $year)->where('month', $month)->first();
    }

    /**
     * Get or create summary for a specific month.
     */
    public static function getOrCreateForMonth(int $year, int $month): self
    {
        return self::firstOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'total_products' => 0,
                'total_quantity' => 0,
                'total_value' => 0,
                'low_stock_count' => 0,
                'sales_transactions' => 0,
                'sales_items_sold' => 0,
                'sales_revenue' => 0,
                'restock_transactions' => 0,
                'restock_items_added' => 0,
                'restock_cost' => 0,
                'net_item_flow' => 0,
                'gross_profit' => 0,
                'total_activities' => 0,
            ]
        );
    }

    /**
     * Record summary from inventory report data.
     */
    public function recordFromReport(array $reportData, ?int $userId = null): self
    {
        $this->update([
            'recorded_by' => $userId,
            'total_products' => $reportData['current_inventory']['total_products'] ?? 0,
            'total_quantity' => $reportData['current_inventory']['total_quantity'] ?? 0,
            'total_value' => $reportData['current_inventory']['total_value'] ?? 0,
            'low_stock_count' => $reportData['current_inventory']['low_stock_count'] ?? 0,
            'sales_transactions' => $reportData['sales_summary']['total_transactions'] ?? 0,
            'sales_items_sold' => $reportData['sales_summary']['total_items_sold'] ?? 0,
            'sales_revenue' => $reportData['sales_summary']['total_revenue'] ?? 0,
            'restock_transactions' => $reportData['restock_summary']['total_transactions'] ?? 0,
            'restock_items_added' => $reportData['restock_summary']['total_items_restocked'] ?? 0,
            'restock_cost' => $reportData['restock_summary']['total_cost'] ?? 0,
            'net_item_flow' => $reportData['net_inventory_flow']['net_flow'] ?? 0,
            'gross_profit' => $reportData['net_inventory_flow']['gross_profit'] ?? 0,
            'total_activities' => $reportData['activity_summary']['total_activities'] ?? 0,
        ]);

        return $this;
    }

    /**
     * Get month name.
     */
    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    /**
     * Get formatted period (e.g., "January 2026").
     */
    public function getPeriodAttribute(): string
    {
        return $this->month_name . ' ' . $this->year;
    }

    /**
     * Get formatted summary for API response.
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'month' => $this->month,
            'month_name' => $this->month_name,
            'period' => $this->period,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'start_date_formatted' => $this->start_date?->format('M d, Y h:i A'),
            'end_date_formatted' => $this->end_date?->format('M d, Y h:i A'),
            'recorded_by' => $this->recorder ? [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ] : null,
            'inventory' => [
                'total_products' => $this->total_products,
                'total_quantity' => $this->total_quantity,
                'total_value' => $this->total_value,
                'low_stock_count' => $this->low_stock_count,
            ],
            'sales' => [
                'total_transactions' => $this->sales_transactions,
                'total_items_sold' => $this->sales_items_sold,
                'total_revenue' => $this->sales_revenue,
            ],
            'restocks' => [
                'total_transactions' => $this->restock_transactions,
                'total_items_restocked' => $this->restock_items_added,
                'total_cost' => $this->restock_cost,
            ],
            'net_flow' => [
                'items_in' => $this->restock_items_added,
                'items_out' => $this->sales_items_sold,
                'net_flow' => $this->net_item_flow,
                'gross_profit' => $this->gross_profit,
            ],
            'total_activities' => $this->total_activities,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
