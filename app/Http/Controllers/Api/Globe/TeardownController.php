<?php

namespace App\Http\Controllers\Api\Globe;

use App\Http\Concerns\StoresPhotos;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GlobeTeardownReport;
use App\Models\GlobeTeardownReportSlot;
use App\Models\GlobeTicket;
use App\Models\PoleCableSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeardownController extends Controller
{
    use StoresPhotos;
    public function store(Request $request, GlobeTicket $ticket)
    {
        if ($ticket->teardownReport) {
            return response()->json(['message' => 'Teardown report already exists for this ticket.'], 422);
        }

        $data = $request->validate([
            'wire_status'        => 'required|in:removed,partially_removed,unable_to_remove',
            'teardown_date'      => 'required|date',
            'before_photo'       => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            'after_photo'        => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            'pole_tag_photo'     => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            'slots'              => 'nullable|array',
            'slots.*.pole_id'    => 'required|exists:poles,id',
            'slots.*.pole_cable_slot_id' => 'required|exists:pole_cable_slots,id',
            'slots.*.slot_label' => 'required|string',
            'offline_mode'       => 'nullable|boolean',
            'captured_at_device' => 'nullable|date',
            'captured_lat'       => 'nullable|numeric',
            'captured_lng'       => 'nullable|numeric',
        ]);

        $report = DB::transaction(function () use ($request, $ticket, $data) {
            $photoFields = ['before_photo', 'after_photo', 'pole_tag_photo'];
            foreach ($photoFields as $field) {
                if ($request->hasFile($field)) {
                    $data[$field] = BaseAuthController::storePhoto($request->file($field), 'globe-teardown');
                }
            }

            $slots = $data['slots'] ?? [];
            unset($data['slots']);

            $report = GlobeTeardownReport::create(array_merge($data, [
                'ticket_id'          => $ticket->id,
                'lineman_id'         => $request->user()->id,
                'status'             => 'submitted',
                'received_at_server' => now(),
            ]));

            foreach ($slots as $slot) {
                GlobeTeardownReportSlot::create(array_merge($slot, ['teardown_report_id' => $report->id]));
                PoleCableSlot::where('id', $slot['pole_cable_slot_id'])
                    ->update(['status' => 'pending_teardown']);
            }

            $ticket->update(['status' => 'for_approval']);
            AuditLog::record('create', $report, null, $report->toArray());

            return $report;
        });

        return response()->json($report->load('slots'), 201);
    }

    public function show(GlobeTeardownReport $teardownReport)
    {
        return response()->json($teardownReport->load(['ticket.pole', 'lineman', 'slots.pole', 'slots.cableSlot']));
    }

    public function approve(Request $request, GlobeTeardownReport $teardownReport)
    {
        $data = $request->validate([
            'action'           => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string',
        ]);

        $old  = $teardownReport->toArray();
        $user = $request->user();

        DB::transaction(function () use ($teardownReport, $data, $user) {
            if ($data['action'] === 'approve') {
                $teardownReport->update([
                    'status'      => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                foreach ($teardownReport->slots as $slot) {
                    PoleCableSlot::where('id', $slot->pole_cable_slot_id)
                        ->update(['status' => 'free', 'occupied_by' => null]);
                }

                $teardownReport->ticket->update(['status' => 'completed']);
            } else {
                $teardownReport->update([
                    'status'           => 'rejected',
                    'approved_by'      => $user->id,
                    'approved_at'      => now(),
                    'rejection_reason' => $data['rejection_reason'],
                ]);
                $teardownReport->ticket->update(['status' => 'rejected']);
            }
        });

        AuditLog::record('update', $teardownReport, $old, $teardownReport->toArray());

        return response()->json($teardownReport->fresh());
    }
}
