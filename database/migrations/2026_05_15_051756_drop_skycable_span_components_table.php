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
        // Migrate expected counts from old normalized table into span_summaries (flat per-span row)
        if (Schema::hasTable('skycable_span_components') && Schema::hasTable('skycable_span_summaries')) {
            $components = DB::table('skycable_span_components')
                ->join('skycable_spans', 'skycable_span_components.span_id', '=', 'skycable_spans.id')
                ->select('skycable_span_components.span_id', 'skycable_spans.node_id', 'component_type', 'expected_count')
                ->get();

            $grouped = $components->groupBy('span_id');

            foreach ($grouped as $spanId => $rows) {
                $nodeId = $rows->first()->node_id;
                $data = ['node_id' => $nodeId];
                foreach ($rows as $row) {
                    $col = match($row->component_type) {
                        'node'           => 'expected_node',
                        'amplifier'      => 'expected_amplifier',
                        'extender'       => 'expected_extender',
                        'tsc'            => 'expected_tsc',
                        'powersupply'    => 'expected_powersupply',
                        'powersupply_case' => 'expected_ps_housing',
                        default          => null,
                    };
                    if ($col) $data[$col] = $row->expected_count ?? 0;
                }
                DB::table('skycable_span_summaries')->updateOrInsert(
                    ['span_id' => $spanId],
                    array_merge($data, ['updated_at' => now(), 'created_at' => now()])
                );
            }
        }

        Schema::dropIfExists('skycable_span_components');
    }

    public function down(): void
    {
        Schema::create('skycable_span_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('span_id')->constrained('skycable_spans')->cascadeOnDelete();
            $table->enum('component_type', ['node','amplifier','extender','tsc','powersupply','powersupply_case']);
            $table->decimal('expected_count', 10, 2)->default(0);
            $table->decimal('actual_count', 10, 2)->default(0);
            $table->string('unit')->nullable();
            $table->timestamps();
        });
    }
};
