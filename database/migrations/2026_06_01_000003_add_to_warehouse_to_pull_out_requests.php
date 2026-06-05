<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pull_out_requests', function (Blueprint $table) {
            $table->foreignId('to_warehouse_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('warehouses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pull_out_requests', function (Blueprint $table) {
            $table->dropForeign(['to_warehouse_id']);
            $table->dropColumn('to_warehouse_id');
        });
    }
};
