<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add indexes for frequently queried columns to improve query performance.
     */
    public function up(): void
    {
        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            // Index for SKU lookups and uniqueness checks
            if (!$this->hasIndex('products', 'products_sku_index')) {
                $table->index('sku');
            }
            // Index for type filtering
            if (!$this->hasIndex('products', 'products_type_index')) {
                $table->index('type');
            }
            // Index for low stock queries
            if (!$this->hasIndex('products', 'products_quantity_index')) {
                $table->index('quantity');
            }
        });

        // Sales table indexes
        Schema::table('sales', function (Blueprint $table) {
            // Index for product sales lookups
            if (!$this->hasIndex('sales', 'sales_product_id_index')) {
                $table->index('product_id');
            }
            // Index for date-range queries (statistics)
            if (!$this->hasIndex('sales', 'sales_created_at_index')) {
                $table->index('created_at');
            }
            // Composite index for statistics queries
            if (!$this->hasIndex('sales', 'sales_created_product_index')) {
                $table->index(['created_at', 'product_id'], 'sales_created_product_index');
            }
        });

        // Restocks table indexes
        Schema::table('restocks', function (Blueprint $table) {
            // Index for product restock lookups
            if (!$this->hasIndex('restocks', 'restocks_product_id_index')) {
                $table->index('product_id');
            }
            // Index for date-range queries (statistics)
            if (!$this->hasIndex('restocks', 'restocks_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Activity logs table indexes
        Schema::table('activity_logs', function (Blueprint $table) {
            // Index for type filtering
            if (!$this->hasIndex('activity_logs', 'activity_logs_type_index')) {
                $table->index('type');
            }
            // Index for ordering by date
            if (!$this->hasIndex('activity_logs', 'activity_logs_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['sku']);
            $table->dropIndex(['type']);
            $table->dropIndex(['quantity']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex('sales_created_product_index');
        });

        Schema::table('restocks', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }
};
