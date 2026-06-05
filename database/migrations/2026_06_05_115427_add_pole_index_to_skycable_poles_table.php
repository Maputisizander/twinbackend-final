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
        Schema::table('skycable_poles', function (Blueprint $table) {
            $table->string('pole_index', 50)->nullable()->after('sequence');
        });
    }

    public function down(): void
    {
        Schema::table('skycable_poles', function (Blueprint $table) {
            $table->dropColumn('pole_index');
        });
    }
};
