<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkycableTeardownReport extends Model
{
    protected $fillable = [
        'local_id', 'span_id', 'team_id', 'lineman_id',
        'start_time', 'end_time', 'duration_minutes',
        'expected_cable', 'actual_cable',
        'nodes_collected', 'amplifiers_collected', 'extenders_collected', 'tsc_collected',
        'powersupply_collected', 'ps_housing_collected',
        'before_photo', 'after_photo', 'pole_tag_photo', 'bunching_photo',
        'status', 'subcon_reviewed_by', 'subcon_reviewed_at',
        'backend_approved_by', 'backend_approved_at',
        'rejection_reason', 'notes',
        'offline_mode', 'captured_at_device', 'received_at_server',
        'captured_lat', 'captured_lng',
    ];

    protected $casts = [
        'start_time'          => 'datetime',
        'end_time'            => 'datetime',
        'subcon_reviewed_at'  => 'datetime',
        'backend_approved_at' => 'datetime',
        'captured_at_device'  => 'datetime',
        'received_at_server'  => 'datetime',
        'offline_mode'        => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (SkycableTeardownReport $report) {
            if ($report->start_time && $report->end_time) {
                // Parse both as UTC to avoid timezone-related negative durations
                $start = \Carbon\Carbon::parse($report->start_time)->utc();
                $end   = \Carbon\Carbon::parse($report->end_time)->utc();
                $diff  = $start->diffInMinutes($end, false); // false = signed
                $report->duration_minutes = max(0, (int) $diff);
            }
        });
    }

    public function span()           { return $this->belongsTo(SkycableSpan::class, 'span_id'); }
    public function team()           { return $this->belongsTo(Team::class); }
    public function lineman()        { return $this->belongsTo(User::class, 'lineman_id'); }
    public function slots()          { return $this->hasMany(SkycableTeardownReportSlot::class, 'teardown_report_id'); }
    public function photos()         { return $this->hasMany(SkycableTeardownPhoto::class, 'teardown_report_id'); }
}
