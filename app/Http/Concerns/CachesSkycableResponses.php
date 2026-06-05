<?php

namespace App\Http\Concerns;

use App\Services\RedisCache;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Skycable response caching — works with ANY cache driver.
 *
 * Driver behaviour:
 *
 *   redis     → instant pattern-delete on mutations. Next GET always fresh.
 *               Cache key: cache:skycable:{scope}:{urlHash}
 *
 *   database  → version-bump on mutations. Old keys become orphaned and
 *               expire naturally via TTL (max 10 min). Perfect for Hostinger.
 *               Cache key: cache:skycable:v{n}:{scope}:{urlHash}
 *
 *   file      → same as database (version-bump)
 *               Cache key: cache:skycable:v{n}:{scope}:{urlHash}
 *
 * ETag / 304 responses work on ALL drivers — the browser sends
 * If-None-Match, server skips body entirely. This alone cuts 60-80%
 * of bandwidth regardless of which cache backend is active.
 */
trait CachesSkycableResponses
{
    use CachesApiResponse;

    private const RESOURCE_TTL = [
        'nodes'      => 300,
        'spans'      => 300,
        'poles'      => 300,
        'areas'      => 600,
        'teardowns'  => 120,
        'warehouses' => 300,
        'stats'      => 120,
    ];

    // ── GET ───────────────────────────────────────────────────────────────────

    protected function skycableCachedJson(
        string $scope,
        int $ttlSeconds,
        Closure $callback,
        ?Request $request = null,
    ): JsonResponse {
        return $this->cachedResponse(
            cacheKey:   $this->skycableCacheKey($scope, $request),
            ttl:        $ttlSeconds,
            callback:   $callback,
            request:    $request,
            visibility: 'private',
        );
    }

    // ── Invalidation (driver-aware) ───────────────────────────────────────────

    /**
     * Invalidate specific resource groups.
     *
     * Redis  → instant pattern delete (cache:skycable:{resource}*)
     * Others → version bump (old versioned keys expire via TTL)
     */
    protected function invalidateResource(string ...$resources): void
    {
        if ($this->isRedis()) {
            foreach ($resources as $resource) {
                try {
                    RedisCache::forgetPattern("cache:skycable:{$resource}*");
                } catch (\Throwable) {}
            }
        } else {
            $this->bumpVersion();
        }
    }

    /**
     * Nuclear invalidation — wipe ALL skycable caches.
     * Use for bulk imports, migrations, cross-cutting mutations.
     */
    protected function bumpSkycableCacheVersion(): void
    {
        if ($this->isRedis()) {
            try {
                RedisCache::forgetPattern('cache:skycable:*');
                return;
            } catch (\Throwable) {}
        }

        $this->bumpVersion();
    }

    // ── Write-through warm (Redis only — no-op on other drivers) ─────────────

    protected function warmCache(string $resource, Closure $fetchCallback): void
    {
        if (! $this->isRedis()) return;

        try {
            $ttl      = self::RESOURCE_TTL[$resource] ?? 120;
            $cacheKey = "cache:skycable:{$resource}:all";
            RedisCache::put($cacheKey, $fetchCallback(), $ttl);
        } catch (\Throwable) {}
    }

    // ── Key builder ───────────────────────────────────────────────────────────

    protected function skycableCacheKey(string $scope, ?Request $request = null): string
    {
        $hash = $request ? md5($request->fullUrl()) : 'static';

        if ($this->isRedis()) {
            // Redis: no version needed — pattern delete handles invalidation
            return "cache:skycable:{$scope}:{$hash}";
        }

        // Database / file: embed version so bumping makes old keys unreachable
        $v = (int) (Cache::get('skycable:data-version') ?? 1);
        return "cache:skycable:v{$v}:{$scope}:{$hash}";
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isRedis(): bool
    {
        return config('cache.default') === 'redis';
    }

    private function bumpVersion(): void
    {
        try {
            $v = (int) (Cache::get('skycable:data-version') ?? 0);
            Cache::put('skycable:data-version', $v + 1, 86400);
        } catch (\Throwable) {}
    }
}
