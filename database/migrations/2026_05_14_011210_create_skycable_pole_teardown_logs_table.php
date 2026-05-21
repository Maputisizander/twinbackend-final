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
        Schema::create('skycable_pole_teardown_logs', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('skycable_pole_id')
                  ->nullable()
                  ->constrained('skycable_poles')
                  ->nullOnDelete();
            $table->foreignId('pole_id')
                  ->constrained('poles')
                  ->cascadeOnDelete();
            $table->foreignId('node_id')
                  ->nullable()
                  ->constrained('skycable_nodes')
                  ->nullOnDelete();
            $table->foreignId('lineman_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable(); // when after-photo was captured
            $table->unsignedInteger('duration_minutes')->nullable(); // auto-computed on save

            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');

            $table->timestamps();

            $table->index(['pole_id', 'status']);
            $table->index('skycable_pole_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skycable_pole_teardown_logs');
    }
};
