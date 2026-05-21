<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoleCableSlot extends Model
{
    protected $fillable = ['pole_id', 'slot_label', 'occupied_by', 'status'];

    public function pole() { return $this->belongsTo(Pole::class); }
}
