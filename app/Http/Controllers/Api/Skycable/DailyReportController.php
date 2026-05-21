<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SkycableDailyReport;
use App\Models\SkycableTeardownReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $query = SkycableDailyReport::with(['node', 'team', 'subcontractor', 'submittedBy'])
            ->when($request->node_id, fn ($q) => $q->where('node_id', $request->node_id))
            ->when($request->team_id, fn ($q) => $q->where('team_id', $request->team_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->date, fn ($q) => $q->whereDate('report_date', $request->date));

        return response()->json($query->latest('report_date')->paginate(30));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'node_id'           => 'required|exists:skycable_nodes,id',
            'team_id'           => 'required|exists:teams,id',
            'subcontractor_id'  => 'required|exists:subcontractors,id',
            'report_date'       => 'required|date',
            'report_type'       => 'required|in:full_report,pole_report',
            'teardown_report_ids' => 'required|array|min:1',
            'teardown_report_ids.*' => 'exists:skycable_teardown_reports,id',
            'notes'             => 'nullable|string',
        ]);

        $report = DB::transaction(function () use ($request, $data) {
            $dailyReport = SkycableDailyReport::create([
                'node_id'          => $data['node_id'],
                'team_id'          => $data['team_id'],
                'subcontractor_id' => $data['subcontractor_id'],
                'submitted_by'     => $request->user()->id,
                'report_date'      => $data['report_date'],
                'status'           => 'submitted',
                'report_type'      => $data['report_type'],
                'notes'            => $data['notes'] ?? null,
            ]);

            $dailyReport->teardownReports()->sync($data['teardown_report_ids']);
            AuditLog::record('create', $dailyReport, null, $dailyReport->toArray());

            return $dailyReport;
        });

        return response()->json($report->load('teardownReports'), 201);
    }

    public function show(SkycableDailyReport $dailyReport)
    {
        $dailyReport->load(['node', 'team', 'subcontractor', 'submittedBy', 'teardownReports.span.fromPole.pole', 'teardownReports.span.toPole.pole', 'teardownReports.photos']);

        return response()->json([
            'report'         => $dailyReport,
            'missing_images' => $dailyReport->getMissingImages(),
        ]);
    }

    public function missingImages(SkycableDailyReport $dailyReport)
    {
        return response()->json([
            'daily_report_id' => $dailyReport->id,
            'missing_images'  => $dailyReport->getMissingImages(),
        ]);
    }

    public function subconReview(Request $request, SkycableDailyReport $dailyReport)
    {
        $data = $request->validate([
            'action'           => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string',
        ]);

        $user = $request->user();
        $old  = $dailyReport->toArray();

        if ($data['action'] === 'approve') {
            $dailyReport->update([
                'status'               => 'subcon_approved',
                'subcon_reviewed_by'   => $user->id,
                'subcon_reviewed_at'   => now(),
                'rejection_reason'     => null,
            ]);
        } else {
            $dailyReport->update([
                'status'             => 'rejected',
                'subcon_reviewed_by' => $user->id,
                'subcon_reviewed_at' => now(),
                'rejection_reason'   => $data['rejection_reason'],
            ]);
        }

        AuditLog::record('update', $dailyReport, $old, $dailyReport->toArray());

        return response()->json($dailyReport->fresh());
    }

    public function backendApprove(Request $request, SkycableDailyReport $dailyReport)
    {
        $data = $request->validate([
            'action'           => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string',
        ]);

        $user = $request->user();
        $old  = $dailyReport->toArray();

        if ($data['action'] === 'approve') {
            $dailyReport->update([
                'status'               => 'backend_approved',
                'backend_approved_by'  => $user->id,
                'backend_approved_at'  => now(),
                'rejection_reason'     => null,
            ]);
        } else {
            $dailyReport->update([
                'status'              => 'rejected',
                'backend_approved_by' => $user->id,
                'backend_approved_at' => now(),
                'rejection_reason'    => $data['rejection_reason'],
            ]);
        }

        AuditLog::record('update', $dailyReport, $old, $dailyReport->toArray());

        return response()->json($dailyReport->fresh());
    }
}
