<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupRequest extends Model
{
    protected $fillable = [
        'from_warehouse_id', 'to_warehouse_id',
        'requested_by', 'approved_by', 'status',
    ];

    public function fromWarehouse() { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse()   { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function requestedBy()   { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy()    { return $this->belongsTo(User::class, 'approved_by'); }
    public function delivery()      { return $this->hasOne(Delivery::class); }
}
