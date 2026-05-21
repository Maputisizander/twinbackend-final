<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pole;
use App\Models\SkycableArea;
use App\Models\SkycableNode;
use App\Models\SkycablePole;
use App\Models\SkycableSpan;
use App\Models\SkycableSpanSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsBuiltController extends Controller
{
    /**
     * POST /asbuilt/import
     *
     * Bulk JSON import from AsBuilt IQ sitemap reader.
     * Auth: X-AsBuilt-Key header (no user credentials needed).
     * Automatically marks the node report_type = full_report.
     *
     * ── Request body ─────────────────────────────────────────────────────────
     * {
     *   "node_id": 1,
     *   "poles": [
     *     { "pole_code": "PL-001", "latitude": 14.5995, "longitude": 120.9842 }
     *   ],
     *   "spans": [
     *     {
     *       "from_pole_code": "PL-001",
     *       "to_pole_code":   "PL-002",
     *       "strand_length":  50.5,
     *       "number_of_runs": 1,
     *       "components": {
     *         "node":         2,
     *         "amplifier":    1,
     *         "extender":     0,
     *         "tsc":          1,
     *         "powersupply":  0,
     *         "ps_housing":   0
     *       }
     *     }
     *   ]
     * }
     */
    public function import(Request $request)
    {
        // ── Accept either a JSON file upload OR a raw JSON body ───────────────
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if (!in_array($file->getMimeType(), ['application/json', 'text/plain', 'text/json'])) {
                return response()->json(['message' => 'Uploaded file must be a .json file.'], 422);
            }

            $decoded = json_decode(file_get_contents($file->getRealPath()), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'Invalid JSON file: ' . json_last_error_msg()], 422);
            }

            // Merge decoded JSON into the request so validation works normally
            $request->merge($decoded);
        }

        $request->validate([
            'node_id'                          => 'required|exists:skycable_nodes,id',
            'poles'                            => 'required|array|min:1',
            'poles.*.pole_code'                => 'required|string|max:100',
            'poles.*.latitude'                 => 'nullable|numeric|between:-90,90',
            'poles.*.longitude'                => 'nullable|numeric|between:-180,180',
            'spans'                            => 'nullable|array',
            'spans.*.from_pole_code'           => 'required|string',
            'spans.*.to_pole_code'             => 'required|string',
            'spans.*.strand_length'            => 'nullable|numeric|min:0',
            'spans.*.number_of_runs'           => 'nullable|integer|min:1',
            'spans.*.components'               => 'nullable|array',
            'spans.*.components.node'          => 'nullable|integer|min:0',
            'spans.*.components.amplifier'     => 'nullable|integer|min:0',
            'spans.*.components.extender'      => 'nullable|integer|min:0',
            'spans.*.components.tsc'           => 'nullable|integer|min:0',
            'spans.*.components.powersupply'   => 'nullable|integer|min:0',
            'spans.*.components.ps_housing'    => 'nullable|integer|min:0',
        ]);

        $node = SkycableNode::findOrFail($request->node_id);

        $result = DB::transaction(function () use ($request, $node) {

            $createdPoles   = [];
            $updatedPoles   = [];
            $createdSpans   = [];
            $updatedSpans   = [];
            $errors         = [];

            // pole_code → skycable_poles.id map for span lookup
            $poleCodeToSkId = [];

            // ── 1. Upsert Poles ──────────────────────────────────────────────
            $maxSeq = SkycablePole::where('node_id', $node->id)->max('sequence') ?? 0;

            foreach ($request->poles as $idx => $poleData) {
                $code = strtoupper(trim($poleData['pole_code']));
                if (!$code) { $errors[] = "poles[{$idx}]: pole_code is empty"; continue; }

                // Find or create in master poles table
                $pole = Pole::firstOrCreate(
                    ['pole_code' => $code],
                    [
                        'lat' => $poleData['latitude']  ?? null,
                        'lng' => $poleData['longitude'] ?? null,
                    ]
                );

                // Update GPS coordinates if now available and not already set
                if ((!$pole->lat || !$pole->lng) && !empty($poleData['latitude'])) {
                    $pole->update([
                        'lat' => $poleData['latitude'],
                        'lng' => $poleData['longitude'],
                    ]);
                }

                if ($pole->wasRecentlyCreated) {
                    $createdPoles[] = $code;
                } else {
                    $updatedPoles[] = $code;
                }

                // Enroll in this node via skycable_poles junction table
                $skycablePole = SkycablePole::firstOrCreate(
                    ['node_id' => $node->id, 'pole_id' => $pole->id],
                    ['sequence' => ++$maxSeq]
                );

                $poleCodeToSkId[$code] = $skycablePole->id;
            }

            // ── 2. Upsert Spans + Summaries ───────────────────────────────────
            foreach (($request->spans ?? []) as $idx => $spanData) {
                $fromCode = strtoupper(trim($spanData['from_pole_code']));
                $toCode   = strtoupper(trim($spanData['to_pole_code']));

                $fromSkId = $poleCodeToSkId[$fromCode] ?? null;
                $toSkId   = $poleCodeToSkId[$toCode]   ?? null;

                if (!$fromSkId) { $errors[] = "spans[{$idx}]: from_pole_code '{$fromCode}' not found in poles list"; continue; }
                if (!$toSkId)   { $errors[] = "spans[{$idx}]: to_pole_code '{$toCode}' not found in poles list"; continue; }
                if ($fromSkId === $toSkId) { $errors[] = "spans[{$idx}]: from and to poles must be different"; continue; }

                $strandLength  = $spanData['strand_length']  ?? null;
                $numberOfRuns  = $spanData['number_of_runs'] ?? 1;
                $expectedCable = $strandLength ? round($strandLength * $numberOfRuns, 2) : 0;

                // Idempotent: find or create by from+to within node
                $span = SkycableSpan::firstOrCreate(
                    [
                        'node_id'      => $node->id,
                        'from_pole_id' => $fromSkId,
                        'to_pole_id'   => $toSkId,
                    ],
                    [
                        'strand_length'  => $strandLength,
                        'number_of_runs' => $numberOfRuns,
                        'status'         => 'pending',
                    ]
                );

                if ($span->wasRecentlyCreated) {
                    $createdSpans[] = "{$fromCode} → {$toCode}";
                } else {
                    $span->update([
                        'strand_length'  => $strandLength  ?? $span->strand_length,
                        'number_of_runs' => $numberOfRuns  ?? $span->number_of_runs,
                    ]);
                    $updatedSpans[] = "{$fromCode} → {$toCode}";
                }

                // Upsert flat span summary (all expected components + cable)
                $comp = $spanData['components'] ?? [];
                SkycableSpanSummary::updateOrCreate(
                    ['span_id' => $span->id],
                    [
                        'node_id'              => $node->id,
                        'expected_cable'       => $expectedCable,
                        'expected_node'        => $comp['node']        ?? 0,
                        'expected_amplifier'   => $comp['amplifier']   ?? 0,
                        'expected_extender'    => $comp['extender']    ?? 0,
                        'expected_tsc'         => $comp['tsc']         ?? 0,
                        'expected_powersupply' => $comp['powersupply'] ?? 0,
                        'expected_ps_housing'  => $comp['ps_housing']  ?? 0,
                    ]
                );
            }

            // ── 3. Mark node as full_report ──────────────────────────────────
            $node->update(['report_type' => 'full_report']);

            AuditLog::record('asbuilt_import', $node, null, [
                'poles_created' => count($createdPoles),
                'poles_updated' => count($updatedPoles),
                'spans_created' => count($createdSpans),
                'spans_updated' => count($updatedSpans),
                'errors'        => $errors,
            ]);

            return [
                'node'          => [
                    'id'          => $node->id,
                    'name'        => $node->name,
                    'report_type' => 'full_report',
                ],
                'poles_created' => $createdPoles,
                'poles_updated' => $updatedPoles,
                'spans_created' => $createdSpans,
                'spans_updated' => $updatedSpans,
                'total_poles'   => count($createdPoles) + count($updatedPoles),
                'total_spans'   => count($createdSpans) + count($updatedSpans),
                'errors'        => $errors,
            ];
        });

        return response()->json([
            'message' => 'AsBuilt import completed.',
            'data'    => $result,
        ], 201);
    }

    /**
     * GET /asbuilt/sites
     * List all areas from skycable_areas — these ARE the "sites" in AsBuilt IQ.
     * (NCR, North Luzon, South Luzon, Visayas, Mindanao)
     */
    public function sites()
    {
        $areas = SkycableArea::withCount('nodes')
            ->orderBy('name')
            ->get()
            ->map(fn ($a) => [
                'id'         => $a->id,
                'name'       => $a->name,
                'node_count' => $a->nodes_count,
            ]);

        return response()->json($areas);
    }

    /**
     * GET /asbuilt/sites/{areaId}/nodes
     * List all nodes under a skycable_area (area = "site" in AsBuilt IQ).
     */
    public function nodesBySite(int $areaId)
    {
        $area = SkycableArea::findOrFail($areaId);

        $nodes = SkycableNode::where('area_id', $areaId)
            ->withCount('skycablePoles as pole_count')
            ->orderBy('name')
            ->get()
            ->map(fn ($n) => [
                'id'          => $n->id,
                'name'        => $n->name,
                'full_label'  => $n->full_label,
                'status'      => $n->status,
                'report_type' => $n->report_type,
                'pole_count'  => $n->pole_count,
            ]);

        return response()->json([
            'site'  => [
                'id'   => $area->id,
                'name' => $area->name,
            ],
            'nodes' => $nodes,
        ]);
    }

    /**
     * GET /asbuilt/node/{nodeId}
     * Returns the current state of a node: all poles + spans + summaries.
     */
    public function node(int $nodeId)
    {
        $node = SkycableNode::with([
            'area',
            'skycablePoles.pole',
            'spans.fromPole.pole',
            'spans.toPole.pole',
            'spans.summary',
        ])->findOrFail($nodeId);

        $poles = $node->skycablePoles->map(fn ($sp) => [
            'skycable_pole_id' => $sp->id,
            'pole_id'          => $sp->pole?->id,
            'pole_code'        => $sp->pole?->pole_code,
            'sequence'         => $sp->sequence,
            'latitude'         => $sp->pole?->lat,
            'longitude'        => $sp->pole?->lng,
            'status'           => $sp->status,
            'date_start'       => $sp->date_start,
            'finished_at'      => $sp->cleared_at,
            'duration'         => $sp->duration,
        ]);

        $spans = $node->spans->map(fn ($s) => [
            'span_id'         => $s->id,
            'from_pole_code'  => $s->fromPole?->pole?->pole_code,
            'to_pole_code'    => $s->toPole?->pole?->pole_code,
            'strand_length'   => $s->strand_length,
            'number_of_runs'  => $s->number_of_runs,
            'expected_cable'  => $s->summary?->expected_cable ?? 0,
            'status'          => $s->status,
            'components'      => [
                'node'        => $s->summary?->expected_node        ?? 0,
                'amplifier'   => $s->summary?->expected_amplifier   ?? 0,
                'extender'    => $s->summary?->expected_extender     ?? 0,
                'tsc'         => $s->summary?->expected_tsc          ?? 0,
                'powersupply' => $s->summary?->expected_powersupply  ?? 0,
                'ps_housing'  => $s->summary?->expected_ps_housing   ?? 0,
            ],
        ]);

        return response()->json([
            'node' => [
                'id'          => $node->id,
                'name'        => $node->name,
                'area'        => $node->area?->name,
                'report_type' => $node->report_type,
                'status'      => $node->status,
            ],
            'poles' => $poles,
            'spans' => $spans,
        ]);
    }
}
