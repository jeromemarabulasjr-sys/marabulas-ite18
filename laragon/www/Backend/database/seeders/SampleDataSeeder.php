<?php

namespace Database\Seeders;

use App\Models\Products;
use App\Models\Sale;
use App\Models\Restock;
use App\Models\ActivityLog;
use App\Models\ActivityType;
use App\Models\MonthlySummary;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    /**
     * Seed sample data for testing the report functionality.
     */
    public function run(): void
    {
        $this->command->info('Creating sample data for report testing...');

        // Create admin user if not exists
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@inventory.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Create sample products
        $products = $this->createProducts();
        $this->command->info('Created ' . count($products) . ' sample products.');

        // Create sales records for the past 2 months
        $this->createSales($products, $adminUser);
        $this->command->info('Created sample sales records.');

        // Create restock records for the past 2 months
        $this->createRestocks($products, $adminUser);
        $this->command->info('Created sample restock records.');

        // Note: Not creating sample activity logs - they're created automatically
        // by the ProductController when sales/restocks are recorded via the UI

        // Create monthly summaries
        $this->createMonthlySummaries();
        $this->command->info('Created monthly summaries.');

        $this->command->info('Sample data seeding complete!');
    }

    private function createProducts(): array
    {
        $productData = [
            // Running Shoes
            ['name' => 'Endure 2 Running Shoes - Black', 'sku' => 'WB-RUN-001', 'type' => 'Running Shoes', 'quantity' => 45, 'price' => 3499.00],
            ['name' => 'Endure 2 Running Shoes - Blue', 'sku' => 'WB-RUN-002', 'type' => 'Running Shoes', 'quantity' => 38, 'price' => 3499.00],
            ['name' => 'Tracker Pro Running Shoes', 'sku' => 'WB-RUN-003', 'type' => 'Running Shoes', 'quantity' => 52, 'price' => 4299.00],
            ['name' => 'Marathon Elite V2', 'sku' => 'WB-RUN-004', 'type' => 'Running Shoes', 'quantity' => 3, 'price' => 4999.00], // Low stock

            // Basketball Shoes
            ['name' => 'Court Master Pro - White', 'sku' => 'WB-BBL-001', 'type' => 'Basketball Shoes', 'quantity' => 30, 'price' => 4799.00],
            ['name' => 'Court Master Pro - Black/Red', 'sku' => 'WB-BBL-002', 'type' => 'Basketball Shoes', 'quantity' => 25, 'price' => 4799.00],
            ['name' => 'Dunk High Performance', 'sku' => 'WB-BBL-003', 'type' => 'Basketball Shoes', 'quantity' => 4, 'price' => 5299.00], // Low stock

            // Training Shoes
            ['name' => 'Trainer X Cross-Training', 'sku' => 'WB-TRN-001', 'type' => 'Training Shoes', 'quantity' => 60, 'price' => 2999.00],
            ['name' => 'Gym Force Training Shoes', 'sku' => 'WB-TRN-002', 'type' => 'Training Shoes', 'quantity' => 48, 'price' => 2799.00],
            ['name' => 'Flex Fit Workout Shoes', 'sku' => 'WB-TRN-003', 'type' => 'Training Shoes', 'quantity' => 5, 'price' => 2599.00], // Low stock

            // Casual/Lifestyle
            ['name' => 'Urban Walker Casual - Gray', 'sku' => 'WB-CSL-001', 'type' => 'Casual Shoes', 'quantity' => 75, 'price' => 2299.00],
            ['name' => 'Urban Walker Casual - Navy', 'sku' => 'WB-CSL-002', 'type' => 'Casual Shoes', 'quantity' => 68, 'price' => 2299.00],
            ['name' => 'Street Style Sneakers', 'sku' => 'WB-CSL-003', 'type' => 'Casual Shoes', 'quantity' => 55, 'price' => 2499.00],

            // Sandals & Slides
            ['name' => 'Sport Slides - Black', 'sku' => 'WB-SND-001', 'type' => 'Sandals', 'quantity' => 120, 'price' => 1199.00],
            ['name' => 'Recovery Sandals', 'sku' => 'WB-SND-002', 'type' => 'Sandals', 'quantity' => 2, 'price' => 1499.00], // Low stock

            // Apparel
            ['name' => 'Performance Dry-Fit Shirt - M', 'sku' => 'WB-APP-001', 'type' => 'Apparel', 'quantity' => 100, 'price' => 1299.00],
            ['name' => 'Performance Dry-Fit Shirt - L', 'sku' => 'WB-APP-002', 'type' => 'Apparel', 'quantity' => 85, 'price' => 1299.00],
            ['name' => 'Running Shorts', 'sku' => 'WB-APP-003', 'type' => 'Apparel', 'quantity' => 70, 'price' => 999.00],
            ['name' => 'Athletic Socks (3-Pack)', 'sku' => 'WB-ACC-001', 'type' => 'Accessories', 'quantity' => 200, 'price' => 599.00],
            ['name' => 'Sports Bag - Medium', 'sku' => 'WB-ACC-002', 'type' => 'Accessories', 'quantity' => 40, 'price' => 1899.00],
        ];

        $products = [];
        foreach ($productData as $data) {
            $products[] = Products::firstOrCreate(
                ['sku' => $data['sku']],
                $data
            );
        }

        return $products;
    }

    private function createSales(array $products, User $user): void
    {
        $now = Carbon::now();
        $saleTypeId = ActivityType::where('type_key', 'sale')->first()?->id;

        // Generate sales for last 60 days - MORE sales to generate profit
        for ($day = 60; $day >= 0; $day--) {
            $date = $now->copy()->subDays($day);

            // Random number of sales per day (8-15) - increased frequency for realistic revenue
            $salesCount = rand(8, 15);

            for ($i = 0; $i < $salesCount; $i++) {
                $product = $products[array_rand($products)];
                $quantity = rand(2, 5); // Realistic quantity per sale
                $unitPrice = $product->price;
                $previousStock = $product->quantity + rand(20, 50); // Simulate having stock before sale
                $newStock = max(0, $previousStock - $quantity);
                $saleTimestamp = $date->copy()->addHours(rand(8, 18))->addMinutes(rand(0, 59));

                Sale::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'username' => $user->name,
                    'quantity_sold' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $quantity * $unitPrice,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'created_at' => $saleTimestamp,
                    'updated_at' => $saleTimestamp,
                ]);

                // Create corresponding activity log
                ActivityLog::create([
                    'type' => 'sale',
                    'activity_type_id' => $saleTypeId,
                    'user_id' => $user->id,
                    'username' => $user->name,
                    'details' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'changes' => ['quantity'],
                        'quantity' => $quantity,
                        'previousStock' => $previousStock,
                        'newStock' => $newStock,
                    ],
                    'created_at' => $saleTimestamp,
                    'updated_at' => $saleTimestamp,
                ]);
            }
        }
    }

    private function createRestocks(array $products, User $user): void
    {
        $now = Carbon::now();
        $restockTypeId = ActivityType::where('type_key', 'restock')->first()?->id;

        // Generate restocks - less frequent with realistic cost margins
        for ($day = 60; $day >= 0; $day -= rand(4, 8)) {
            $date = $now->copy()->subDays($day);

            // Fewer restocks (1-3 per period)
            $restockCount = rand(1, 3);

            for ($i = 0; $i < $restockCount; $i++) {
                $product = $products[array_rand($products)];
                $quantity = rand(8, 25); // Realistic restock quantity
                $unitCost = $product->price * 0.5; // 50% cost margin (realistic for retail)
                $previousStock = max(0, $product->quantity - rand(5, 20)); // Simulate stock before restock
                $newStock = $previousStock + $quantity;
                $restockTimestamp = $date->copy()->addHours(rand(8, 18))->addMinutes(rand(0, 59));

                Restock::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'username' => $user->name,
                    'quantity_added' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $quantity * $unitCost,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'created_at' => $restockTimestamp,
                    'updated_at' => $restockTimestamp,
                ]);

                // Create corresponding activity log
                ActivityLog::create([
                    'type' => 'restock',
                    'activity_type_id' => $restockTypeId,
                    'user_id' => $user->id,
                    'username' => $user->name,
                    'details' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'changes' => ['quantity'],
                        'quantity' => $quantity,
                        'previousStock' => $previousStock,
                        'newStock' => $newStock,
                    ],
                    'created_at' => $restockTimestamp,
                    'updated_at' => $restockTimestamp,
                ]);
            }
        }
    }

    private function createActivityLogs(User $user): void
    {
        $now = Carbon::now();
        $activityTypes = [
            'product_created',
            'product_updated',
            'product_deleted',
            'sale_recorded',
            'restock_recorded',
            'login',
            'export_data',
        ];

        // Generate activity logs for last 30 days
        for ($day = 30; $day >= 0; $day--) {
            $date = $now->copy()->subDays($day);

            // Random number of activities per day (2-8)
            $activityCount = rand(2, 8);

            for ($i = 0; $i < $activityCount; $i++) {
                $type = $activityTypes[array_rand($activityTypes)];

                ActivityLog::create([
                    'type' => $type,
                    'username' => $user->name,
                    'user_id' => $user->id,
                    'details' => [
                        'action' => $type,
                        'timestamp' => $date->toIso8601String(),
                        'sample' => true,
                    ],
                    'created_at' => $date->copy()->addHours(rand(8, 18))->addMinutes(rand(0, 59)),
                    'updated_at' => $date->copy()->addHours(rand(8, 18))->addMinutes(rand(0, 59)),
                ]);
            }
        }
    }

    private function createMonthlySummaries(): void
    {
        $now = Carbon::now();
        $products = Products::all();
        $adminUser = User::where('role', 'admin')->first();

        $totalValue = $products->sum(function ($p) {
            return $p->quantity * $p->price;
        });

        // Create summary for current month
        MonthlySummary::firstOrCreate(
            ['year' => $now->year, 'month' => $now->month],
            [
                'recorded_by' => $adminUser?->id,
                'total_products' => $products->count(),
                'total_quantity' => $products->sum('quantity'),
                'total_value' => $totalValue,
                'low_stock_count' => $products->where('quantity', '<=', 5)->count(),
                'sales_transactions' => Sale::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                'sales_items_sold' => Sale::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('quantity_sold'),
                'sales_revenue' => Sale::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_amount'),
                'restock_transactions' => Restock::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                'restock_items_added' => Restock::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('quantity_added'),
                'restock_cost' => Restock::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_cost'),
                'net_item_flow' => Restock::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('quantity_added') - Sale::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('quantity_sold'),
                'gross_profit' => Sale::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_amount') - Restock::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_cost'),
                'total_activities' => ActivityLog::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            ]
        );

        // Create summary for last month
        $lastMonth = $now->copy()->subMonth();
        MonthlySummary::firstOrCreate(
            ['year' => $lastMonth->year, 'month' => $lastMonth->month],
            [
                'recorded_by' => $adminUser?->id,
                'total_products' => $products->count() - 2,
                'total_quantity' => $products->sum('quantity') - 50,
                'total_value' => $totalValue - 1500,
                'low_stock_count' => 3,
                'sales_transactions' => Sale::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count(),
                'sales_items_sold' => Sale::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->sum('quantity_sold'),
                'sales_revenue' => Sale::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->sum('total_amount'),
                'restock_transactions' => Restock::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count(),
                'restock_items_added' => Restock::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->sum('quantity_added'),
                'restock_cost' => Restock::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->sum('total_cost'),
                'net_item_flow' => Restock::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->sum('quantity_added') - Sale::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->sum('quantity_sold'),
                'gross_profit' => Sale::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->sum('total_amount') - Restock::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->sum('total_cost'),
                'total_activities' => ActivityLog::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count(),
            ]
        );
    }
}
