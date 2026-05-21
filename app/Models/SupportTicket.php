<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number', 'company', 'submitted_by', 'assigned_to',
        'subject', 'description', 'priority', 'status',
        'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at'   => 'datetime',
    ];

    public function submittedBy() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function assignedTo()  { return $this->belongsTo(User::class, 'assigned_to'); }
    public function messages()    { return $this->hasMany(SupportTicketMessage::class); }
    public function attachments() { return $this->hasMany(SupportTicketAttachment::class); }
}
