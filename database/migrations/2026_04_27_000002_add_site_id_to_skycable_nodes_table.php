<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skycable_nodes', function (Blueprint $table) {
            $table->foreignId('site_id')
                  ->nullable()
                  ->after('area_id')
                  ->constrained('skycable_sites')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('skycable_nodes', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });
    }
};
