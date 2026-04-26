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
        Schema::rename('inventory_snapshots', 'monthly_summary');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('monthly_summary', 'inventory_snapshots');
    }
};
