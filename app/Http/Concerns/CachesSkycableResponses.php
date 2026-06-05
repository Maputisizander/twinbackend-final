<?php

namespace App\Http\Concerns;

use App\Services\RedisCache;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Skycable response caching with per-resource write-through.
 *
 * Cache key format:
 *   cache:skycable:{resource}:{paramHash}
 *
 * Flow:
 *   GET  → check Redis ETag → 304 or Redis HIT or DB MISS → cache result
 *   POST/PUT/DELETE → save to DB → immediately warm Redis with fresh data
 *
 * Resource groups (for targeted invalidation):
 *   nodes   → nodes, node detail
 *   spans   → spans, span detail
 *   poles   → poles, map pins
 *   areas   → areas
 *   teardowns → teardowns
 *   warehouses → warehouses, stocks, receipts
 */
trait CachesSkycableResponses
{
    use CachesApiResponse;

    // ── TTL per resource type ─────────────────────────────────────────────────

    private const RESOURCE_TTL = [
        'nodes'      => 300,   // 5 min
        'spans'      => 300,
        'poles'      => 300,
        'areas'      => 600,   // 10 min — areas change rarely
        'teardowns'  => 120,   // 2 min — active workflow
        'warehouses' => 300,
        'stats'      => 120,
    ];

    // ── GET — serve from Redis or DB ──────────────────────────────────────────

    protected function skycableCachedJson(
        string $scope,
        int $ttlSeconds,
        Closure $callback,
        ?Request $request = null,
    ): JsonResponse {
        $cacheKey = $this->skycableCacheKey($scope, $request);

        return $this->cachedResponse(
            cacheKey:   $cacheKey,
            ttl:        $ttlSeconds,
            callback:   $callback,
            request:    $request,
            visibility: 'private',
        );
    }

    // ── Write-through — save to DB + immediately warm Redis ───────────────────

    /**
     * After a mutation, immediately refresh a specific resource list in Redis.
     * Pass the same $scope + $fetchCallback used in the GET endpoint.
     *
     * Example (NodeController::store):
     *   $node = SkycableNode::create($data);
     *   $this->warmCache('nodes', fn () => SkycableNode::with('area')->get());
     */
    protected function warmCache(string $resource, Closure $fetchCallback, ?Request $request = null): void
    {
        try {
            $ttl      = self::RESOURCE_TTL[$resource] ?? 120;
            $cacheKey = "cache:skycable:{$resource}:all";

            $freshData = $fetchCallback();
            RedisCache::put($cacheKey, $freshData, $ttl);
        } catch (\Throwable) {
            // Never crash a mutation because cache warm failed
        }
    }

    /**
     * Invalidate all Redis keys for a given resource group.
     * Use when write-through is impractical (complex queries, bulk ops).
     *
     * Example: $this->invalidateResource('nodes', 'spans');
     */
    protected function invalidateResource(string ...$resources): void
    {
        foreach ($resources as $resource) {
            try {
                // Keys are formatted as cache:skycable:{resource}.{action}:{hash}
                // Pattern uses prefix match: cache:skycable:spans* matches spans.index, spans.show.5, etc.
                RedisCache::forgetPattern("cache:skycable:{$resource}*");
            } catch (\Throwable) {
                // Never crash a mutation because cache invalidation failed
            }
        }
    }

    /**
     * Full nuclear option — invalidate ALL skycable caches.
     * Use only for bulk imports, migrations, or cross-cutting changes.
     */
    protected function bumpSkycableCacheVersion(): void
    {
        try {
            RedisCache::forgetPattern('cache:skycable:*');
        } catch (\Throwable) {
            // Fallback: version bump (file driver, Redis unavailable)
            $version = (int) (Cache::get('skycable:data-version') ?? 0);
            Cache::put('skycable:data-version', $version + 1, 86400);
        }
    }

    // ── Key builder ───────────────────────────────────────────────────────────

    protected function skycableCacheKey(string $scope, ?Request $request = null): string
    {
        $paramHash = $request ? md5($request->fullUrl()) : 'static';
        return "cache:skycable:{$scope}:{$paramHash}";
    }
}
