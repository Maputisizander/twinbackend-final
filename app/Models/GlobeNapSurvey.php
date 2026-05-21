<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobeNapSurvey extends Model
{
    protected $fillable = ['nap_box_id', 'surveyed_by', 'surveyed_at', 'status'];

    protected $casts = ['surveyed_at' => 'datetime'];

    public function napBox()     { return $this->belongsTo(GlobeNapBox::class, 'nap_box_id'); }
    public function surveyedBy() { return $this->belongsTo(User::class, 'surveyed_by'); }
    public function items()      { return $this->hasMany(GlobeNapSurveyItem::class, 'survey_id'); }
}
