<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkycableTeardownPhoto extends Model
{
    protected $fillable = ['teardown_report_id', 'photo_type', 'image_path'];

    public function teardownReport() { return $this->belongsTo(SkycableTeardownReport::class, 'teardown_report_id'); }
}
