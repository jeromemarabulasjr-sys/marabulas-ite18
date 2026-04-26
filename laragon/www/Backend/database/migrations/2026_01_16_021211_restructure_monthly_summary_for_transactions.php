<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('monthly_summary');

        Schema::create('monthly_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            // Current inventory snapshot
            $table->integer('total_products')->default(0);
            $table->integer('total_quantity')->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->integer('low_stock_count')->default(0);

            // Sales (OUT transactions)
            $table->integer('sales_transactions')->default(0);
            $table->integer('sales_items_sold')->default(0);
            $table->decimal('sales_revenue', 15, 2)->default(0);

            // Restocks (IN transactions)
            $table->integer('restock_transactions')->default(0);
            $table->integer('restock_items_added')->default(0);
            $table->decimal('restock_cost', 15, 2)->default(0);

            // Net flow calculations
            $table->integer('net_item_flow')->default(0); // restock_items_added - sales_items_sold
            $table->decimal('gross_profit', 15, 2)->default(0); // sales_revenue - restock_cost

            // Activity summary
            $table->integer('total_activities')->default(0);

            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_summary');

        Schema::create('monthly_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->integer('start_product_count')->default(0);
            $table->integer('start_total_quantity')->default(0);
            $table->decimal('start_total_value', 15, 2)->default(0);
            $table->integer('end_product_count')->nullable();
            $table->integer('end_total_quantity')->nullable();
            $table->decimal('end_total_value', 15, 2)->nullable();
            $table->boolean('month_completed')->default(false);
            $table->timestamp('start_recorded_at')->nullable();
            $table->timestamp('end_recorded_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }
};
