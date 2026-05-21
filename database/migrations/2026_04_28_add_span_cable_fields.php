<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skycable_spans', function (Blueprint $table) {
            $table->decimal('strand_length', 10, 2)->nullable()->after('length_meters');
            $table->integer('number_of_runs')->nullable()->after('strand_length');
            $table->decimal('actual_cable', 10, 2)->nullable()->after('number_of_runs');
        });

        // Add 'cancelled' to the status enum
        DB::statement("ALTER TABLE skycable_spans MODIFY COLUMN status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending'");

        // Add 'powersupply_case' to component_type enum
        DB::statement("ALTER TABLE skycable_span_components MODIFY COLUMN component_type ENUM('node','amplifier','extender','tsc','cable','powersupply','powersupply_case') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('skycable_spans', function (Blueprint $table) {
            $table->dropColumn(['strand_length', 'number_of_runs', 'actual_cable']);
        });

        DB::statement("ALTER TABLE skycable_spans MODIFY COLUMN status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE skycable_span_components MODIFY COLUMN component_type ENUM('node','amplifier','extender','tsc','cable','powersupply') NOT NULL");
    }
};
