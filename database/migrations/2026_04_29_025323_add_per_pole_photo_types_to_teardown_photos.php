<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE skycable_teardown_photos
            MODIFY COLUMN photo_type ENUM(
                'before','after','pole_tag','bunching','supporting',
                'from_before','from_after','from_pole_tag',
                'to_before','to_after','to_pole_tag'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE skycable_teardown_photos
            MODIFY COLUMN photo_type ENUM(
                'before','after','pole_tag','bunching','supporting'
            ) NOT NULL
        ");
    }
};
