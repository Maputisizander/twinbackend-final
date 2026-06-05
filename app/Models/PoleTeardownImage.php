<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoleTeardownImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'pole_id',
        'area_id',
        'node_id',
        'pole_code',
        'image_type',
        'pole_tag',
        'file_path',
        'inventory_type',
        'locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'locked'    => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function getImageUrlAttribute(): string
    {
        return $this->file_path
            ? \Illuminate\Support\Facades\Storage::url($this->file_path)
            : '';
    }

    protected $appends = ['image_url'];

    public function area()
    {
        return $this->belongsTo(SkycableArea::class, 'area_id');
    }

    public function node()
    {
        return $this->belongsTo(SkycableNode::class, 'node_id');
    }

    public function pole()
    {
        return $this->belongsTo(Pole::class, 'pole_id');
    }
}
