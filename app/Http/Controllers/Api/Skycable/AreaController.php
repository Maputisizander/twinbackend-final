<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Concerns\CachesSkycableResponses;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SkycableArea;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    use CachesSkycableResponses;

    public function index(Request $request)
    {
        $teamId = $request->team_id;

        $query = SkycableArea::withCount([
            'nodes'                      => fn ($q) => $teamId ? $q->where('team_id', $teamId) : $q,
            'nodes as pending_count'     => fn ($q) => $q->where('status', 'pending')    ->when($teamId, fn ($q) => $q->where('team_id', $teamId)),
            'nodes as in_progress_count' => fn ($q) => $q->where('status', 'in_progress')->when($teamId, fn ($q) => $q->where('team_id', $teamId)),
            'nodes as completed_count'   => fn ($q) => $q->where('status', 'completed')  ->when($teamId, fn ($q) => $q->where('team_id', $teamId)),
        ])->orderBy('name');

        // When filtering by team, only return areas that have at least one node for that team
        if ($teamId) {
            $query->whereHas('nodes', fn ($q) => $q->where('team_id', $teamId));
        }

        return $this->skycableCachedJson('areas.index', 300, fn () => $query->get(), $request);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|unique:skycable_areas,name']);

        $area = SkycableArea::create($data);
        AuditLog::record('create', $area, null, $area->toArray());
        $this->bumpSkycableCacheVersion();

        return response()->json($area, 201);
    }

    public function show(SkycableArea $area)
    {
        return $this->skycableCachedJson("areas.show.{$area->id}", 300, fn () => $area->load('nodes'));
    }

    public function update(Request $request, SkycableArea $area)
    {
        $data = $request->validate(['name' => 'required|string|unique:skycable_areas,name,' . $area->id]);

        $old = $area->toArray();
        $area->update($data);
        AuditLog::record('update', $area, $old, $area->toArray());
        $this->bumpSkycableCacheVersion();

        return response()->json($area);
    }

    public function destroy(SkycableArea $area)
    {
        AuditLog::record('delete', $area, $area->toArray(), null);
        $area->delete();
        $this->bumpSkycableCacheVersion();

        return response()->json(['message' => 'Area deleted.']);
    }
}
