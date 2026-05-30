<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Concerns\CachesSkycableResponses;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pole;
use App\Models\SkycablePole;
use App\Models\SkycableSpan;
use App\Models\SkycableSpanSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpanController extends Controller
{
    use CachesSkycableResponses;

    public function index(Request $request)
    {
        return $this->skycableCachedJson('spans.index', 120, function () use ($request) {
            return SkycableSpan::with(['node', 'fromPole.pole', 'toPole.pole', 'summary'])
                ->when($request->node_id, fn ($q) => $q->where('node_id', $request->node_id))
                ->when($request->status,  fn ($q) => $q->where('status', $request->status))
                ->get();
        }, $request);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'node_id'          => 'required|exists:skycable_nodes,id',
            'from_pole_id'     => 'required|exists:skycable_poles,id',
            'to_pole_id'       => 'required|exists:skycable_poles,id|different:from_pole_id',
            'span_code'        => 'nullable|string|max:100',
            'strand_length'    => 'nullable|numeric|min:0',
            'number_of_runs'   => 'nullable|integer|min:0',
            'actual_cable'     => 'nullable|numeric|min:0',
            'nodes_count'      => 'nullable|integer|min:0',
            'amplifier'        => 'nullable|integer|min:0',
            'extender'         => 'nullable|integer|min:0',
            'tsc'              => 'nullable|integer|min:0',
            'power_supply'     => 'nullable|integer|min:0',
            'power_supply_case'=> 'nullable|integer|min:0',
            // aliases used by web frontend
            'powersupply'      => 'nullable|integer|min:0',
            'ps_housing'       => 'nullable|integer|min:0',
        ]);

        $span = SkycableSpan::create([
            'node_id'        => $data['node_id'],
            'from_pole_id'   => $data['from_pole_id'],
            'to_pole_id'     => $data['to_pole_id'],
            'span_code'      => $data['span_code'] ?? null,
            'strand_length'  => $data['strand_length'] ?? null,
            'number_of_runs' => $data['number_of_runs'] ?? null,
            'actual_cable'   => $data['actual_cable'] ?? null,
        ]);

        $this->syncComponents($span, $data);

        // Recalculate pole statuses — a new pending span makes a cleared pole active again
        $poleChanges = $this->refreshPoleStatuses($span);

        AuditLog::record('created', $span, null, array_merge($span->toArray(), ['pole_status_changes' => $poleChanges]));
        $this->bumpSkycableCacheVersion();

        return response()->json([
            'span'               => $span->load(['fromPole.pole', 'toPole.pole', 'summary']),
            'pole_status_changes' => $poleChanges,
        ], 201);
    }

    public function show(SkycableSpan $span)
    {
        return $this->skycableCachedJson("spans.show.{$span->id}", 120, function () use ($span) {
            return $span->load(['node', 'fromPole.pole', 'toPole.pole', 'summary', 'teardownReports']);
        });
    }

    public function update(Request $request, SkycableSpan $span)
    {
        $data = $request->validate([
            'strand_length'    => 'sometimes|nullable|numeric|min:0',
            'number_of_runs'   => 'sometimes|nullable|integer|min:0',
            'actual_cable'     => 'sometimes|nullable|numeric|min:0',
            'status'           => 'sometimes|in:pending,in_progress,completed,cancelled',
            'nodes_count'      => 'sometimes|nullable|integer|min:0',
            'amplifier'        => 'sometimes|nullable|integer|min:0',
            'extender'         => 'sometimes|nullable|integer|min:0',
            'tsc'              => 'sometimes|nullable|integer|min:0',
            'power_supply'     => 'sometimes|nullable|integer|min:0',
            'power_supply_case'=> 'sometimes|nullable|integer|min:0',
            'powersupply'      => 'sometimes|nullable|integer|min:0',
            'ps_housing'       => 'sometimes|nullable|integer|min:0',
        ]);

        $old = $span->toArray();
        $span->update([
            'strand_length'  => $data['strand_length']  ?? $span->strand_length,
            'number_of_runs' => $data['number_of_runs'] ?? $span->number_of_runs,
            'actual_cable'   => $data['actual_cable']   ?? $span->actual_cable,
            'status'         => $data['status']         ?? $span->status,
        ]);

        $this->syncComponents($span, $data);

        AuditLog::record('updated', $span, $old, $span->toArray());
        $this->bumpSkycableCacheVersion();

        return response()->json($span->load(['fromPole.pole', 'toPole.pole', 'summary']));
    }

    public function destroy(SkycableSpan $span)
    {
        AuditLog::record('deleted', $span, $span->toArray(), null);
        $span->delete();
        $this->bumpSkycableCacheVersion();

        return response()->json(['message' => 'Span deleted.']);
    }

    /**
     * Split a span by inserting a new pole between from_pole and to_pole.
     *
     * POST /skycable/spans/{span}/split
     * Body: { pole_name: string, idempotency_key?: string }
     *
     * Returns: { new_pole, span_a, span_b }
     *   span_a: original from_pole → new_pole
     *   span_b: new_pole → original to_pole
     */
    public function split(Request $request, SkycableSpan $span)
    {
        $request->validate([
            'pole_name'       => 'required|string|max:100',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        // Idempotency: if already split with this key, return existing result
        if ($key = $request->input('idempotency_key')) {
            $existing = SkycableSpan::where('idempotency_key', $key)->first();
            if ($existing) {
                $spanA = SkycableSpan::where('idempotency_key', $key . '_a')->with(['fromPole.pole', 'toPole.pole', 'summary'])->first();
                $spanB = SkycableSpan::where('idempotency_key', $key . '_b')->with(['fromPole.pole', 'toPole.pole', 'summary'])->first();
                $newPole = $spanA?->toPole?->pole;
                return response()->json([
                    'new_pole' => $newPole,
                    'span_a'   => $spanA,
                    'span_b'   => $spanB,
                ], 200);
            }
        }

        if ($span->status === 'superseded') {
            return response()->json(['message' => 'This span has already been split.'], 409);
        }

        $result = DB::transaction(function () use ($span, $request, $key) {
            $poleName = $request->input('pole_name');

            // 1. Create the physical pole
            $newPole = Pole::create([
                'pole_code' => $poleName,
                'lat'       => null,
                'lng'       => null,
            ]);

            // 2. Register it in skycable_poles — auto-assign next sequence in this node
            $nextSeq = SkycablePole::where('node_id', $span->node_id)->max('sequence') + 1;
            $newSkycablePole = SkycablePole::create([
                'node_id'  => $span->node_id,
                'pole_id'  => $newPole->id,
                'sequence' => $nextSeq,
            ]);

            // 3. span_a: original from_pole → new pole (inherit half the cable/components)
            $spanA = SkycableSpan::create([
                'node_id'          => $span->node_id,
                'from_pole_id'     => $span->from_pole_id,
                'to_pole_id'       => $newSkycablePole->id,
                'span_code'        => $span->span_code ? $span->span_code . '-A' : null,
                'strand_length'    => $span->strand_length ? round($span->strand_length / 2, 2) : null,
                'number_of_runs'   => $span->number_of_runs,
                'actual_cable'     => null,
                'status'           => 'pending',
                'idempotency_key'  => $key ? $key . '_a' : null,
            ]);

            // 4. span_b: new pole → original to_pole
            $spanB = SkycableSpan::create([
                'node_id'          => $span->node_id,
                'from_pole_id'     => $newSkycablePole->id,
                'to_pole_id'       => $span->to_pole_id,
                'span_code'        => $span->span_code ? $span->span_code . '-B' : null,
                'strand_length'    => $span->strand_length ? round($span->strand_length / 2, 2) : null,
                'number_of_runs'   => $span->number_of_runs,
                'actual_cable'     => null,
                'status'           => 'pending',
                'idempotency_key'  => $key ? $key . '_b' : null,
            ]);

            // 5. Copy expected components to both new spans from span_summaries
            $parentSummary = SkycableSpanSummary::where('span_id', $span->id)->first();
            foreach ([$spanA->id, $spanB->id] as $sid) {
                SkycableSpanSummary::create([
                    'span_id'              => $sid,
                    'node_id'              => $span->node_id,
                    'expected_cable'       => $parentSummary?->expected_cable ?? 0,
                    'expected_node'        => $parentSummary?->expected_node ?? 0,
                    'expected_amplifier'   => $parentSummary?->expected_amplifier ?? 0,
                    'expected_extender'    => $parentSummary?->expected_extender ?? 0,
                    'expected_tsc'         => $parentSummary?->expected_tsc ?? 0,
                    'expected_powersupply' => $parentSummary?->expected_powersupply ?? 0,
                    'expected_ps_housing'  => $parentSummary?->expected_ps_housing ?? 0,
                ]);
            }

            // 6. Mark original span as superseded
            $span->update(['status' => 'superseded']);

            AuditLog::record('split', $span, $span->toArray(), [
                'new_pole_id' => $newPole->id,
                'span_a_id'   => $spanA->id,
                'span_b_id'   => $spanB->id,
            ]);

            return [
                'new_pole' => $newPole->fresh(),
                'span_a'   => $spanA->load(['fromPole.pole', 'toPole.pole', 'summary']),
                'span_b'   => $spanB->load(['fromPole.pole', 'toPole.pole', 'summary']),
            ];
        });

        // Recalculate pole statuses — new pending spans bring cleared poles back to pending
        $poleChanges = array_merge(
            $this->refreshPoleStatuses($result['span_a']),
            $this->refreshPoleStatuses($result['span_b']),
        );

        $this->bumpSkycableCacheVersion();

        return response()->json(array_merge($result, ['pole_status_changes' => $poleChanges]), 201);
    }

    public function updateComponents(Request $request, SkycableSpan $span)
    {
        $request->validate([
            'expected_node'       => 'nullable|integer|min:0',
            'expected_amplifier'  => 'nullable|integer|min:0',
            'expected_extender'   => 'nullable|integer|min:0',
            'expected_tsc'        => 'nullable|integer|min:0',
            'expected_powersupply'=> 'nullable|integer|min:0',
            'expected_ps_housing' => 'nullable|integer|min:0',
            'expected_cable'      => 'nullable|numeric|min:0',
        ]);

        SkycableSpanSummary::updateOrCreate(
            ['span_id' => $span->id],
            array_merge(
                ['node_id' => $span->node_id],
                array_filter($request->only([
                    'expected_node', 'expected_amplifier', 'expected_extender',
                    'expected_tsc', 'expected_powersupply', 'expected_ps_housing', 'expected_cable',
                ]), fn($v) => !is_null($v))
            )
        );

        AuditLog::record('updated', $span, null, ['components_updated' => true]);
        $this->bumpSkycableCacheVersion();

        return response()->json($span->load('summary'));
    }

    /**
     * Recalculate skycable_status for poles connected to a span.
     *
     * A pole only becomes pending/in_progress if it VERIFIABLY has at least
     * one non-superseded, non-completed span in the database.
     * This prevents a cleared pole from going back to pending by mistake.
     *
     * Rules:
     *  - Has pending spans only              → pending   (teardown available)
     *  - Has both completed & pending spans  → in_progress (teardown still available)
     *  - All spans completed, none pending   → cleared   (done, hidden from active list)
     *  - No spans at all                     → unchanged (we never touch it)
     *
     * Returns array of [pole_code => new_status] for logging/response.
     */
    private function refreshPoleStatuses(SkycableSpan $span): array
    {
        $skycablePoleIds = array_filter([$span->from_pole_id, $span->to_pole_id]);
        $changed = [];

        foreach ($skycablePoleIds as $spId) {
            $skycablePole = SkycablePole::with('pole')->find($spId);
            if (!$skycablePole?->pole) continue;

            // Only count spans that are not superseded (superseded = already split, irrelevant)
            $allSpans = SkycableSpan::where(function ($q) use ($spId) {
                $q->where('from_pole_id', $spId)->orWhere('to_pole_id', $spId);
            })->whereNotIn('status', ['superseded', 'cancelled'])->get();

            // Guard: pole has no actionable spans — do not touch its status
            if ($allSpans->isEmpty()) continue;

            $pendingSpans   = $allSpans->where('status', '!=', 'completed');
            $completedSpans = $allSpans->where('status', 'completed');

            $hasPending   = $pendingSpans->isNotEmpty();
            $hasCompleted = $completedSpans->isNotEmpty();

            $newStatus = match (true) {
                $hasPending && !$hasCompleted => 'pending',      // all new/unstarted
                $hasPending &&  $hasCompleted => 'in_progress',  // some done, some left
                default                       => 'cleared',      // all done
            };

            $poleCode = $skycablePole->pole->pole_code;

            if ($skycablePole->pole->skycable_status !== $newStatus) {
                $skycablePole->pole->update(['skycable_status' => $newStatus]);
                $changed[$poleCode] = [
                    'from' => $skycablePole->pole->skycable_status,
                    'to'   => $newStatus,
                    'reason' => "has {$pendingSpans->count()} pending + {$completedSpans->count()} completed spans",
                ];
            }
        }

        return $changed;
    }

    private function syncComponents(SkycableSpan $span, array $data): void
    {
        // Compute expected cable from strand × runs
        $strand = (float) ($span->strand_length ?? 0);
        $runs   = (int)   ($span->number_of_runs ?? 1);
        $expectedCable = $strand * $runs;

        // Accept both field name conventions (power_supply vs powersupply)
        $fields = [
            'expected_cable'       => $expectedCable,
            'expected_node'        => (int) ($data['nodes_count']      ?? $data['expected_node']      ?? 0),
            'expected_amplifier'   => (int) ($data['amplifier']         ?? $data['expected_amplifier']  ?? 0),
            'expected_extender'    => (int) ($data['extender']          ?? $data['expected_extender']   ?? 0),
            'expected_tsc'         => (int) ($data['tsc']               ?? $data['expected_tsc']        ?? 0),
            'expected_powersupply' => (int) ($data['power_supply']      ?? $data['powersupply']         ?? 0),
            'expected_ps_housing'  => (int) ($data['power_supply_case'] ?? $data['ps_housing']          ?? 0),
        ];

        SkycableSpanSummary::updateOrCreate(
            ['span_id' => $span->id],
            array_merge(['node_id' => $span->node_id], $fields)
        );
    }
}
