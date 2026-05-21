<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PsgcProvince extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['code', 'name', 'region_code'];

    public function region() { return $this->belongsTo(PsgcRegion::class, 'region_code', 'code'); }
    public function cities() { return $this->hasMany(PsgcCity::class, 'province_code', 'code'); }
}
