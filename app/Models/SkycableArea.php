<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkycableArea extends Model
{
    protected $fillable = ['name'];

    public function sites() { return $this->hasMany(SkycableSite::class, 'area_id'); }
    public function nodes() { return $this->hasMany(SkycableNode::class, 'area_id'); }
}
