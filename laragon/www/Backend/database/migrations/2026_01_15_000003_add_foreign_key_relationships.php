<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds foreign key relationships:
     * - activity_logs.type -> activity_types.type_key
     * - sales.username -> users.name (nullable)
     * - restocks.username -> users.name (nullable)
     */
    public function up(): void
    {
        // First, ensure all existing activity_logs.type values exist in activity_types
        // Add any missing types that might exist in activity_logs
        $existingTypes = DB::table('activity_logs')
            ->select('type')
            ->distinct()
            ->pluck('type');

        $knownTypes = DB::table('activity_types')
            ->pluck('type_key');

        foreach ($existingTypes as $type) {
            if (!$knownTypes->contains($type)) {
                DB::table('activity_types')->insert([
                    'type_key' => $type,
                    'display_name' => ucfirst(str_replace('-', ' ', $type)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Add activity_type_id column to activity_logs and populate it
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('activity_type_id')->nullable()->after('type');
        });

        // Populate activity_type_id from type
        DB::statement('
            UPDATE activity_logs
            SET activity_type_id = (
                SELECT id FROM activity_types WHERE activity_types.type_key = activity_logs.type
            )
        ');

        // Add foreign key constraint
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreign('activity_type_id')
                ->references('id')
                ->on('activity_types')
                ->onDelete('set null');
        });

        // Add user_id columns to sales and restocks for proper FK relationships
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('product_id');
        });

        Schema::table('restocks', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('product_id');
        });

        // Populate user_id from username where possible
        DB::statement('
            UPDATE sales
            SET user_id = (
                SELECT id FROM users WHERE users.name = sales.username
            )
            WHERE username IS NOT NULL
        ');

        DB::statement('
            UPDATE restocks
            SET user_id = (
                SELECT id FROM users WHERE users.name = restocks.username
            )
            WHERE username IS NOT NULL
        ');

        // Add foreign key constraints for user relationships
        Schema::table('sales', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('user_id');
        });

        Schema::table('restocks', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('user_id');
        });

        // Add user_id to activity_logs as well
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('username');
        });

        DB::statement('
            UPDATE activity_logs
            SET user_id = (
                SELECT id FROM users WHERE users.name = activity_logs.username
            )
            WHERE username IS NOT NULL
        ');

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['activity_type_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['activity_type_id', 'user_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('restocks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
