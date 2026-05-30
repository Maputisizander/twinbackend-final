<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'company', 'role', 'project_access',
        'subcontractor_id', 'team_id',
        'first_name', 'last_name', 'email', 'password',
        'cellphone', 'address', 'profile_photo',
        'current_gps_lat', 'current_gps_lng',
        'last_seen_at', 'last_login',
        'status', 'password_reset_required', 'temp_password_set_at',
        'can_approve_delivery',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return [
            'project_access'          => 'array',
            'password'                => 'hashed',
            'last_seen_at'            => 'datetime',
            'last_login'              => 'datetime',
            'temp_password_set_at'    => 'datetime',
            'password_reset_required' => 'boolean',
            'can_approve_delivery'    => 'boolean',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->diffInMinutes(now()) <= 5;
    }

    public function subcontractor()
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
