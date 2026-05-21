<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketAttachment extends Model
{
    protected $fillable = ['support_ticket_id', 'message_id', 'file_path', 'file_name'];

    public function ticket()  { return $this->belongsTo(SupportTicket::class); }
    public function message() { return $this->belongsTo(SupportTicketMessage::class); }
}
