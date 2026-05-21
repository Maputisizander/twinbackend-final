<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = SupportTicket::with(['submittedBy', 'assignedTo'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority));

        // Non-admin users only see their own tickets
        if (! in_array($user->role, ['admin', 'executive', 'project_manager']) || $user->company !== 'telcovantage') {
            $query->where('submitted_by', $user->id);
        } else {
            $query->when($request->company, fn ($q) => $q->where('company', $request->company));
        }

        return response()->json($query->latest()->paginate(30));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'     => 'required|string|max:255',
            'description' => 'required|string',
            'priority'    => 'nullable|in:low,medium,high,urgent',
        ]);

        $user   = $request->user();
        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
            'company'       => $user->company,
            'submitted_by'  => $user->id,
            'subject'       => $data['subject'],
            'description'   => $data['description'],
            'priority'      => $data['priority'] ?? 'medium',
            'status'        => 'open',
        ]);

        AuditLog::record('create', $ticket, null, $ticket->toArray());

        return response()->json($ticket, 201);
    }

    public function show(SupportTicket $supportTicket)
    {
        return response()->json($supportTicket->load(['submittedBy', 'assignedTo', 'messages.sender', 'messages.attachments']));
    }

    public function reply(Request $request, SupportTicket $supportTicket)
    {
        $data = $request->validate([
            'message'       => 'required|string',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $supportTicket->id,
            'sender_id'         => $request->user()->id,
            'message'           => $data['message'],
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support-attachments', 'local');
                SupportTicketAttachment::create([
                    'support_ticket_id' => $supportTicket->id,
                    'message_id'        => $message->id,
                    'file_path'         => $path,
                    'file_name'         => $file->getClientOriginalName(),
                ]);
            }
        }

        return response()->json($message->load('attachments'), 201);
    }

    public function assign(Request $request, SupportTicket $supportTicket)
    {
        $data = $request->validate(['assigned_to' => 'required|exists:users,id']);

        $old = $supportTicket->toArray();
        $supportTicket->update(['assigned_to' => $data['assigned_to'], 'status' => 'in_progress']);
        AuditLog::record('update', $supportTicket, $old, $supportTicket->toArray());

        return response()->json($supportTicket);
    }

    public function updateStatus(Request $request, SupportTicket $supportTicket)
    {
        $data = $request->validate(['status' => 'required|in:open,in_progress,resolved,closed']);

        $old = $supportTicket->toArray();
        $upd = ['status' => $data['status']];

        if ($data['status'] === 'resolved') $upd['resolved_at'] = now();
        if ($data['status'] === 'closed')   $upd['closed_at']   = now();

        $supportTicket->update($upd);
        AuditLog::record('update', $supportTicket, $old, $supportTicket->toArray());

        return response()->json($supportTicket);
    }
}
