<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skycable_poles', function (Blueprint $table) {
            $table->timestamp('date_start')->nullable()->after('sequence');
            $table->timestamp('cleared_at')->nullable()->after('date_start');
        });
    }

    public function down(): void
    {
        Schema::table('skycable_poles', function (Blueprint $table) {
            $table->dropColumn(['date_start', 'cleared_at']);
        });
    }
};
