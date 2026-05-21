<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pole_teardown_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id')->nullable();
            $table->unsignedBigInteger('pole_id');
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('node_id')->nullable();
            $table->string('pole_code');
            $table->string('image_type'); // before, after, pole_tag
            $table->string('pole_tag')->nullable();
            $table->string('file_path');
            $table->string('inventory_type'); // skycable, globe
            $table->timestamps();

            // Optional: Indexing for faster lookups
            $table->index(['report_id', 'inventory_type']);
            $table->index(['pole_id', 'inventory_type']);
            $table->index(['node_id', 'inventory_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pole_teardown_images');
    }
};
