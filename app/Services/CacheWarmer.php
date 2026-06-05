<?php

namespace App\Services;

use App\Models\SkycableArea;
use App\Models\SkycableNode;
use App\Models\SkycableSpan;
use App\Models\SkycablePole;

/**
 * Write-through cache warmer.
 *
 * After every POST / PUT, call the appropriate warm() method
 * to immediately push fresh data into Redis so the next GET
 * is always a cache HIT with zero DB calls.
 *
 * Pattern:
 *   1. Controller saves to DB
 *   2. Controller calls CacheWarmer::nodes()
 *   3. Warmer runs the exact same query as the GET index
 *   4. Pushes result into Redis under the same cache key
 *   5. Next GET → Redis HIT immediately
 */
class CacheWarmer
{
    // TTLs must match what the controllers use in skycableCachedJson()
    const TTL_NODES    = 120;
    const TTL_SPANS    = 120;
    const TTL_AREAS    = 120;
    const TTL_POLES    = 120;

    /**
     * Warm the node list cache after a node create/update.
     * Covers the most common web dashboard call: GET /skycable/nodes
     *
     * @param int|null $areaId  If provided, also warm the area-filtered list
     */
    public static function nodes(?int $areaId = null): void
    {
        static::run('nodes.index', self::TTL_NODES, function () {
            return SkycableNode::with(['area', 'site', 'subcontractor', 'team'])
                ->withCount('spans')
                ->withSum('spanSummaries', 'expected_cable')
                ->withSum('spanSummaries', 'actual_cable')
                ->orderBy('full_label')
                ->paginate(50);
        });

        if ($areaId) {
            static::run("nodes.index.area.{$areaId}", self::TTL_NODES, function () use ($areaId) {
                return SkycableNode::with(['area', 'site', 'subcontractor', 'team'])
                    ->withCount('spans')
                    ->where('area_id', $areaId)
                    ->orderBy('full_label')
                    ->paginate(50);
            });
        }
    }

    /**
     * Warm spans cache after a span create/update.
     * Covers: GET /skycable/spans?node_id={nodeId}
     */
    public static function spans(int $nodeId): void
    {
        static::run("spans.index.node.{$nodeId}", self::TTL_SPANS, function () use ($nodeId) {
            return SkycableSpan::with(['node', 'fromPole.pole', 'toPole.pole', 'summary'])
                ->where('node_id', $nodeId)
                ->whereNotIn('status', ['superseded'])
                ->get();
        });

        // Also warm the stats for this node
        static::run("spans.stats.node.{$nodeId}", self::TTL_SPANS, function () use ($nodeId) {
            $spans = SkycableSpan::with('summary')
                ->where('node_id', $nodeId)
                ->whereNotIn('status', ['superseded', 'cancelled'])
                ->get();

            $active = $spans;
            return [
                'total'             => $active->count(),
                'pending'           => $active->where('status', 'pending')->count(),
                'in_progress'       => $active->where('status', 'in_progress')->count(),
                'completed'         => $active->where('status', 'completed')->count(),
                'total_cable_m'     => round($active->sum(fn ($s) => $s->summary?->expected_cable ?? 0), 2),
                'completed_cable_m' => round($active->where('status', 'completed')->sum(fn ($s) => $s->summary?->expected_cable ?? 0), 2),
            ];
        });
    }

    /**
     * Warm areas cache after an area create/update.
     */
    public static function areas(): void
    {
        static::run('areas.index', self::TTL_AREAS, function () {
            return SkycableArea::withCount('nodes')->orderBy('name')->get();
        });
    }

    /**
     * Warm poles cache for a node after pole GPS update or new pole added.
     */
    public static function poles(int $nodeId): void
    {
        static::run("poles.index.node.{$nodeId}", self::TTL_POLES, function () use ($nodeId) {
            return SkycablePole::with('pole')
                ->where('node_id', $nodeId)
                ->orderBy('sequence')
                ->get();
        });
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * Run a fetch callback and immediately put the result into Redis.
     * Never throws — a failed warm is non-fatal.
     */
    private static function run(string $scope, int $ttl, \Closure $fetch): void
    {
        try {
            $data = $fetch();
            RedisCache::put("cache:skycable:{$scope}:warmed", $data, $ttl);
        } catch (\Throwable) {
            // Fire-and-forget — never crash a write because warming failed
        }
    }
}
