<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkycableSite extends Model
{
    use SoftDeletes;

    protected $fillable = ['area_id', 'name', 'address', 'barangay_code'];

    public function area()     { return $this->belongsTo(SkycableArea::class, 'area_id'); }
    public function barangay() { return $this->belongsTo(PsgcBarangay::class, 'barangay_code', 'code'); }
    public function nodes()    { return $this->hasMany(SkycableNode::class, 'site_id'); }
}
