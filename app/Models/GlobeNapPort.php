<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobeNapPort extends Model
{
    protected $fillable = [
        'nap_box_id', 'port_number', 'status',
        'subscriber_id', 'subscriber_name', 'account_number',
        'surveyed_by', 'surveyed_at', 'updated_by',
    ];

    protected $casts = ['surveyed_at' => 'datetime'];

    public function napBox()     { return $this->belongsTo(GlobeNapBox::class, 'nap_box_id'); }
    public function surveyedBy() { return $this->belongsTo(User::class, 'surveyed_by'); }
    public function updatedBy()  { return $this->belongsTo(User::class, 'updated_by'); }
}
