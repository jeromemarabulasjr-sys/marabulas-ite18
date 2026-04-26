<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Products;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing products
        DB::table('products')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $products = [
            [
                'name' => 'Wireless Mouse',
                'sku' => 'MOU-001',
                'type' => 'Peripherals',
                'price' => 899.00,
                'quantity' => 45,
            ],
            [
                'name' => 'USB-C Cable 2m',
                'sku' => 'CAB-UC-2',
                'type' => 'Cables',
                'price' => 299.00,
                'quantity' => 120,
            ],
            [
                'name' => 'AA Batteries (4-pack)',
                'sku' => 'BAT-AA-4',
                'type' => 'Power',
                'price' => 199.00,
                'quantity' => 200,
            ],
            [
                'name' => 'Laptop Stand',
                'sku' => 'ACC-LS-01',
                'type' => 'Accessories',
                'price' => 1499.00,
                'quantity' => 15,
            ],
            [
                'name' => 'Mechanical Keyboard',
                'sku' => 'KEY-MECH-01',
                'type' => 'Peripherals',
                'price' => 3499.00,
                'quantity' => 8,
            ],
            [
                'name' => 'HDMI Cable 1.5m',
                'sku' => 'CAB-HDMI-15',
                'type' => 'Cables',
                'price' => 449.00,
                'quantity' => 3,
            ],
            [
                'name' => 'Webcam HD 1080p',
                'sku' => 'CAM-HD-01',
                'type' => 'Peripherals',
                'price' => 1899.00,
                'quantity' => 22,
            ],
            [
                'name' => 'External SSD 500GB',
                'sku' => 'SSD-EXT-500',
                'type' => 'Storage',
                'price' => 2799.00,
                'quantity' => 12,
            ],
            [
                'name' => 'Phone Charger 20W',
                'sku' => 'CHG-20W-01',
                'type' => 'Power',
                'price' => 599.00,
                'quantity' => 85,
            ],
            [
                'name' => 'Desk Lamp LED',
                'sku' => 'LAMP-LED-01',
                'type' => 'Accessories',
                'price' => 1299.00,
                'quantity' => 2,
            ],
        ];

        foreach ($products as $product) {
            Products::create($product);
        }
    }
}
