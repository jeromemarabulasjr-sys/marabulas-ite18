<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds start_date and end_date columns to track the exact date range
     * for each monthly summary period.
     */
    public function up(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->datetime('start_date')->nullable()->after('month');
            $table->datetime('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_summary', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
