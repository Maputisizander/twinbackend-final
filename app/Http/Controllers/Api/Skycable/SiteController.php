<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Concerns\CachesSkycableResponses;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SkycableSite;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    use CachesSkycableResponses;

    public function index(Request $request)
    {
        $query = SkycableSite::withCount('nodes')
            ->with('area')
            ->when($request->area_id, fn ($q) => $q->where('area_id', $request->area_id))
            ->orderBy('name');

        return $this->skycableCachedJson('sites.index', 300, fn () => $query->get(), $request);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'area_id'       => 'required|exists:skycable_areas,id',
            'name'          => 'required|string|max:255',
            'address'       => 'nullable|string|max:500',
            'barangay_code' => 'nullable|exists:psgc_barangays,code',
        ]);

        $site = SkycableSite::create($data);
        AuditLog::record('create', $site, null, $site->toArray());
        $this->bumpSkycableCacheVersion();

        return response()->json($site->load('area'), 201);
    }

    public function show(SkycableSite $site)
    {
        return $this->skycableCachedJson("sites.show.{$site->id}", 300, fn () => $site->load(['area', 'barangay', 'nodes.barangay']));
    }

    public function update(Request $request, SkycableSite $site)
    {
        $data = $request->validate([
            'area_id'       => 'sometimes|exists:skycable_areas,id',
            'name'          => 'sometimes|string|max:255',
            'address'       => 'sometimes|nullable|string|max:500',
            'barangay_code' => 'sometimes|nullable|exists:psgc_barangays,code',
        ]);

        $old = $site->toArray();
        $site->update($data);
        AuditLog::record('update', $site, $old, $site->fresh()->toArray());
        $this->bumpSkycableCacheVersion();

        return response()->json($site->fresh()->load('area'));
    }

    public function destroy(SkycableSite $site)
    {
        AuditLog::record('delete', $site, $site->toArray(), null);
        $site->delete();
        $this->bumpSkycableCacheVersion();

        return response()->json(['message' => 'Site deleted.']);
    }
}
