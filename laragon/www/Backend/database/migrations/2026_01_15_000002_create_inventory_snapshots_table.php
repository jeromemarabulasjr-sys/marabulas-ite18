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
        Schema::create('inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month'); // 1-12

            // Start of month metrics
            $table->integer('start_product_count')->default(0);
            $table->integer('start_total_quantity')->default(0);
            $table->decimal('start_total_value', 15, 2)->default(0);

            // End of month metrics
            $table->integer('end_product_count')->nullable();
            $table->integer('end_total_quantity')->nullable();
            $table->decimal('end_total_value', 15, 2)->nullable();

            // Status flags
            $table->boolean('month_completed')->default(false);
            $table->timestamp('start_recorded_at')->nullable();
            $table->timestamp('end_recorded_at')->nullable();

            $table->timestamps();

            // Ensure only one record per month
            $table->unique(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_snapshots');
    }
};
