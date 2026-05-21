<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = ['subcontractor_id', 'name', 'type', 'sqm', 'status'];

    public function subcontractor() { return $this->belongsTo(Subcontractor::class); }
    public function stocks()        { return $this->hasMany(WarehouseStock::class); }
    public function receipts()      { return $this->hasMany(WarehouseReceipt::class); }
}
