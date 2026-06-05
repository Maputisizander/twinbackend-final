<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseReceiptSource extends Model
{
    protected $fillable = [
        'receipt_id', 'teardown_local_id', 'via_delivery_id', 'from_receipt_id',
    ];

    public function receipt()      { return $this->belongsTo(WarehouseReceipt::class, 'receipt_id'); }
    public function fromReceipt()  { return $this->belongsTo(WarehouseReceipt::class, 'from_receipt_id'); }
    public function viaDelivery()  { return $this->belongsTo(Delivery::class, 'via_delivery_id'); }
    public function teardown()     { return $this->belongsTo(SkycableTeardownReport::class, 'teardown_local_id', 'local_id'); }
}
