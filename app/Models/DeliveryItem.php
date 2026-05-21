<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    protected $fillable = ['delivery_id', 'item_type', 'quantity', 'unit'];

    public function delivery() { return $this->belongsTo(Delivery::class); }
}
