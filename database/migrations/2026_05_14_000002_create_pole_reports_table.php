<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pole_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pole_id')->constrained('poles')->cascadeOnDelete();
            $table->foreignId('node_id')->nullable()->constrained('skycable_nodes')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('condition')->nullable();
            $table->string('material')->nullable();
            $table->string('height_ft')->nullable();
            $table->text('landmark')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('gps_captured_at')->nullable();
            $table->json('slots')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pole_reports');
    }
};
