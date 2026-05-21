<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skycable_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('skycable_nodes')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('subcontractor_id')->nullable()->constrained('subcontractors')->nullOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();

            $table->date('report_date');
            $table->enum('status', [
                'draft',
                'submitted',
                'subcon_reviewing',
                'subcon_approved',
                'backend_approved',
                'rejected',
            ])->default('draft');

            $table->foreignId('subcon_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('subcon_reviewed_at')->nullable();
            $table->foreignId('backend_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('backend_approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('skycable_daily_report_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('skycable_daily_reports')->cascadeOnDelete();
            $table->foreignId('teardown_report_id')->constrained('skycable_teardown_reports')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['daily_report_id', 'teardown_report_id'], 'sdr_logs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skycable_daily_report_logs');
        Schema::dropIfExists('skycable_daily_reports');
    }
};
