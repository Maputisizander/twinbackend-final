<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change from ENUM to VARCHAR so any action string is accepted
        // without requiring a new migration every time a new action is added.
        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(64) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('created','updated','deleted','split') NOT NULL");
    }
};
