<?php

namespace App\Http\Controllers\Api\Globe;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GlobeDailyReport;
use App\Models\GlobeTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $query = GlobeDailyReport::with(['team', 'submittedBy'])
            ->when($request->team_id, fn ($q) => $q->where('team_id', $request->team_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->date, fn ($q) => $q->whereDate('report_date', $request->date));

        return response()->json($query->latest('report_date')->paginate(30));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'team_id'    => 'required|exists:teams,id',
            'report_date' => 'required|date',
            'ticket_ids'  => 'required|array|min:1',
            'ticket_ids.*' => 'exists:globe_tickets,id',
        ]);

        $report = DB::transaction(function () use ($request, $data) {
            $tickets   = GlobeTicket::whereIn('id', $data['ticket_ids'])->get();
            $completed = $tickets->where('status', 'completed')->count();
            $rejected  = $tickets->where('status', 'rejected')->count();

            $report = GlobeDailyReport::create([
                'team_id'         => $data['team_id'],
                'submitted_by'    => $request->user()->id,
                'report_date'     => $data['report_date'],
                'total_tickets'   => $tickets->count(),
                'total_completed' => $completed,
                'total_rejected'  => $rejected,
                'status'          => 'submitted',
            ]);

            $report->tickets()->sync($data['ticket_ids']);
            AuditLog::record('create', $report, null, $report->toArray());

            return $report;
        });

        return response()->json($report->load('tickets'), 201);
    }

    public function show(GlobeDailyReport $dailyReport)
    {
        return response()->json($dailyReport->load(['team', 'submittedBy', 'approvedBy', 'tickets.teardownReport']));
    }

    public function approve(Request $request, GlobeDailyReport $dailyReport)
    {
        $data = $request->validate([
            'action'           => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string',
        ]);

        $user = $request->user();
        $old  = $dailyReport->toArray();

        if ($data['action'] === 'approve') {
            $dailyReport->update([
                'status'      => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        } else {
            $dailyReport->update([
                'status'           => 'rejected',
                'approved_by'      => $user->id,
                'approved_at'      => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);
        }

        AuditLog::record('update', $dailyReport, $old, $dailyReport->toArray());

        return response()->json($dailyReport->fresh());
    }
}
