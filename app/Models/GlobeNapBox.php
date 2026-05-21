<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobeNapBox extends Model
{
    use SoftDeletes;

    protected $fillable = ['pole_id', 'nap_code', 'port_count', 'status'];

    protected static function booted(): void
    {
        static::created(function (GlobeNapBox $napBox) {
            $ports = [];
            for ($i = 1; $i <= $napBox->port_count; $i++) {
                $ports[] = [
                    'nap_box_id'    => $napBox->id,
                    'port_number'   => $i,
                    'status'        => 'free',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
            GlobeNapPort::insert($ports);
        });
    }

    public function pole()    { return $this->belongsTo(Pole::class); }
    public function ports()   { return $this->hasMany(GlobeNapPort::class, 'nap_box_id'); }
    public function surveys() { return $this->hasMany(GlobeNapSurvey::class, 'nap_box_id'); }
    public function tickets() { return $this->hasMany(GlobeTicket::class, 'nap_box_id'); }
}
