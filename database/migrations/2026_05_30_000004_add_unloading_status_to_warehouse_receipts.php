<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE warehouse_receipts MODIFY COLUMN status ENUM('pending','arrived','unloading','approved','rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('warehouse_receipts')->where('status', 'unloading')->update(['status' => 'arrived']);
        DB::statement("ALTER TABLE warehouse_receipts MODIFY COLUMN status ENUM('pending','arrived','approved','rejected') NOT NULL DEFAULT 'pending'");
    }
};
