<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkycableDailyReport extends Model
{
    protected $fillable = [
        'node_id', 'team_id', 'subcontractor_id', 'submitted_by',
        'report_date', 'status', 'report_type',
        'subcon_reviewed_by', 'subcon_reviewed_at',
        'backend_approved_by', 'backend_approved_at',
        'rejection_reason', 'notes',
    ];

    protected $casts = [
        'report_date'         => 'date',
        'subcon_reviewed_at'  => 'datetime',
        'backend_approved_at' => 'datetime',
    ];

    public function node()         { return $this->belongsTo(SkycableNode::class, 'node_id'); }
    public function team()         { return $this->belongsTo(Team::class); }
    public function subcontractor(){ return $this->belongsTo(Subcontractor::class); }
    public function submittedBy()  { return $this->belongsTo(User::class, 'submitted_by'); }

    public function teardownReports()
    {
        return $this->belongsToMany(
            SkycableTeardownReport::class,
            'skycable_daily_report_logs',
            'daily_report_id',
            'teardown_report_id'
        );
    }

    public function getMissingImages(): array
    {
        $missing = [];
        foreach ($this->teardownReports as $report) {
            $required = ['before_photo', 'after_photo', 'pole_tag_photo'];
            $lacking  = array_filter($required, fn ($f) => empty($report->$f));

            if (! empty($lacking)) {
                $missing[] = [
                    'teardown_report_id' => $report->id,
                    'span'               => optional($report->span->fromPole->pole)->pole_code
                                         . ' → '
                                         . optional($report->span->toPole->pole)->pole_code,
                    'missing'            => array_values($lacking),
                ];
            }
        }
        return $missing;
    }
}
