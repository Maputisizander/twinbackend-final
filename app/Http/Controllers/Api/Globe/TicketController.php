<?php

namespace App\Http\Controllers\Api\Globe;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GlobeTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = GlobeTicket::with(['pole', 'napBox', 'team', 'subcontractor', 'createdBy', 'claimedBy', 'teardownReport'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->team_id, fn ($q) => $q->where('team_id', $request->team_id))
            ->when($request->pole_id, fn ($q) => $q->where('pole_id', $request->pole_id));

        return response()->json($query->latest()->paginate(50));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pole_id'          => 'required|exists:poles,id',
            'nap_box_id'       => 'nullable|exists:globe_nap_boxes,id',
            'subcontractor_id' => 'nullable|exists:subcontractors,id',
            'team_id'          => 'nullable|exists:teams,id',
        ]);

        $ticket = GlobeTicket::create(array_merge($data, [
            'ticket_number' => 'GLB-' . strtoupper(Str::random(8)),
            'created_by'    => $request->user()->id,
            'status'        => 'pending',
        ]));

        AuditLog::record('create', $ticket, null, $ticket->toArray());

        return response()->json($ticket->load(['pole', 'napBox']), 201);
    }

    public function show(GlobeTicket $ticket)
    {
        return response()->json($ticket->load(['pole.barangay', 'napBox.ports', 'team', 'subcontractor', 'createdBy', 'claimedBy', 'teardownReport.slots']));
    }

    public function claim(Request $request, GlobeTicket $ticket)
    {
        if ($ticket->status !== 'pending') {
            return response()->json(['message' => 'Ticket is not available to claim.'], 422);
        }

        $data = $request->validate([
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $old = $ticket->toArray();
        $ticket->update([
            'claimed_by'  => $request->user()->id,
            'claimed_at'  => now(),
            'assigned_at' => now(),
            'team_id'     => $data['team_id'] ?? $ticket->team_id,
            'status'      => 'in_progress',
        ]);

        AuditLog::record('update', $ticket, $old, $ticket->toArray());

        return response()->json($ticket->fresh());
    }

    public function update(Request $request, GlobeTicket $ticket)
    {
        $data = $request->validate([
            'subcontractor_id' => 'sometimes|nullable|exists:subcontractors,id',
            'team_id'          => 'sometimes|nullable|exists:teams,id',
            'status'           => 'sometimes|in:pending,in_progress,for_approval,completed,cancelled,rejected',
        ]);

        $old = $ticket->toArray();
        $ticket->update($data);
        AuditLog::record('update', $ticket, $old, $ticket->toArray());

        return response()->json($ticket->fresh());
    }

    public function cancel(Request $request, GlobeTicket $ticket)
    {
        $old = $ticket->toArray();
        $ticket->update(['status' => 'cancelled']);
        AuditLog::record('update', $ticket, $old, $ticket->toArray());

        return response()->json(['message' => 'Ticket cancelled.']);
    }
}
