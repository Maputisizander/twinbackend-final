<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PsgcBarangay extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['code', 'name', 'city_code'];

    public function city() { return $this->belongsTo(PsgcCity::class, 'city_code', 'code'); }
}
