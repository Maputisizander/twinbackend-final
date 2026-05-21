<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    protected $fillable = ['warehouse_id', 'item_type', 'quantity', 'unit'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
}
