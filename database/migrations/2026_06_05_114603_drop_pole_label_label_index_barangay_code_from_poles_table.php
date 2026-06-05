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
            $table->dropForeign(['barangay_code']);
            $table->dropColumn(['pole_label', 'label_index', 'barangay_code']);
        });
    }

    public function down(): void
    {
        Schema::table('poles', function (Blueprint $table) {
            $table->string('pole_label', 100)->nullable()->after('pole_code');
            $table->unsignedSmallInteger('label_index')->nullable()->after('pole_label');
            $table->string('barangay_code', 20)->nullable()->after('label_index');
        });
    }
};
