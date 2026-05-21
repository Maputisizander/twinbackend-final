<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('globe_nap_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pole_id')->constrained('poles')->cascadeOnDelete();
            $table->string('nap_code')->unique();
            $table->enum('port_count', ['8', '12', '16', '32']);
            $table->enum('status', ['active', 'inactive', 'for_removal'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('globe_nap_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nap_box_id')->constrained('globe_nap_boxes')->cascadeOnDelete();
            $table->integer('port_number');
            $table->enum('status', ['active', 'inactive', 'free'])->default('free');
            $table->string('subscriber_id')->nullable();
            $table->string('subscriber_name')->nullable();
            $table->string('account_number')->nullable();
            $table->foreignId('surveyed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('surveyed_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['nap_box_id', 'port_number']);
        });

        Schema::create('globe_nap_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nap_box_id')->constrained('globe_nap_boxes')->cascadeOnDelete();
            $table->foreignId('surveyed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('surveyed_at')->nullable();
            $table->enum('status', ['pending', 'partial', 'complete'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('globe_nap_survey_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('globe_nap_surveys')->cascadeOnDelete();
            $table->integer('port_number');
            $table->string('subscriber_id')->nullable();
            $table->string('account_number')->nullable();
            $table->string('subscriber_name')->nullable();
            $table->enum('status', ['active', 'inactive', 'free'])->default('free');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('globe_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('subcontractor_id')->nullable()->constrained('subcontractors')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('pole_id')->constrained('poles')->cascadeOnDelete();
            $table->foreignId('nap_box_id')->nullable()->constrained('globe_nap_boxes')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'for_approval', 'completed', 'cancelled', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('globe_teardown_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('globe_tickets')->cascadeOnDelete();
            $table->foreignId('lineman_id')->constrained('users')->cascadeOnDelete();
            $table->enum('wire_status', ['removed', 'partially_removed', 'unable_to_remove'])->default('removed');
            $table->date('teardown_date')->nullable();
            $table->string('before_photo')->nullable();
            $table->string('after_photo')->nullable();
            $table->string('pole_tag_photo')->nullable();
            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();

            // Offline sync
            $table->boolean('offline_mode')->default(false);
            $table->timestamp('captured_at_device')->nullable();
            $table->timestamp('received_at_server')->nullable();
            $table->decimal('captured_lat', 10, 7)->nullable();
            $table->decimal('captured_lng', 10, 7)->nullable();

            $table->timestamps();
        });

        Schema::create('globe_teardown_report_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teardown_report_id')->constrained('globe_teardown_reports')->cascadeOnDelete();
            $table->foreignId('pole_id')->constrained('poles')->cascadeOnDelete();
            $table->foreignId('pole_cable_slot_id')->constrained('pole_cable_slots')->cascadeOnDelete();
            $table->string('slot_label');
            $table->timestamps();
        });

        Schema::create('globe_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->date('report_date');
            $table->integer('total_tickets')->default(0);
            $table->integer('total_completed')->default(0);
            $table->integer('total_rejected')->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('globe_daily_report_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('globe_daily_reports')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('globe_tickets')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['daily_report_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('globe_daily_report_tickets');
        Schema::dropIfExists('globe_daily_reports');
        Schema::dropIfExists('globe_teardown_report_slots');
        Schema::dropIfExists('globe_teardown_reports');
        Schema::dropIfExists('globe_tickets');
        Schema::dropIfExists('globe_nap_survey_items');
        Schema::dropIfExists('globe_nap_surveys');
        Schema::dropIfExists('globe_nap_ports');
        Schema::dropIfExists('globe_nap_boxes');
    }
};
