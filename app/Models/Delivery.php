<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'pickup_request_id', 'from_warehouse_id', 'to_warehouse_id',
        'dispatched_by', 'dispatched_at', 'arrived_at',
        'accepted_by', 'accepted_at', 'status',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'arrived_at'    => 'datetime',
        'accepted_at'   => 'datetime',
    ];

    public function pickupRequest()  { return $this->belongsTo(PickupRequest::class); }
    public function fromWarehouse()  { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse()    { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function dispatchedBy()   { return $this->belongsTo(User::class, 'dispatched_by'); }
    public function acceptedBy()     { return $this->belongsTo(User::class, 'accepted_by'); }
    public function items()          { return $this->hasMany(DeliveryItem::class); }
}
