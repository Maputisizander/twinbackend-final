<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkycablePoleTeardownLog extends Model
{
    protected $fillable = [
        'skycable_pole_id',
        'pole_id',
        'node_id',
        'lineman_id',
        'started_at',
        'finished_at',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    // Auto-compute duration_minutes before saving
    protected static function booted(): void
    {
        static::saving(function (self $log) {
            if ($log->started_at && $log->finished_at) {
                $log->duration_minutes = (int) $log->started_at->diffInMinutes($log->finished_at);
            }
            // Auto-set status based on timing fields
            if ($log->finished_at) {
                $log->status = 'completed';
            } elseif ($log->started_at) {
                $log->status = 'in_progress';
            }
        });
    }

    public function pole()         { return $this->belongsTo(Pole::class); }
    public function skycablePole() { return $this->belongsTo(SkycablePole::class); }
    public function node()         { return $this->belongsTo(SkycableNode::class, 'node_id'); }
    public function lineman()      { return $this->belongsTo(User::class, 'lineman_id'); }
}
