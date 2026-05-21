<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobeTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number', 'subcontractor_id', 'team_id',
        'pole_id', 'nap_box_id',
        'created_by', 'claimed_by',
        'assigned_at', 'claimed_at', 'status',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'claimed_at'  => 'datetime',
    ];

    public function pole()          { return $this->belongsTo(Pole::class); }
    public function napBox()        { return $this->belongsTo(GlobeNapBox::class, 'nap_box_id'); }
    public function subcontractor() { return $this->belongsTo(Subcontractor::class); }
    public function team()          { return $this->belongsTo(Team::class); }
    public function createdBy()     { return $this->belongsTo(User::class, 'created_by'); }
    public function claimedBy()     { return $this->belongsTo(User::class, 'claimed_by'); }
    public function teardownReport(){ return $this->hasOne(GlobeTeardownReport::class, 'ticket_id'); }
}
