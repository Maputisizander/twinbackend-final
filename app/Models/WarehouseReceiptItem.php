<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseReceiptItem extends Model
{
    protected $fillable = ['receipt_id', 'item_type', 'quantity', 'unit'];

    public function receipt() { return $this->belongsTo(WarehouseReceipt::class); }
}
