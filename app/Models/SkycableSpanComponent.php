<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkycableSpanComponent extends Model
{
    protected $fillable = ['span_id', 'component_type', 'expected_count', 'actual_count', 'unit'];

    public function span() { return $this->belongsTo(SkycableSpan::class, 'span_id'); }
}
