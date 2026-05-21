<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobeTeardownReportSlot extends Model
{
    protected $fillable = ['teardown_report_id', 'pole_id', 'pole_cable_slot_id', 'slot_label'];

    public function teardownReport() { return $this->belongsTo(GlobeTeardownReport::class, 'teardown_report_id'); }
    public function pole()           { return $this->belongsTo(Pole::class); }
    public function cableSlot()      { return $this->belongsTo(PoleCableSlot::class, 'pole_cable_slot_id'); }
}
