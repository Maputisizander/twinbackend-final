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
        Schema::create('skycable_span_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('span_id')->unique()->constrained('skycable_spans')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained('skycable_nodes')->cascadeOnDelete();

            // Expected quantities (set by admin / imported)
            $table->decimal('expected_cable', 10, 2)->default(0);
            $table->unsignedInteger('expected_node')->default(0);
            $table->unsignedInteger('expected_amplifier')->default(0);
            $table->unsignedInteger('expected_extender')->default(0);
            $table->unsignedInteger('expected_tsc')->default(0);
            $table->unsignedInteger('expected_powersupply')->default(0);
            $table->unsignedInteger('expected_ps_housing')->default(0);

            // Actual collected (updated on each mobile submission)
            $table->decimal('actual_cable', 10, 2)->default(0);
            $table->unsignedInteger('actual_node')->default(0);
            $table->unsignedInteger('actual_amplifier')->default(0);
            $table->unsignedInteger('actual_extender')->default(0);
            $table->unsignedInteger('actual_tsc')->default(0);
            $table->unsignedInteger('actual_powersupply')->default(0);
            $table->unsignedInteger('actual_ps_housing')->default(0);

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skycable_span_summaries');
    }
};
