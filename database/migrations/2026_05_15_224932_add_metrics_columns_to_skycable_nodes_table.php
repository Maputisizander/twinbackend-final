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
        $existing = collect(DB::select('SHOW COLUMNS FROM skycable_nodes'))->pluck('Field')->toArray();
        Schema::table('skycable_nodes', function (Blueprint $table) use ($existing) {
            $cols = [
                'expected_powersupply' => fn() => $table->unsignedInteger('expected_powersupply')->default(0)->after('expected_tsc'),
                'expected_ps_housing'  => fn() => $table->unsignedInteger('expected_ps_housing')->default(0)->after('expected_powersupply'),
                'actual_node'          => fn() => $table->unsignedInteger('actual_node')->default(0)->after('expected_ps_housing'),
                'actual_amplifier'     => fn() => $table->unsignedInteger('actual_amplifier')->default(0)->after('actual_node'),
                'actual_extender'      => fn() => $table->unsignedInteger('actual_extender')->default(0)->after('actual_amplifier'),
                'actual_tsc'           => fn() => $table->unsignedInteger('actual_tsc')->default(0)->after('actual_extender'),
                'actual_powersupply'   => fn() => $table->unsignedInteger('actual_powersupply')->default(0)->after('actual_tsc'),
                'actual_ps_housing'    => fn() => $table->unsignedInteger('actual_ps_housing')->default(0)->after('actual_powersupply'),
            ];
            foreach ($cols as $col => $fn) {
                if (!in_array($col, $existing)) $fn();
            }
        });
    }

    public function down(): void
    {
        Schema::table('skycable_nodes', function (Blueprint $table) {
            $table->dropColumn([
                'expected_powersupply', 'expected_ps_housing',
                'actual_node', 'actual_amplifier', 'actual_extender',
                'actual_tsc', 'actual_powersupply', 'actual_ps_housing',
            ]);
        });
    }
};
