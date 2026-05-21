<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcontractor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company', 'name', 'contact_name', 'contact_phone', 'contact_email', 'address', 'status',
    ];

    protected static function booted(): void
    {
        // Auto-create a default warehouse when a subcontractor is created
        static::created(function (Subcontractor $subcon) {
            Warehouse::create([
                'subcontractor_id' => $subcon->id,
                'name'             => $subcon->name . ' Warehouse',
                'type'             => 'subcon',
                'sqm'              => 0,
                'status'           => 'active',
            ]);
        });
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}
