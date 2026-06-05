<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand enum to include 'main' if not already present
        DB::statement("ALTER TABLE warehouses MODIFY COLUMN type ENUM('subcon','staging','main') NOT NULL DEFAULT 'subcon'");

        // Set TELCOVANTAGE DEVELOPERS Warehouse as the main receiving warehouse
        DB::table('warehouses')
            ->where('name', 'like', '%TELCOVANTAGE%')
            ->update(['type' => 'main']);
    }

    public function down(): void
    {
        DB::table('warehouses')
            ->where('name', 'like', '%TELCOVANTAGE%')
            ->update(['type' => 'subcon']);
    }
};
