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
        Schema::table('poles', function (Blueprint $table) {
            // Original DXF label e.g. "NPT", "PT", "NT" — before indexing
            $table->string('pole_label', 100)->nullable()->after('pole_code');
            // Index within the same label per node e.g. NPT-1 = 1, NPT-2 = 2, unique NPT = null
            $table->unsignedSmallInteger('label_index')->nullable()->after('pole_label');
        });
    }

    public function down(): void
    {
        Schema::table('poles', function (Blueprint $table) {
            $table->dropColumn(['pole_label', 'label_index']);
        });
    }
};
