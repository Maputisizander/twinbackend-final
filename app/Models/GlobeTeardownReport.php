<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobeTeardownReport extends Model
{
    protected $fillable = [
        'ticket_id', 'lineman_id', 'wire_status', 'teardown_date',
        'before_photo', 'after_photo', 'pole_tag_photo',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
        'offline_mode', 'captured_at_device', 'received_at_server',
        'captured_lat', 'captured_lng',
    ];

    protected $casts = [
        'teardown_date'      => 'date',
        'approved_at'        => 'datetime',
        'captured_at_device' => 'datetime',
        'received_at_server' => 'datetime',
        'offline_mode'       => 'boolean',
    ];

    public function ticket()     { return $this->belongsTo(GlobeTicket::class, 'ticket_id'); }
    public function lineman()    { return $this->belongsTo(User::class, 'lineman_id'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function slots()      { return $this->hasMany(GlobeTeardownReportSlot::class, 'teardown_report_id'); }
}
