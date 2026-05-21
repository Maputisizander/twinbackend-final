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
        Schema::table('skycable_nodes', function (Blueprint $table) {
            $table->string('region')->nullable()->after('barangay_code');
            $table->string('province')->nullable()->after('region');
            $table->string('city')->nullable()->after('province');
            $table->string('barangay_name')->nullable()->after('city');
            // make barangay_code optional — PSGC lookup tables are not populated
            $table->string('barangay_code', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('skycable_nodes', function (Blueprint $table) {
            $table->dropColumn(['region', 'province', 'city', 'barangay_name']);
        });
    }
};
