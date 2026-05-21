<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PsgcRegion extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['code', 'name'];

    public function provinces()
    {
        return $this->hasMany(PsgcProvince::class, 'region_code', 'code');
    }
}
