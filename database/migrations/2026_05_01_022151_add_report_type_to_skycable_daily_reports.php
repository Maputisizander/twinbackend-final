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
        Schema::table('skycable_daily_reports', function (Blueprint $table) {
            $table->enum('report_type', ['full_report', 'pole_report'])
                  ->default('full_report')
                  ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('skycable_daily_reports', function (Blueprint $table) {
            $table->dropColumn('report_type');
        });
    }
};
