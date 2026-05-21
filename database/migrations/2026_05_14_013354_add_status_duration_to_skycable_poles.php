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
            $table->enum('status', ['pending', 'in_progress', 'completed'])
                  ->default('pending')
                  ->after('sequence');
            $table->unsignedInteger('duration')->nullable()
                  ->comment('minutes from date_start to cleared_at')
                  ->after('cleared_at');
        });
    }

    public function down(): void
    {
        Schema::table('skycable_poles', function (Blueprint $table) {
            $table->dropColumn(['status', 'duration']);
        });
    }
};
