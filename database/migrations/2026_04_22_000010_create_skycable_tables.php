<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Areas (NCR, North Luzon, South Luzon, Visayas, Mindanao)
        Schema::create('skycable_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Nodes — top-level map unit per area+barangay
        Schema::create('skycable_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('skycable_areas')->cascadeOnDelete();
            $table->string('barangay_code', 20)->nullable();
            $table->foreign('barangay_code')->references('code')->on('psgc_barangays')->nullOnDelete();
            $table->foreignId('subcontractor_id')->nullable()->constrained('subcontractors')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();

            $table->string('name');
            $table->string('label')->nullable();
            $table->string('full_label')->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->enum('data_source', ['manual', 'json_import', 'ai_scanner'])->default('manual');
            $table->string('source_file')->nullable();

            $table->date('date_start')->nullable();
            $table->date('due_date')->nullable();
            $table->date('date_finished')->nullable();

            $table->decimal('expected_cable', 12, 2)->default(0);
            $table->decimal('actual_cable', 12, 2)->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0);

            $table->softDeletes();
            $table->timestamps();
        });

        // Bridge: node ↔ shared poles
        Schema::create('skycable_poles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('skycable_nodes')->cascadeOnDelete();
            $table->foreignId('pole_id')->constrained('poles')->cascadeOnDelete();
            $table->integer('sequence')->default(0);
            $table->timestamps();

            $table->unique(['node_id', 'pole_id']);
        });

        // Spans — pair of poles where cable/equipment is installed
        Schema::create('skycable_spans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('skycable_nodes')->cascadeOnDelete();
            $table->foreignId('from_pole_id')->constrained('skycable_poles')->cascadeOnDelete();
            $table->foreignId('to_pole_id')->constrained('skycable_poles')->cascadeOnDelete();
            $table->string('span_code')->nullable()->unique();
            $table->decimal('length_meters', 10, 2)->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['from_pole_id', 'to_pole_id']);
        });

        // Expected components per span (from map data)
        Schema::create('skycable_span_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('span_id')->constrained('skycable_spans')->cascadeOnDelete();
            $table->enum('component_type', ['node', 'amplifier', 'extender', 'tsc', 'cable', 'powersupply']);
            $table->decimal('expected_count', 10, 2)->default(0);
            $table->decimal('actual_count', 10, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->timestamps();

            $table->unique(['span_id', 'component_type']);
        });

        // Teardown reports per span
        Schema::create('skycable_teardown_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('span_id')->constrained('skycable_spans')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('lineman_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_minutes')->nullable();

            $table->decimal('expected_cable', 10, 2)->default(0);
            $table->decimal('actual_cable', 10, 2)->default(0);

            $table->string('before_photo')->nullable();
            $table->string('after_photo')->nullable();
            $table->string('pole_tag_photo')->nullable();
            $table->string('bunching_photo')->nullable();

            $table->enum('status', ['pending', 'submitted', 'subcon_approved', 'backend_approved', 'rejected'])->default('pending');
            $table->foreignId('subcon_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('subcon_reviewed_at')->nullable();
            $table->foreignId('backend_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('backend_approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();

            // Offline sync fields
            $table->boolean('offline_mode')->default(false);
            $table->timestamp('captured_at_device')->nullable();
            $table->timestamp('received_at_server')->nullable();
            $table->decimal('captured_lat', 10, 7)->nullable();
            $table->decimal('captured_lng', 10, 7)->nullable();

            $table->timestamps();
        });

        // Which cable slots were removed per teardown report
        Schema::create('skycable_teardown_report_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teardown_report_id')->constrained('skycable_teardown_reports')->cascadeOnDelete();
            $table->foreignId('pole_id')->constrained('poles')->cascadeOnDelete();
            $table->foreignId('pole_cable_slot_id')->constrained('pole_cable_slots')->cascadeOnDelete();
            $table->string('slot_label');
            $table->timestamps();
        });

        // Photos for teardown reports (flexible multiple photos)
        Schema::create('skycable_teardown_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teardown_report_id')->constrained('skycable_teardown_reports')->cascadeOnDelete();
            $table->enum('photo_type', ['before', 'after', 'pole_tag', 'bunching', 'supporting']);
            $table->string('image_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skycable_teardown_photos');
        Schema::dropIfExists('skycable_teardown_report_slots');
        Schema::dropIfExists('skycable_teardown_reports');
        Schema::dropIfExists('skycable_span_components');
        Schema::dropIfExists('skycable_spans');
        Schema::dropIfExists('skycable_poles');
        Schema::dropIfExists('skycable_nodes');
        Schema::dropIfExists('skycable_areas');
    }
};
