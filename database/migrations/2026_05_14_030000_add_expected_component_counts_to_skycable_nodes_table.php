<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skycable_nodes', function (Blueprint $table) {
            $table->integer('expected_nodes')->default(0)->after('actual_cable');
            $table->integer('expected_amplifier')->default(0)->after('expected_nodes');
            $table->integer('expected_extender')->default(0)->after('expected_amplifier');
            $table->integer('expected_tsc')->default(0)->after('expected_extender');
        });
    }

    public function down(): void
    {
        Schema::table('skycable_nodes', function (Blueprint $table) {
            $table->dropColumn([
                'expected_nodes',
                'expected_amplifier',
                'expected_extender',
                'expected_tsc',
            ]);
        });
    }
};
