<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PsgcCity extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['code', 'name', 'province_code'];

    public function province()  { return $this->belongsTo(PsgcProvince::class, 'province_code', 'code'); }
    public function barangays() { return $this->hasMany(PsgcBarangay::class, 'city_code', 'code'); }
}
