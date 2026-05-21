<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseReceipt extends Model
{
    protected $fillable = [
        'warehouse_id', 'subcontractor_id', 'node_id',
        'received_by', 'approved_by', 'receipt_date', 'status',
    ];

    protected $casts = ['receipt_date' => 'date'];

    public function warehouse()     { return $this->belongsTo(Warehouse::class); }
    public function subcontractor() { return $this->belongsTo(Subcontractor::class); }
    public function node()          { return $this->belongsTo(SkycableNode::class, 'node_id'); }
    public function receivedBy()    { return $this->belongsTo(User::class, 'received_by'); }
    public function approvedBy()    { return $this->belongsTo(User::class, 'approved_by'); }
    public function items()         { return $this->hasMany(WarehouseReceiptItem::class, 'receipt_id'); }
}
