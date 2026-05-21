<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkycableSpan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'node_id', 'from_pole_id', 'to_pole_id',
        'span_code', 'length_meters', 'strand_length', 'number_of_runs', 'actual_cable',
        'status', 'completed_at', 'idempotency_key',
    ];

    protected $casts = ['completed_at' => 'datetime'];

    protected static function booted(): void
    {
        // When a new span is added that connects to an already-cleared pole,
        // reset that pole back to pending so the lineman must re-do it.
        static::created(function (self $span) {
            foreach (array_filter([$span->fromPole, $span->toPole]) as $sp) {
                if (! $sp->pole) continue;
                if ($sp->pole->skycable_status === 'cleared') {
                    $sp->pole->update([
                        'skycable_status'     => 'pending',
                        'skycable_cleared_at' => null,
                    ]);
                    $sp->update(['cleared_at' => null]);
                }
            }
        });
    }

    public function node()      { return $this->belongsTo(SkycableNode::class, 'node_id'); }
    public function fromPole()  { return $this->belongsTo(SkycablePole::class, 'from_pole_id'); }
    public function toPole()    { return $this->belongsTo(SkycablePole::class, 'to_pole_id'); }
    public function teardownReports() { return $this->hasMany(SkycableTeardownReport::class, 'span_id'); }
    public function summary()   { return $this->hasOne(SkycableSpanSummary::class, 'span_id'); }
}
