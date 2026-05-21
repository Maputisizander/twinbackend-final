<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkycableSpanSummary extends Model
{
    protected $fillable = [
        'span_id', 'node_id',
        'expected_cable', 'expected_node', 'expected_amplifier', 'expected_extender',
        'expected_tsc', 'expected_powersupply', 'expected_ps_housing',
        'actual_cable', 'actual_node', 'actual_amplifier', 'actual_extender',
        'actual_tsc', 'actual_powersupply', 'actual_ps_housing',
        'updated_by',
    ];

    public function span() { return $this->belongsTo(SkycableSpan::class, 'span_id'); }
    public function node() { return $this->belongsTo(SkycableNode::class, 'node_id'); }
}
