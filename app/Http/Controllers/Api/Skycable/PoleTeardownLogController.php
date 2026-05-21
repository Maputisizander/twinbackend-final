<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Controllers\Controller;
use App\Models\SkycablePoleTeardownLog;
use App\Models\SkycablePole;
use Illuminate\Http\Request;

class PoleTeardownLogController extends Controller
{
    public function upsert(Request $request)
    {
        $data = $request->validate([
            'pole_id'         => 'required|exists:poles,id',
            'skycable_pole_id'=> 'nullable|exists:skycable_poles,id',
            'node_id'         => 'nullable|exists:skycable_nodes,id',
            'started_at'      => 'nullable|date',
            'finished_at'     => 'nullable|date',
            'status'          => 'nullable|in:pending,in_progress,completed',
        ]);

        $data['lineman_id'] = $request->user()->id;

        // One active log per pole — update if exists, create if not
        $log = SkycablePoleTeardownLog::where('pole_id', $data['pole_id'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest()
            ->first();

        if ($log) {
            $log->update($data);
        } else {
            $log = SkycablePoleTeardownLog::create($data);
        }

        return response()->json($log->fresh(), $log->wasRecentlyCreated ? 201 : 200);
    }

    public function byNode(Request $request, $nodeId)
    {
        $logs = SkycablePoleTeardownLog::with(['pole', 'lineman'])
            ->where('node_id', $nodeId)
            ->latest()
            ->get();

        return response()->json($logs);
    }

    public function byPole(Request $request, $poleId)
    {
        $log = SkycablePoleTeardownLog::where('pole_id', $poleId)
            ->latest()
            ->first();

        return response()->json($log);
    }
}
