<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psgc_regions', function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('name');
        });

        Schema::create('psgc_provinces', function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('name');
            $table->string('region_code', 20);
            $table->foreign('region_code')->references('code')->on('psgc_regions');
        });

        Schema::create('psgc_cities', function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('name');
            $table->string('province_code', 20)->nullable();
            $table->foreign('province_code')->references('code')->on('psgc_provinces');
        });

        Schema::create('psgc_barangays', function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('name');
            $table->string('city_code', 20);
            $table->foreign('city_code')->references('code')->on('psgc_cities');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psgc_barangays');
        Schema::dropIfExists('psgc_cities');
        Schema::dropIfExists('psgc_provinces');
        Schema::dropIfExists('psgc_regions');
    }
};
