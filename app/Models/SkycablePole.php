<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkycablePole extends Model
{
    protected $fillable = ['node_id', 'pole_id', 'sequence', 'pole_index', 'date_start', 'cleared_at', 'status', 'duration'];

    protected $casts = [
        'date_start' => 'datetime',
        'cleared_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $sp) {
            // Auto-compute duration in minutes when both timestamps present
            if ($sp->date_start && $sp->cleared_at) {
                $sp->duration = max(0, (int) $sp->date_start->diffInMinutes($sp->cleared_at));
            }
            // Auto-set status from timestamps
            if ($sp->cleared_at) {
                $sp->status = 'completed';
            } elseif ($sp->date_start) {
                $sp->status = 'in_progress';
            }
        });
    }

    public function node()       { return $this->belongsTo(SkycableNode::class, 'node_id'); }
    public function pole()       { return $this->belongsTo(Pole::class); }
    public function spansFrom()  { return $this->hasMany(SkycableSpan::class, 'from_pole_id'); }
    public function spansTo()    { return $this->hasMany(SkycableSpan::class, 'to_pole_id'); }

    public function allSpans()
    {
        return SkycableSpan::where('from_pole_id', $this->id)
                           ->orWhere('to_pole_id', $this->id);
    }
}
