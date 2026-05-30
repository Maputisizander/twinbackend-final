<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Concerns\CachesSkycableResponses;
use App\Http\Controllers\Controller;
use App\Models\SkycablePole;
use App\Models\SkycablePoleTeardownLog;
use Illuminate\Http\Request;

class PoleTeardownLogController extends Controller
{
    use CachesSkycableResponses;

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'pole_id' => 'required_without:skycable_pole_id|exists:poles,id',
            'skycable_pole_id' => 'nullable|exists:skycable_poles,id',
            'node_id' => 'nullable|exists:skycable_nodes,id',
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed',
        ]);

        if (! empty($data['skycable_pole_id'])) {
            $skycablePole = SkycablePole::findOrFail($data['skycable_pole_id']);
            $data['pole_id'] = $skycablePole->pole_id;
            $data['node_id'] = $skycablePole->node_id;
        } elseif (! empty($data['node_id'])) {
            $skycablePole = SkycablePole::where('node_id', $data['node_id'])
                ->where('pole_id', $data['pole_id'])
                ->first();

            if ($skycablePole) {
                $data['skycable_pole_id'] = $skycablePole->id;
            }
        }

        $data['lineman_id'] = $request->user()->id;

        // One active log per node-scoped pole — update if exists, create if not
        $log = SkycablePoleTeardownLog::query()
            ->when(
                ! empty($data['skycable_pole_id']),
                fn ($q) => $q->where('skycable_pole_id', $data['skycable_pole_id']),
                fn ($q) => $q->where('pole_id', $data['pole_id'])->when(
                    ! empty($data['node_id']),
                    fn ($nq) => $nq->where('node_id', $data['node_id'])
                )
            )
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest()
            ->first();

        if ($log) {
            $log->update($data);
        } else {
            $log = SkycablePoleTeardownLog::create($data);
        }

        $this->bumpSkycableCacheVersion();

        return response()->json($log->fresh(), $log->wasRecentlyCreated ? 201 : 200);
    }

    public function byNode(Request $request, $nodeId)
    {
        return $this->skycableCachedJson("poleTeardownLogs.byNode.{$nodeId}", 30, function () use ($nodeId) {
            return SkycablePoleTeardownLog::with(['pole', 'lineman'])
                ->where('node_id', $nodeId)
                ->latest()
                ->get();
        }, $request);
    }

    public function byPole(Request $request, $poleId)
    {
        return $this->skycableCachedJson("poleTeardownLogs.byPole.{$poleId}", 30, function () use ($poleId) {
            return SkycablePoleTeardownLog::where('pole_id', $poleId)
                ->latest()
                ->first();
        }, $request);
    }
}
