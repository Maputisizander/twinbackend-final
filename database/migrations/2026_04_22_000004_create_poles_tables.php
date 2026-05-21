<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poles', function (Blueprint $table) {
            $table->id();
            $table->string('pole_code')->unique();
            $table->string('barangay_code', 20)->nullable();
            $table->foreign('barangay_code')->references('code')->on('psgc_barangays')->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->enum('skycable_status', ['pending', 'in_progress', 'cleared'])->default('pending');
            $table->timestamp('skycable_cleared_at')->nullable();
            $table->enum('globe_status', ['pending', 'in_progress', 'cleared'])->default('pending');
            $table->timestamp('globe_cleared_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('pole_cable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pole_id')->constrained()->cascadeOnDelete();
            $table->string('slot_label');
            $table->enum('occupied_by', ['skycable', 'globe', 'meralco', 'others', 'free'])->default('free');
            $table->enum('status', ['occupied', 'free'])->default('free');
            $table->timestamps();

            $table->unique(['pole_id', 'slot_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pole_cable_slots');
        Schema::dropIfExists('poles');
    }
};
