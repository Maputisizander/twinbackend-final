<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add pending_teardown to the status enum
        DB::statement("ALTER TABLE pole_cable_slots MODIFY COLUMN status ENUM('occupied','pending_teardown','free') NOT NULL DEFAULT 'free'");

        // Make occupied_by nullable so backend-approve can clear it properly,
        // and add meralco / others values that were missing from the original enum
        DB::statement("ALTER TABLE pole_cable_slots MODIFY COLUMN occupied_by ENUM('skycable','globe','meralco','others','free') NULL DEFAULT 'free'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pole_cable_slots MODIFY COLUMN status ENUM('occupied','free') NOT NULL DEFAULT 'free'");
        DB::statement("ALTER TABLE pole_cable_slots MODIFY COLUMN occupied_by ENUM('skycable','globe','meralco','others','free') NOT NULL DEFAULT 'free'");
    }
};
