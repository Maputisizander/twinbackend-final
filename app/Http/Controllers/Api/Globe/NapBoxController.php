<?php

namespace App\Http\Controllers\Api\Globe;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GlobeNapBox;
use App\Models\GlobeNapPort;
use Illuminate\Http\Request;

class NapBoxController extends Controller
{
    public function index(Request $request)
    {
        $query = GlobeNapBox::with(['pole.barangay'])
            ->when($request->pole_id, fn ($q) => $q->where('pole_id', $request->pole_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        return response()->json($query->paginate(50));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pole_id'    => 'required|exists:poles,id',
            'nap_code'   => 'required|string|unique:globe_nap_boxes,nap_code',
            'port_count' => 'required|in:8,12,16,32',
            'status'     => 'nullable|in:active,inactive,removed',
        ]);

        $napBox = GlobeNapBox::create($data);
        AuditLog::record('create', $napBox, null, $napBox->toArray());

        return response()->json($napBox->load('ports'), 201);
    }

    public function show(GlobeNapBox $napBox)
    {
        return response()->json($napBox->load(['pole.barangay', 'ports', 'surveys']));
    }

    public function update(Request $request, GlobeNapBox $napBox)
    {
        $data = $request->validate([
            'nap_code' => 'sometimes|string|unique:globe_nap_boxes,nap_code,' . $napBox->id,
            'status'   => 'sometimes|in:active,inactive,removed',
        ]);

        $old = $napBox->toArray();
        $napBox->update($data);
        AuditLog::record('update', $napBox, $old, $napBox->toArray());

        return response()->json($napBox);
    }

    public function ports(GlobeNapBox $napBox)
    {
        return response()->json($napBox->ports()->with('surveyedBy')->get());
    }

    public function updatePort(Request $request, GlobeNapBox $napBox, int $portNumber)
    {
        $data = $request->validate([
            'status'          => 'required|in:active,inactive,free',
            'subscriber_id'   => 'nullable|string',
            'subscriber_name' => 'nullable|string',
            'account_number'  => 'nullable|string',
        ]);

        $port = GlobeNapPort::where('nap_box_id', $napBox->id)
            ->where('port_number', $portNumber)
            ->firstOrFail();

        $old = $port->toArray();

        if ($data['status'] === 'free') {
            $data['subscriber_id']   = null;
            $data['subscriber_name'] = null;
            $data['account_number']  = null;
        }

        $data['updated_by']  = $request->user()->id;
        $data['surveyed_by'] = $request->user()->id;
        $data['surveyed_at'] = now();

        $port->update($data);
        AuditLog::record('update', $port, $old, $port->toArray());

        return response()->json($port);
    }
}
