<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skycable_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('skycable_areas')->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('barangay_code', 20)->nullable();
            $table->foreign('barangay_code')->references('code')->on('psgc_barangays')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skycable_sites');
    }
};
