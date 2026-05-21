<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoleReport extends Model
{
    protected $fillable = [
        'pole_id', 'node_id', 'submitted_by',
        'condition', 'material', 'height_ft',
        'landmark', 'notes',
        'latitude', 'longitude', 'gps_captured_at',
        'slots',
    ];

    protected $casts = [
        'slots'     => 'array',
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    public function pole()          { return $this->belongsTo(Pole::class); }
    public function node()          { return $this->belongsTo(SkycableNode::class, 'node_id'); }
    public function submittedBy()   { return $this->belongsTo(User::class, 'submitted_by'); }
}
