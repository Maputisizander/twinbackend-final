<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobeNapSurveyItem extends Model
{
    protected $fillable = [
        'survey_id', 'port_number',
        'subscriber_id', 'subscriber_name', 'account_number', 'status',
    ];

    public function survey() { return $this->belongsTo(GlobeNapSurvey::class); }
}
