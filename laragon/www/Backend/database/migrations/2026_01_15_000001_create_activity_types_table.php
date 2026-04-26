<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_key', 50)->unique();
            $table->string('display_name', 100);
            $table->timestamps();
        });

        // Seed initial activity types
        DB::table('activity_types')->insert([
            ['type_key' => 'add', 'display_name' => 'Added', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'edit', 'display_name' => 'Edited', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'update', 'display_name' => 'Updated', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'delete', 'display_name' => 'Deleted', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'remove', 'display_name' => 'Removed', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'clear', 'display_name' => 'Cleared All', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'import', 'display_name' => 'Imported', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'sale', 'display_name' => 'Sold', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'restock', 'display_name' => 'Restocked', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'credential-added', 'display_name' => 'Credential Added', 'created_at' => now(), 'updated_at' => now()],
            ['type_key' => 'credential-removed', 'display_name' => 'Credential Removed', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};
