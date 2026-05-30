<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poles', function (Blueprint $table) {
            $table->dropUnique('poles_pole_code_unique');
            $table->index('pole_code', 'poles_pole_code_index');
        });
    }

    public function down(): void
    {
        Schema::table('poles', function (Blueprint $table) {
            $table->dropIndex('poles_pole_code_index');
            $table->unique('pole_code', 'poles_pole_code_unique');
        });
    }
};
