<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinemanLocation extends Model
{
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'barangay',
        'city',
        'province',
        'region_name',
        'pinged_at',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'accuracy'  => 'float',
        'pinged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
