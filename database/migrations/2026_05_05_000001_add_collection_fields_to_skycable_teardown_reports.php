<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skycable_teardown_reports', function (Blueprint $table) {
            $table->integer('nodes_collected')->default(0)->after('actual_cable');
            $table->integer('amplifiers_collected')->default(0)->after('nodes_collected');
            $table->integer('extenders_collected')->default(0)->after('amplifiers_collected');
            $table->integer('tsc_collected')->default(0)->after('extenders_collected');
        });
    }

    public function down(): void
    {
        Schema::table('skycable_teardown_reports', function (Blueprint $table) {
            $table->dropColumn(['nodes_collected', 'amplifiers_collected', 'extenders_collected', 'tsc_collected']);
        });
    }
};
