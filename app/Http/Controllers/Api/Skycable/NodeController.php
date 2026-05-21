<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SkycableNode;
use App\Models\SkycableSpan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NodeController extends Controller
{
    public function index(Request $request)
    {
        $hasFilter = $request->hasAny(['area_id', 'site_id', 'subcontractor_id', 'team_id', 'status']);
        $page      = $request->page ?? 1;
        $perPage   = min((int) ($request->per_page ?? 50), 200);

        $fetch = function () use ($request, $perPage) {
            $query = SkycableNode::with(['area', 'site', 'subcontractor', 'team', 'barangay.city.province.region', 'latestDailyReport'])
                ->withCount('spans')
                ->withSum('spanSummaries', 'expected_cable')
                ->withSum('spanSummaries', 'actual_cable')
                ->withSum('spanSummaries', 'actual_node')
                ->withSum('spanSummaries', 'actual_amplifier')
                ->withSum('spanSummaries', 'actual_extender')
                ->withSum('spanSummaries', 'actual_tsc')
                ->when($request->area_id,          fn ($q) => $q->where('area_id',          $request->area_id))
                ->when($request->site_id,          fn ($q) => $q->where('site_id',          $request->site_id))
                ->when($request->subcontractor_id, fn ($q) => $q->where('subcontractor_id', $request->subcontractor_id))
                ->when($request->team_id,          fn ($q) => $q->where('team_id',          $request->team_id))
                ->when($request->status,           fn ($q) => $q->where('status',           $request->status));

            $paginated = $query->orderBy('full_label')->paginate($perPage);

            $paginated->getCollection()->transform(function ($node) {
                if (!$node->region && $node->barangay) {
                    $node->region        = optional(optional(optional($node->barangay->city)->province)->region)->name;
                    $node->province      = optional(optional($node->barangay->city)->province)->name;
                    $node->city          = optional($node->barangay->city)->name;
                    $node->barangay_name = $node->barangay->name;
                }
                return $node;
            });

            return $paginated;
        };

        // Cache unfiltered calls for 2 minutes — node list rarely changes mid-session
        $result = $hasFilter
            ? $fetch()
            : Cache::remember("nodes_index_{$perPage}_p{$page}", 120, $fetch);

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'area_id'          => 'required|exists:skycable_areas,id',
            'site_id'          => 'nullable|exists:skycable_sites,id',
            'barangay_code'    => 'nullable|string|max:20',
            'subcontractor_id' => 'nullable|exists:subcontractors,id',
            'team_id'          => 'nullable|exists:teams,id',
            'name'             => 'required|string|max:255',
            'status'           => 'nullable|in:pending,in_progress,completed',
            'report_type'      => 'nullable|in:full_report,pole_report',
            'data_source'      => 'nullable|in:manual,json_import,ai_scanner',
            'expected_cable_meters' => 'nullable|numeric|min:0',
            'expected_cable'   => 'nullable|numeric|min:0',
            'expected_nodes'   => 'nullable|integer|min:0',
            'expected_amplifier' => 'nullable|integer|min:0',
            'expected_extender' => 'nullable|integer|min:0',
            'expected_tsc'     => 'nullable|integer|min:0',
            'region'           => 'nullable|string|max:255',
            'province'         => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:255',
            'barangay_name'    => 'nullable|string|max:255',
        ]);

        // Coerce nullable numeric fields to 0 so NOT NULL columns never receive null
        $data['expected_cable']     = $data['expected_cable']     ?? $data['expected_cable_meters'] ?? 0;
        $data['expected_nodes']     = $data['expected_nodes']     ?? 0;
        $data['expected_amplifier'] = $data['expected_amplifier'] ?? 0;
        $data['expected_extender']  = $data['expected_extender']  ?? 0;
        $data['expected_tsc']       = $data['expected_tsc']       ?? 0;
        unset($data['expected_cable_meters']); // alias — not a real column

        $node = SkycableNode::create($data);
        AuditLog::record('create', $node, null, $node->toArray());

        return response()->json($node->load('area'), 201);
    }

    public function show(SkycableNode $node)
    {
        $node->load(['area', 'barangay', 'subcontractor', 'team', 'skycablePoles.pole', 'spans']);
        
        $node->loadSum('spanSummaries', 'expected_node');
        $node->loadSum('spanSummaries', 'expected_amplifier');
        $node->loadSum('spanSummaries', 'expected_extender');
        $node->loadSum('spanSummaries', 'expected_tsc');
        $node->loadSum('spanSummaries', 'expected_powersupply');
        $node->loadSum('spanSummaries', 'expected_ps_housing');

        $node->loadSum('spanSummaries', 'actual_node');
        $node->loadSum('spanSummaries', 'actual_amplifier');
        $node->loadSum('spanSummaries', 'actual_extender');
        $node->loadSum('spanSummaries', 'actual_tsc');
        $node->loadSum('spanSummaries', 'actual_powersupply');
        $node->loadSum('spanSummaries', 'actual_ps_housing');

        return response()->json($node);
    }

    public function update(Request $request, SkycableNode $node)
    {
        $data = $request->validate([
            'area_id'             => 'sometimes|exists:skycable_areas,id',
            'site_id'             => 'sometimes|nullable|exists:skycable_sites,id',
            'subcontractor_id'    => 'sometimes|nullable|exists:subcontractors,id',
            'team_id'             => 'sometimes|nullable|exists:teams,id',
            'name'                => 'sometimes|string|max:255',
            'status'              => 'sometimes|in:pending,in_progress,completed',
            'report_type'         => 'sometimes|nullable|in:full_report,pole_report',
            'expected_cable_meters' => 'sometimes|nullable|numeric|min:0',
            'barangay_code'       => 'sometimes|nullable|string|max:20',
            'region'              => 'sometimes|nullable|string|max:255',
            'province'            => 'sometimes|nullable|string|max:255',
            'city'                => 'sometimes|nullable|string|max:255',
            'barangay_name'       => 'sometimes|nullable|string|max:255',
            'lat'                 => 'sometimes|nullable|numeric|between:-90,90',
            'lng'                 => 'sometimes|nullable|numeric|between:-180,180',
            // Teardown session fields
            'date_start'          => 'sometimes|nullable|string',
            'due_date'            => 'sometimes|nullable|string',
            'date_finished'       => 'sometimes|nullable|string',
            'expected_cable'      => 'sometimes|nullable|numeric|min:0',
            'expected_nodes'      => 'sometimes|nullable|integer|min:0',
            'expected_amplifier'  => 'sometimes|nullable|integer|min:0',
            'expected_extender'   => 'sometimes|nullable|integer|min:0',
            'expected_tsc'        => 'sometimes|nullable|integer|min:0',
            'expected_powersupply' => 'sometimes|nullable|integer|min:0',
            'expected_ps_housing' => 'sometimes|nullable|integer|min:0',
            'actual_node'         => 'sometimes|nullable|integer|min:0',
            'actual_amplifier'    => 'sometimes|nullable|integer|min:0',
            'actual_extender'     => 'sometimes|nullable|integer|min:0',
            'actual_tsc'          => 'sometimes|nullable|integer|min:0',
            'actual_powersupply'  => 'sometimes|nullable|integer|min:0',
            'actual_ps_housing'   => 'sometimes|nullable|integer|min:0',
            'actual_cable'        => 'sometimes|nullable|numeric|min:0',
            'progress_percentage' => 'sometimes|numeric|min:0|max:100',
        ]);

        // Auto-set status based on teardown lifecycle
        if (isset($data['date_start']) && $data['date_start'] && $node->status === 'pending') {
            $data['status'] = 'in_progress';
        }
        if (isset($data['date_finished']) && $data['date_finished']) {
            $data['status'] = 'completed';
        }
        if (isset($data['progress_percentage']) && (float) $data['progress_percentage'] >= 100) {
            $data['status'] = 'completed';
        }

        $old = $node->toArray();
        $node->update($data);
        AuditLog::record('update', $node, $old, $node->fresh()->toArray());

        // Clear all node list caches so web dashboard sees fresh status
        Cache::forget('nodes_index_50_p1');
        Cache::forget('nodes_index_100_p1');
        Cache::forget('nodes_index_200_p1');

        return response()->json($node->fresh()->load('area'));
    }

    public function destroy(SkycableNode $node)
    {
        AuditLog::record('delete', $node, $node->toArray(), null);
        $node->delete();

        return response()->json(['message' => 'Node deleted.']);
    }

    public function polePhotos(SkycableNode $node)
    {
        // Get all spans for this node, with teardown photos and pole info
        $spans = SkycableSpan::with([
            'fromPole.pole',
            'toPole.pole',
            'teardownReports.photos',
        ])
        ->where('node_id', $node->id)
        ->get();

        $rows = [];

        foreach ($spans as $span) {
            // Collect all approved/submitted teardown photos for this span
            $photos = $span->teardownReports
                ->whereIn('status', ['submitted', 'subcon_approved', 'backend_approved'])
                ->flatMap(fn ($r) => $r->photos)
                ->keyBy('photo_type');

            // From-pole
            if ($span->fromPole) {
                $poleId = $span->fromPole->id;
                if (!isset($rows[$poleId])) {
                    $rows[$poleId] = [
                        'skycable_pole_id' => $span->fromPole->id,
                        'sequence'         => $span->fromPole->sequence,
                        'pole_code'        => $span->fromPole->pole->pole_code ?? null,
                        'before'           => null,
                        'after'            => null,
                        'pole_tag'         => null,
                    ];
                }
                if (!$rows[$poleId]['before']    && isset($photos['from_before']))    $rows[$poleId]['before']   = $photos['from_before']->image_path;
                if (!$rows[$poleId]['after']     && isset($photos['from_after']))     $rows[$poleId]['after']    = $photos['from_after']->image_path;
                if (!$rows[$poleId]['pole_tag']  && isset($photos['from_pole_tag']))  $rows[$poleId]['pole_tag'] = $photos['from_pole_tag']->image_path;
            }

            // To-pole
            if ($span->toPole) {
                $poleId = $span->toPole->id;
                if (!isset($rows[$poleId])) {
                    $rows[$poleId] = [
                        'skycable_pole_id' => $span->toPole->id,
                        'sequence'         => $span->toPole->sequence,
                        'pole_code'        => $span->toPole->pole->pole_code ?? null,
                        'before'           => null,
                        'after'            => null,
                        'pole_tag'         => null,
                    ];
                }
                if (!$rows[$poleId]['before']   && isset($photos['to_before']))    $rows[$poleId]['before']   = $photos['to_before']->image_path;
                if (!$rows[$poleId]['after']    && isset($photos['to_after']))      $rows[$poleId]['after']    = $photos['to_after']->image_path;
                if (!$rows[$poleId]['pole_tag'] && isset($photos['to_pole_tag']))   $rows[$poleId]['pole_tag'] = $photos['to_pole_tag']->image_path;
            }
        }

        // Sort by sequence, reset keys
        $sorted = collect(array_values($rows))->sortBy('sequence')->values();

        return response()->json($sorted);
    }

    public function importJson(Request $request, SkycableNode $node)
    {
        $request->validate([
            'poles'   => 'required|array',
            'poles.*' => 'exists:poles,id',
        ]);

        $sequence = 1;
        foreach ($request->poles as $poleId) {
            $node->skycablePoles()->firstOrCreate(
                ['pole_id' => $poleId],
                ['sequence' => $sequence++]
            );
        }

        $old = $node->toArray();
        $node->update(['data_source' => 'json_import']);
        AuditLog::record('update', $node, $old, $node->fresh()->toArray());

        return response()->json(['message' => 'Poles imported.', 'count' => count($request->poles)]);
    }

    public function mapPins(Request $request)
    {
        $query = SkycableNode::with(['area', 'site', 'team', 'barangay.city.province', 'skycablePoles.pole']);

        if ($request->subcontractor_id) {
            $query->where('subcontractor_id', $request->subcontractor_id);
        }

        $nodes = $query->get()->map(function ($node) {
            // Find lat/lng: use node's own lat/lng if available
            $lat = $node->lat ? (float) $node->lat : null;
            $lng = $node->lng ? (float) $node->lng : null;

            // Fallback: average of its poles' lat/lng
            if (!$lat || !$lng) {
                $validPoles = $node->skycablePoles
                    ->map(fn($sp) => $sp->pole)
                    ->filter(fn($p) => $p && $p->lat && $p->lng);

                if ($validPoles->count() > 0) {
                    $lat = (float) $validPoles->avg('lat');
                    $lng = (float) $validPoles->avg('lng');
                    // Save to node so future loads are extremely fast
                    $node->update(['lat' => $lat, 'lng' => $lng]);
                }
            }

            if (!$lat || !$lng) {
                return null;
            }

            $city = optional($node->barangay->city ?? null)->name;
            $province = optional(optional($node->barangay->city ?? null)->province ?? null)->name;
            if (!$city && $node->city) {
                $city = $node->city;
            }
            if (!$province && $node->province) {
                $province = $node->province;
            }

            return [
                'id'         => $node->id,
                'node_id'    => $node->label ?? ($node->name),
                'node_name'  => $node->name,
                'city'       => $city,
                'province'   => $province,
                'sites'      => optional($node->site)->name ?? optional($node->area)->name,
                'team'       => optional($node->team)->name,
                'status'     => strtoupper($node->status ?? 'pending'),
                'progress'   => (float) ($node->progress_percentage ?? 0),
                'lat'        => $lat,
                'lng'        => $lng,
                'pole_count' => $node->skycablePoles->count(),
            ];
        })
        ->filter()
        ->values();

        return response()->json($nodes);
    }
}
