<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullOutRequest extends Model
{
    protected $fillable = [
        'warehouse_id', 'to_warehouse_id', 'purpose', 'declared_by', 'approved_by',
        'approved_at', 'destination', 'arrival_confirmed_by', 'arrival_confirmed_at', 'status', 'notes',
    ];

    protected $casts = ['arrival_confirmed_at' => 'datetime', 'approved_at' => 'datetime'];

    public function warehouse()           { return $this->belongsTo(Warehouse::class); }
    public function toWarehouse()         { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function declaredBy()          { return $this->belongsTo(User::class, 'declared_by'); }
    public function approvedBy()          { return $this->belongsTo(User::class, 'approved_by'); }
    public function arrivalConfirmedBy()  { return $this->belongsTo(User::class, 'arrival_confirmed_by'); }
    public function items()               { return $this->hasMany(PullOutItem::class, 'pull_out_request_id'); }
}
