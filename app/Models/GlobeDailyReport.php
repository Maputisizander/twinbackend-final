<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobeDailyReport extends Model
{
    protected $fillable = [
        'team_id', 'submitted_by', 'report_date',
        'total_tickets', 'total_completed', 'total_rejected',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'report_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function team()       { return $this->belongsTo(Team::class); }
    public function submittedBy(){ return $this->belongsTo(User::class, 'submitted_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }

    public function tickets()
    {
        return $this->belongsToMany(
            GlobeTicket::class,
            'globe_daily_report_tickets',
            'daily_report_id',
            'ticket_id'
        );
    }
}
