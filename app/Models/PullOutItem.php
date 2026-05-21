<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PullOutItem extends Model
{
    protected $fillable = ['pull_out_request_id', 'item_type', 'quantity', 'unit'];

    public function pullOutRequest() { return $this->belongsTo(PullOutRequest::class); }
}
