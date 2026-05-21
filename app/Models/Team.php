<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = ['company', 'subcontractor_id', 'name', 'status'];

    public function subcontractor()
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members')
                    ->withPivot('role')
                    ->withTimestamps();
    }
}
