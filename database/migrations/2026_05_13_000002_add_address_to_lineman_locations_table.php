<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lineman_locations', function (Blueprint $table) {
            $table->string('barangay')->nullable()->after('accuracy');
            $table->string('city')->nullable()->after('barangay');
            $table->string('province')->nullable()->after('city');
            $table->string('region_name')->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('lineman_locations', function (Blueprint $table) {
            $table->dropColumn(['barangay', 'city', 'province', 'region_name']);
        });
    }
};
