<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skycable_teardown_reports', function (Blueprint $table) {
            $table->integer('powersupply_collected')->default(0)->after('tsc_collected');
            $table->integer('ps_housing_collected')->default(0)->after('powersupply_collected');
        });
    }

    public function down(): void
    {
        Schema::table('skycable_teardown_reports', function (Blueprint $table) {
            $table->dropColumn(['powersupply_collected', 'ps_housing_collected']);
        });
    }
};
