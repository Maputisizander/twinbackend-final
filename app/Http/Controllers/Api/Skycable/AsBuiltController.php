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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AsBuiltController extends Controller
{
    /**
     * POST /asbuilt/import
     *
     * node_id  = the VARCHAR identifier string (e.g. "TY1401") stored in skycable_nodes.node_id
     * node_name = human name like "MONTEVISTA SUBD." — saved to skycable_nodes.name
     * region / province / city = passed in payload, saved to node
     * poles[].barangay_name = optional per pole; node.barangay_name = majority value
     * source_file is set to "asbuilt" automatically
     */
    public function import(Request $request)
    {
        // ── Accept either a JSON file upload OR a raw JSON body ───────────────
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if (! in_array($file->getMimeType(), ['application/json', 'text/plain', 'text/json'])) {
                return response()->json(['message' => 'Uploaded file must be a .json file.'], 422);
            }

            $decoded = json_decode(file_get_contents($file->getRealPath()), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'Invalid JSON file: '.json_last_error_msg()], 422);
            }

            $request->merge($decoded);
        }

        $request->validate([
            'node_id'                      => 'required|string|max:100',
            'node_name'                    => 'required|string|max:255',
            'area_id'                      => 'required|exists:skycable_areas,id',
            'region'                       => 'nullable|string|max:255',
            'province'                     => 'nullable|string|max:255',
            'city'                         => 'nullable|string|max:255',
            'poles'                        => 'required|array|min:1',
            'poles.*.pole_code'            => 'required|string|max:100',
            'poles.*.latitude'             => 'nullable|numeric|between:-90,90',
            'poles.*.longitude'            => 'nullable|numeric|between:-180,180',
            'poles.*.barangay_name'        => 'nullable|string|max:255',
            'spans'                        => 'nullable|array',
            'spans.*.from_pole_code'       => 'required|string',
            'spans.*.to_pole_code'         => 'required|string',
            'spans.*.strand_length'        => 'nullable|numeric|min:0',
            'spans.*.number_of_runs'       => 'nullable|integer|min:1',
            'spans.*.components'           => 'nullable|array',
            'spans.*.components.node'      => 'nullable|integer|min:0',
            'spans.*.components.amplifier' => 'nullable|integer|min:0',
            'spans.*.components.extender'  => 'nullable|integer|min:0',
            'spans.*.components.tsc'       => 'nullable|integer|min:0',
            'spans.*.components.powersupply' => 'nullable|integer|min:0',
            'spans.*.components.ps_housing'  => 'nullable|integer|min:0',
        ]);

        // ── Find or create node by node_id string ────────────────────────────
        $node = SkycableNode::firstOrCreate(
            ['node_id' => $request->node_id, 'area_id' => $request->area_id],
            [
                'name'        => $request->node_name,
                'status'      => 'pending',
                'source_file' => 'asbuilt',
            ]
        );

        $result = DB::transaction(function () use ($request, $node) {

            $createdPoles  = [];
            $updatedPoles  = [];
            $createdSpans  = [];
            $updatedSpans  = [];
            $errors        = [];

            $poleCodeToEntries  = [];
            $usedSkycablePoleIds = [];
            $importedCoordinates = [];
            $barangayNames       = [];

            // ── 1. Upsert Poles ──────────────────────────────────────────────
            $maxSeq = SkycablePole::where('node_id', $node->id)->max('sequence') ?? 0;

            // Pre-count pole_code occurrences to detect indexed names (NPT-1, NPT-2…)
            // pole_label = base name before the trailing "-N" suffix
            // label_index = the N suffix if present, null if the name is unique
            $poleCodeCounts = array_count_values(
                array_map(fn($p) => strtoupper(trim($p['pole_code'] ?? '')), $request->poles)
            );

            foreach ($request->poles as $idx => $poleData) {
                $code = strtoupper(trim($poleData['pole_code']));
                if (! $code) {
                    $errors[] = "poles[{$idx}]: pole_code is empty";
                    continue;
                }

                // Derive pole_label and label_index from the code.
                // Pattern: "NPT-2" → label="NPT", index=2
                //           "NPT"   → label="NPT", index=null (unique)
                //           "TY-001"→ label="TY-001", index=null (not an indexed duplicate)
                $poleLabel = $code;
                $labelIndex = null;
                if (preg_match('/^(.+)-(\d+)$/', $code, $m)) {
                    $baseCode = $m[1];
                    $suffix   = (int) $m[2];
                    // Only treat as indexed if the base name alone also appears
                    // OR other "-N" variants appear (i.e. it looks intentionally indexed)
                    $siblingPattern = $baseCode . '-';
                    $hasSiblings = collect(array_keys($poleCodeCounts))
                        ->contains(fn($c) => str_starts_with($c, $siblingPattern) && $c !== $code);
                    if ($hasSiblings || isset($poleCodeCounts[$baseCode])) {
                        $poleLabel  = $baseCode;
                        $labelIndex = $suffix;
                    }
                }

                $lat       = $this->normalizeCoordinate($poleData['latitude'] ?? null);
                $lng       = $this->normalizeCoordinate($poleData['longitude'] ?? null);
                $poleIndex = isset($poleData['pole_index']) ? (int) $poleData['pole_index'] : null;

                if (! empty($poleData['barangay_name'])) {
                    $barangayNames[] = trim($poleData['barangay_name']);
                }

                $skycablePole = $this->matchExistingNodePole(
                    $node->id, $code, $lat, $lng, $usedSkycablePoleIds
                );

                if ($skycablePole) {
                    $pole = $this->preparePoleForNodeImport($skycablePole, $code, $lat, $lng);

                    // Update label/index if not yet set
                    if (! $pole->pole_label) {
                        $pole->update(['pole_label' => $poleLabel, 'label_index' => $labelIndex]);
                    }

                    $spUpdate = [];
                    if ($skycablePole->pole_id !== $pole->id) $spUpdate['pole_id'] = $pole->id;
                    if ($poleIndex !== null) $spUpdate['sequence'] = $poleIndex;
                    if ($spUpdate) $skycablePole->update($spUpdate);

                    $updatedPoles[] = $code;
                } else {
                    $pole = Pole::create([
                        'pole_code'   => $code,
                        'pole_label'  => $poleLabel,
                        'label_index' => $labelIndex,
                        'lat'         => $lat,
                        'lng'         => $lng,
                    ]);

                    $skycablePole = SkycablePole::create([
                        'node_id'  => $node->id,
                        'pole_id'  => $pole->id,
                        'sequence' => $poleIndex ?? ++$maxSeq,
                    ]);

                    $createdPoles[] = $code;
                }

                $skycablePole->setRelation('pole', $pole);
                $usedSkycablePoleIds[] = $skycablePole->id;

                if ($lat !== null && $lng !== null) {
                    $importedCoordinates[] = ['lat' => $lat, 'lng' => $lng];
                }

                $poleCodeToEntries[$code][] = [
                    'id'           => $skycablePole->id,
                    'latitude'     => $lat,
                    'longitude'    => $lng,
                    'source_index' => $idx,           // 0-based array position (fallback)
                    'pole_index'   => $poleIndex,     // explicit pole_index from payload (preferred)
                ];
            }

            // ── 2. Upsert Spans + Summaries ───────────────────────────────────
            foreach (($request->spans ?? []) as $idx => $spanData) {
                $fromCode = strtoupper(trim($spanData['from_pole_code']));
                $toCode   = strtoupper(trim($spanData['to_pole_code']));

                $fromEntry = $this->resolveImportedPoleEntry($poleCodeToEntries, $fromCode, $spanData, 'from');
                $toEntry   = $this->resolveImportedPoleEntry($poleCodeToEntries, $toCode,   $spanData, 'to');

                $fromSkId = $fromEntry['id'] ?? null;
                $toSkId   = $toEntry['id']   ?? null;

                if (! $fromSkId) {
                    $errors[] = "spans[{$idx}]: from_pole_code '{$fromCode}' not found in poles list";
                    continue;
                }
                if (! $toSkId) {
                    $errors[] = "spans[{$idx}]: to_pole_code '{$toCode}' not found in poles list";
                    continue;
                }
                if ($fromSkId === $toSkId) {
                    $errors[] = "spans[{$idx}]: from and to poles must be different";
                    continue;
                }

                $strandLength  = $spanData['strand_length']  ?? null;
                $numberOfRuns  = $spanData['number_of_runs'] ?? 1;
                $expectedCable = $strandLength ? round($strandLength * $numberOfRuns, 2) : 0;

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

                $comp = $spanData['components'] ?? [];
                SkycableSpanSummary::updateOrCreate(
                    ['span_id' => $span->id],
                    [
                        'node_id'            => $node->id,
                        'expected_cable'     => $expectedCable,
                        'expected_node'      => $comp['node']        ?? 0,
                        'expected_amplifier' => $comp['amplifier']   ?? 0,
                        'expected_extender'  => $comp['extender']    ?? 0,
                        'expected_tsc'       => $comp['tsc']         ?? 0,
                        'expected_powersupply' => $comp['powersupply'] ?? 0,
                        'expected_ps_housing'  => $comp['ps_housing']  ?? 0,
                    ]
                );
            }

            // ── 3. Majority barangay from poles ───────────────────────────────
            $majorityBarangay = collect($barangayNames)
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first();

            // ── 4. Update node metadata ───────────────────────────────────────
            $nodeUpdate = [
                'name'        => $request->node_name,
                'report_type' => 'full_report',
                'data_source' => 'json_import',
                'source_file' => 'asbuilt',
            ];

            if ($request->filled('region'))   $nodeUpdate['region']   = $request->region;
            if ($request->filled('province'))  $nodeUpdate['province'] = $request->province;
            if ($request->filled('city'))      $nodeUpdate['city']     = $request->city;
            if ($majorityBarangay)             $nodeUpdate['barangay_name'] = $majorityBarangay;

            if (count($importedCoordinates) > 0) {
                $nodeUpdate['lat'] = collect($importedCoordinates)->avg('lat');
                $nodeUpdate['lng'] = collect($importedCoordinates)->avg('lng');
            }

            $node->update($nodeUpdate);
            $this->clearSkycableMapCaches();

            AuditLog::record('asbuilt_import', $node, null, [
                'poles_created' => count($createdPoles),
                'poles_updated' => count($updatedPoles),
                'spans_created' => count($createdSpans),
                'spans_updated' => count($updatedSpans),
                'errors'        => $errors,
            ]);

            return [
                'node' => [
                    'id'           => $node->id,
                    'node_id'      => $node->node_id,
                    'name'         => $node->name,
                    'region'       => $node->region,
                    'province'     => $node->province,
                    'city'         => $node->city,
                    'barangay_name' => $node->barangay_name,
                    'report_type'  => 'full_report',
                    'source_file'  => 'asbuilt',
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

        // Warm cache immediately — next GET /skycable/nodes will be a Redis HIT
        \App\Services\CacheWarmer::nodes($request->area_id);
        \App\Services\CacheWarmer::spans($result['node']['id'] ?? 0);

        return response()->json([
            'message' => 'AsBuilt import completed.',
            'data'    => $result,
        ], 201);
    }

    /**
     * GET /asbuilt/sites
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
                'node_id'     => $n->node_id,
                'name'        => $n->name,
                'full_label'  => $n->full_label,
                'status'      => $n->status,
                'report_type' => $n->report_type,
                'source_file' => $n->source_file,
                'pole_count'  => $n->pole_count,
            ]);

        return response()->json([
            'site'  => ['id' => $area->id, 'name' => $area->name],
            'nodes' => $nodes,
        ]);
    }

    /**
     * GET /asbuilt/node/{nodeId}
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
            'span_id'        => $s->id,
            'from_pole_code' => $s->fromPole?->pole?->pole_code,
            'to_pole_code'   => $s->toPole?->pole?->pole_code,
            'strand_length'  => $s->strand_length,
            'number_of_runs' => $s->number_of_runs,
            'expected_cable' => $s->summary?->expected_cable ?? 0,
            'status'         => $s->status,
            'components'     => [
                'node'        => $s->summary?->expected_node        ?? 0,
                'amplifier'   => $s->summary?->expected_amplifier   ?? 0,
                'extender'    => $s->summary?->expected_extender    ?? 0,
                'tsc'         => $s->summary?->expected_tsc         ?? 0,
                'powersupply' => $s->summary?->expected_powersupply ?? 0,
                'ps_housing'  => $s->summary?->expected_ps_housing  ?? 0,
            ],
        ]);

        return response()->json([
            'node' => [
                'id'          => $node->id,
                'node_id'     => $node->node_id,
                'name'        => $node->name,
                'area'        => $node->area?->name,
                'region'      => $node->region,
                'province'    => $node->province,
                'city'        => $node->city,
                'barangay'    => $node->barangay_name,
                'report_type' => $node->report_type,
                'source_file' => $node->source_file,
                'status'      => $node->status,
            ],
            'poles' => $poles,
            'spans' => $spans,
        ]);
    }

    /**
     * POST /asbuilt/import-by-sequence
     *
     * Sequence-first import. Every pole carries a `sequence` (1-based integer).
     * Every span uses `from_sequence` / `to_sequence` to reference poles — no
     * pole_code disambiguation needed, no duplicate-code confusion.
     *
     * Payload example:
     *   poles: [{ sequence:1, pole_code:"PL-001", lat:14.53, lng:121.10 }, ...]
     *   spans: [{ from_sequence:1, to_sequence:2, strand_length:50.5 }, ...]
     */
    public function importBySequence(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if (! in_array($file->getMimeType(), ['application/json', 'text/plain', 'text/json'])) {
                return response()->json(['message' => 'Uploaded file must be a .json file.'], 422);
            }
            $decoded = json_decode(file_get_contents($file->getRealPath()), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'Invalid JSON file: '.json_last_error_msg()], 422);
            }
            $request->merge($decoded);
        }

        $request->validate([
            'node_id'                       => 'required|string|max:100',
            'node_name'                     => 'required|string|max:255',
            'area_id'                       => 'required|exists:skycable_areas,id',
            'region'                        => 'nullable|string|max:255',
            'province'                      => 'nullable|string|max:255',
            'city'                          => 'nullable|string|max:255',
            'poles'                         => 'required|array|min:1',
            // pole_index is the preferred unique key (e.g. "NPT-1", "CV8-001")
            // sequence is accepted for backward compatibility
            'poles.*.pole_index'            => 'nullable|string|max:50',
            'poles.*.sequence'              => 'nullable|integer|min:1',
            'poles.*.pole_code'             => 'required|string|max:100',
            'poles.*.lat'                   => 'nullable|numeric|between:-90,90',
            'poles.*.lng'                   => 'nullable|numeric|between:-180,180',
            'poles.*.latitude'              => 'nullable|numeric|between:-90,90',
            'poles.*.longitude'             => 'nullable|numeric|between:-180,180',
            'poles.*.barangay_name'         => 'nullable|string|max:255',
            'spans'                         => 'nullable|array',
            // from_pole_index / to_pole_index are preferred (human-readable, no NPT confusion)
            // from_sequence / to_sequence accepted for backward compatibility
            'spans.*.from_pole_index'       => 'nullable|string|max:50',
            'spans.*.to_pole_index'         => 'nullable|string|max:50',
            'spans.*.from_sequence'         => 'nullable|integer|min:1',
            'spans.*.to_sequence'           => 'nullable|integer|min:1',
            'spans.*.strand_length'         => 'nullable|numeric|min:0',
            'spans.*.number_of_runs'        => 'nullable|integer|min:1',
            'spans.*.components'            => 'nullable|array',
            'spans.*.components.node'       => 'nullable|integer|min:0',
            'spans.*.components.amplifier'  => 'nullable|integer|min:0',
            'spans.*.components.extender'   => 'nullable|integer|min:0',
            'spans.*.components.tsc'        => 'nullable|integer|min:0',
            'spans.*.components.powersupply'=> 'nullable|integer|min:0',
            'spans.*.components.ps_housing' => 'nullable|integer|min:0',
        ]);

        // ── Resolve / create node ─────────────────────────────────────────────
        $node = SkycableNode::firstOrNew([
            'node_id' => strtoupper(trim($request->node_id)),
            'area_id' => $request->area_id,
        ]);
        $node->fill(array_filter([
            'name'        => trim($request->node_name),
            'region'      => $request->region   ? trim($request->region)   : null,
            'province'    => $request->province  ? trim($request->province) : null,
            'city'        => $request->city      ? trim($request->city)     : null,
            'report_type' => 'full_report',
            'source_file' => 'asbuilt',
        ], fn ($v) => $v !== null));
        $node->save();

        $result = DB::transaction(function () use ($request, $node) {

            $createdPoles  = [];
            $updatedPoles  = [];
            $createdSpans  = [];
            $updatedSpans  = [];
            $errors        = [];
            $barangayNames = [];

            // pole_index → skycable_pole id  (e.g. "NPT-1", "CV8-001")
            // sequence is the TEARDOWN ORDER assigned by lineman, NOT used for import.
            $indexMap = [];
            $seqMap   = []; // kept for backward compat only

            // ── 1. Upsert Poles ───────────────────────────────────────────────
            foreach ($request->poles as $idx => $poleData) {
                $poleIndex = isset($poleData['pole_index']) ? strtoupper(trim($poleData['pole_index'])) : null;
                $seq       = isset($poleData['sequence'])   ? (int) $poleData['sequence'] : null;
                $code      = strtoupper(trim($poleData['pole_code']));
                $lat       = $this->normalizeCoordinate($poleData['lat'] ?? $poleData['latitude'] ?? null);
                $lng       = $this->normalizeCoordinate($poleData['lng'] ?? $poleData['longitude'] ?? null);

                // At least one of pole_index or sequence must be present
                if (! $poleIndex && ! $seq) {
                    $errors[] = "poles[{$idx}]: either pole_index or sequence is required";
                    continue;
                }
                if (! $code) {
                    $errors[] = "poles[{$idx}]: pole_code is empty";
                    continue;
                }

                // Prevent duplicates within this import batch
                $lookupKey = $poleIndex ?? (string) $seq;
                if (isset($indexMap[$lookupKey])) {
                    $errors[] = "poles[{$idx}]: duplicate pole_index/sequence '{$lookupKey}'";
                    continue;
                }
                if (! empty($poleData['barangay_name'])) {
                    $barangayNames[] = trim($poleData['barangay_name']);
                }

                // Try to find existing pole by pole_index first, then sequence, then code
                $skycablePole = null;
                if ($poleIndex) {
                    $skycablePole = SkycablePole::with('pole')
                        ->where('node_id', $node->id)
                        ->where('pole_index', $poleIndex)
                        ->first();
                }
                if (! $skycablePole && $seq) {
                    $skycablePole = SkycablePole::with('pole')
                        ->where('node_id', $node->id)
                        ->where('sequence', $seq)
                        ->first();
                }
                if (! $skycablePole) {
                    $skycablePole = SkycablePole::with('pole')
                        ->where('node_id', $node->id)
                        ->whereHas('pole', fn ($q) => $q->where('pole_code', $code))
                        ->first();
                }

                if ($skycablePole) {
                    $pole = $skycablePole->pole;
                    if ($lat !== null && $lng !== null) {
                        $pole->update(['lat' => $lat, 'lng' => $lng]);
                    }
                    $skycablePole->update(array_filter([
                        'sequence'   => $seq ?? $skycablePole->sequence,
                        'pole_index' => $poleIndex,
                    ], fn ($v) => $v !== null));
                    $updatedPoles[] = $code;
                } else {
                    $pole = Pole::firstOrCreate(
                        ['pole_code' => $code],
                        ['lat' => $lat, 'lng' => $lng]
                    );
                    if ($lat !== null && $lng !== null && ($pole->lat !== $lat || $pole->lng !== $lng)) {
                        $pole->update(['lat' => $lat, 'lng' => $lng]);
                    }
                    $skycablePole = SkycablePole::create([
                        'node_id'    => $node->id,
                        'pole_id'    => $pole->id,
                        'sequence'   => $seq ?? ++$autoSeq,
                        'pole_index' => $poleIndex,
                    ]);
                    $createdPoles[] = $code;
                }

                // Register under BOTH pole_index AND sequence for span resolution
                $indexMap[$lookupKey] = $skycablePole->id;
                if ($poleIndex) $indexMap[$poleIndex] = $skycablePole->id;
                if ($seq)       $seqMap[$seq]         = $skycablePole->id;
            }

            // Auto-set node barangay_name from majority
            if ($barangayNames) {
                $counts = array_count_values($barangayNames);
                arsort($counts);
                $node->barangay_name = array_key_first($counts);
                $node->save();
            }

            // ── 2. Upsert Spans ───────────────────────────────────────────────
            foreach (($request->spans ?? []) as $idx => $spanData) {
                // Resolve from-pole: prefer pole_index, fall back to sequence
                $fromKey = $spanData['from_pole_index'] ?? null;
                if ($fromKey) $fromKey = strtoupper(trim($fromKey));
                if (! $fromKey && isset($spanData['from_sequence'])) {
                    $fromKey = (int) $spanData['from_sequence'];
                }

                // Resolve to-pole: prefer pole_index, fall back to sequence
                $toKey = $spanData['to_pole_index'] ?? null;
                if ($toKey) $toKey = strtoupper(trim($toKey));
                if (! $toKey && isset($spanData['to_sequence'])) {
                    $toKey = (int) $spanData['to_sequence'];
                }

                if ($fromKey === null) {
                    $errors[] = "spans[{$idx}]: from_pole_index or from_sequence is required";
                    continue;
                }
                if ($toKey === null) {
                    $errors[] = "spans[{$idx}]: to_pole_index or to_sequence is required";
                    continue;
                }

                if (! isset($indexMap[$fromKey])) {
                    $errors[] = "spans[{$idx}]: from '{$fromKey}' not found in poles list";
                    continue;
                }
                if (! isset($indexMap[$toKey])) {
                    $errors[] = "spans[{$idx}]: to '{$toKey}' not found in poles list";
                    continue;
                }
                if ($indexMap[$fromKey] === $indexMap[$toKey]) {
                    $errors[] = "spans[{$idx}]: from and to poles must be different";
                    continue;
                }

                $fromSkId = $indexMap[$fromKey];
                $toSkId   = $indexMap[$toKey];
                $strandLength  = isset($spanData['strand_length'])  ? (float) $spanData['strand_length']  : null;
                $numberOfRuns  = isset($spanData['number_of_runs']) ? max(1, (int) $spanData['number_of_runs']) : 1;
                $expectedCable = $strandLength !== null ? round($strandLength * $numberOfRuns, 4) : null;
                $components    = $spanData['components'] ?? [];

                $span = SkycableSpan::where('node_id', $node->id)
                    ->where('from_pole_id', $fromSkId)
                    ->where('to_pole_id',   $toSkId)
                    ->first();

                if ($span) {
                    $span->update(['node_id' => $node->id]);
                    $updatedSpans[] = "seq{$fromSeq}→seq{$toSeq}";
                } else {
                    $span = SkycableSpan::create([
                        'node_id'      => $node->id,
                        'from_pole_id' => $fromSkId,
                        'to_pole_id'   => $toSkId,
                    ]);
                    $createdSpans[] = "seq{$fromSeq}→seq{$toSeq}";
                }

                SkycableSpanSummary::updateOrCreate(
                    ['span_id' => $span->id],
                    [
                        'strand_length'  => $strandLength,
                        'number_of_runs' => $numberOfRuns,
                        'expected_cable' => $expectedCable,
                        'node_count'     => $components['node']        ?? 0,
                        'amplifier'      => $components['amplifier']   ?? 0,
                        'extender'       => $components['extender']    ?? 0,
                        'tsc'            => $components['tsc']         ?? 0,
                        'powersupply'    => $components['powersupply'] ?? 0,
                        'ps_housing'     => $components['ps_housing']  ?? 0,
                    ]
                );
            }

            AuditLog::record('create', $node, null, $node->toArray());
            $this->clearSkycableMapCaches();

            return [
                'node'          => $node->fresh(),
                'poles_created' => $createdPoles,
                'poles_updated' => $updatedPoles,
                'spans_created' => $createdSpans,
                'spans_updated' => $updatedSpans,
                'total_poles'   => count($createdPoles) + count($updatedPoles),
                'total_spans'   => count($createdSpans) + count($updatedSpans),
                'errors'        => $errors,
            ];
        });

        $n = $result['node'];

        return response()->json([
            'message' => 'AsBuilt sequence import completed.',
            'data'    => [
                'node' => [
                    'id'           => $n->id,
                    'node_id'      => $n->node_id,
                    'name'         => $n->name,
                    'region'       => $n->region,
                    'province'     => $n->province,
                    'city'         => $n->city,
                    'barangay_name'=> $n->barangay_name,
                    'report_type'  => $n->report_type,
                    'source_file'  => $n->source_file,
                ],
                'poles_created' => $result['poles_created'],
                'poles_updated' => $result['poles_updated'],
                'spans_created' => $result['spans_created'],
                'spans_updated' => $result['spans_updated'],
                'total_poles'   => $result['total_poles'],
                'total_spans'   => $result['total_spans'],
                'errors'        => $result['errors'],
            ],
        ], 201);
    }

    private function normalizeCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        return round((float) $value, 7);
    }

    private function matchExistingNodePole(int $nodeId, string $code, ?float $lat, ?float $lng, array $usedSkycablePoleIds): ?SkycablePole
    {
        $existing = SkycablePole::with('pole')
            ->where('node_id', $nodeId)
            ->whereNotIn('id', $usedSkycablePoleIds ?: [0])
            ->whereHas('pole', fn ($q) => $q->where('pole_code', $code))
            ->orderBy('sequence')
            ->get();

        if ($existing->isEmpty()) return null;

        return $existing->first(fn ($sp) => $this->poleMatchesCoordinates($sp->pole, $lat, $lng))
            ?? $existing->first();
    }

    private function preparePoleForNodeImport(SkycablePole $skycablePole, string $code, ?float $lat, ?float $lng): Pole
    {
        $pole = $skycablePole->pole;

        if (! $pole) {
            return Pole::create(['pole_code' => $code, 'lat' => $lat, 'lng' => $lng]);
        }

        $isShared = SkycablePole::where('pole_id', $pole->id)
            ->where('id', '!=', $skycablePole->id)
            ->exists();

        if ($isShared) {
            return Pole::create(['pole_code' => $code, 'lat' => $lat, 'lng' => $lng]);
        }

        $updates = [];
        if ($pole->pole_code !== $code) $updates['pole_code'] = $code;
        if ($lat !== null && ! $this->coordinatesEqual($pole->lat, $lat)) $updates['lat'] = $lat;
        if ($lng !== null && ! $this->coordinatesEqual($pole->lng, $lng)) $updates['lng'] = $lng;

        if (! empty($updates)) $pole->update($updates);

        return $pole->fresh();
    }

    private function poleMatchesCoordinates(?Pole $pole, ?float $lat, ?float $lng): bool
    {
        if (! $pole) return false;
        if ($lat === null || $lng === null) return $pole->lat === null && $pole->lng === null;

        return $this->coordinatesEqual($pole->lat, $lat) && $this->coordinatesEqual($pole->lng, $lng);
    }

    private function coordinatesEqual(mixed $stored, float $incoming): bool
    {
        if ($stored === null || $stored === '') return false;
        return abs(round((float) $stored, 7) - $incoming) < 0.0000001;
    }

    private function resolveImportedPoleEntry(array $poleCodeToEntries, string $code, array $spanData, string $side): ?array
    {
        $entries = $poleCodeToEntries[$code] ?? [];
        if (count($entries) === 0) return null;

        $lat = $this->normalizeCoordinate(
            $spanData["{$side}_latitude"] ?? $spanData["{$side}_pole_latitude"] ?? $spanData["{$side}_lat"] ?? null
        );
        $lng = $this->normalizeCoordinate(
            $spanData["{$side}_longitude"] ?? $spanData["{$side}_pole_longitude"] ?? $spanData["{$side}_lng"] ?? null
        );

        if ($lat !== null && $lng !== null) {
            $match = collect($entries)->first(
                fn ($e) => $this->coordinatesEqual($e['latitude'], $lat) && $this->coordinatesEqual($e['longitude'], $lng)
            );
            if ($match) return $match;
        }

        $sourceIndex = $spanData["{$side}_pole_index"] ?? $spanData["{$side}_index"] ?? null;
        if ($sourceIndex !== null) {
            $idx = (int) $sourceIndex;
            // 1. Match against explicit pole_index field (preferred — stable across re-ordering)
            $match = collect($entries)->first(fn ($e) => $e['pole_index'] !== null && $e['pole_index'] === $idx);
            if ($match) return $match;
            // 2. Fall back to 0-based array position
            $match = collect($entries)->first(fn ($e) => $e['source_index'] === $idx);
            if ($match) return $match;
            if (isset($entries[$idx])) return $entries[$idx];
        }

        return $entries[0];
    }

    private function clearSkycableMapCaches(): void
    {
        Cache::forget('skycable_all_poles');
        Cache::forget('nodes_index_50_p1');
        Cache::forget('nodes_index_100_p1');
        Cache::forget('nodes_index_200_p1');
    }
}
