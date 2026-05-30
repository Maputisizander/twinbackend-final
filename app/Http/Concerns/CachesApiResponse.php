<?php

namespace App\Http\Concerns;

use App\Services\RedisCache;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Universal Redis-backed response caching with HTTP 304 / ETag support.
 *
 * Flow for GET endpoints:
 *   1. Read ETag from Redis (one tiny Redis GET — no DB, no JSON decode).
 *   2. If client's If-None-Match matches → return 304 immediately.
 *   3. If Redis has data but client has no/stale ETag → return 200 from Redis (no DB).
 *   4. Cache miss → run $callback (hits DB) → store in Redis → return 200 with ETag.
 *
 * Flow for mutations (store/update/destroy):
 *   Call bustCache() or bustCachePattern() to invalidate affected keys.
 */
trait CachesApiResponse
{
    /**
     * Return a cached JSON response. Handles ETag negotiation automatically.
     *
     * @param  string        $cacheKey     Redis data key (use RedisCache::userKey / globalKey)
     * @param  int           $ttl          TTL in seconds
     * @param  Closure       $callback     Runs only on cache miss; must return serialisable data
     * @param  Request|null  $request      Pass request to enable If-None-Match → 304
     * @param  string        $visibility   'private' for user data, 'public' for global data
     */
    protected function cachedResponse(
        string $cacheKey,
        int $ttl,
        Closure $callback,
        ?Request $request = null,
        string $visibility = 'private',
    ): JsonResponse {
        $cacheControl = "{$visibility}, max-age={$ttl}, must-revalidate";

        // ── Step 1: ETag-only check (cheapest path, no data deserialization) ──
        $cachedEtag = RedisCache::getEtag($cacheKey);

        if ($cachedEtag && $request) {
            $clientEtag = $request->header('If-None-Match');

            if ($clientEtag && $clientEtag === $cachedEtag) {
                $this->markCacheStats($request, redisHit: true, dbQueried: false);

                return response()->json(null, 304)
                    ->header('ETag', $cachedEtag)
                    ->header('Cache-Control', $cacheControl)
                    ->header('X-Cache', 'HIT')
                    ->header('X-Cache-Source', 'redis-etag');
            }
        }

        // ── Step 2: Full Redis cache hit ──────────────────────────────────────
        $result = RedisCache::rememberWithEtag($cacheKey, $ttl, function () use ($callback, $request) {
            $this->markCacheStats($request, redisHit: false, dbQueried: true);
            return $callback();
        });

        if ($result['hit']) {
            $this->markCacheStats($request, redisHit: true, dbQueried: false);
        }

        return response()->json($result['data'])
            ->header('ETag', $result['etag'])
            ->header('Cache-Control', $cacheControl)
            ->header('X-Cache', $result['hit'] ? 'HIT' : 'MISS')
            ->header('X-Cache-Source', $result['hit'] ? 'redis' : 'db');
    }

    /**
     * Invalidate specific cache keys (data + paired ETag).
     */
    protected function bustCache(string ...$cacheKeys): void
    {
        RedisCache::forget(...$cacheKeys);
    }

    /**
     * Invalidate all Redis keys matching a glob pattern.
     * Use sparingly — KEYS is O(N).
     */
    protected function bustCachePattern(string $pattern): void
    {
        RedisCache::forgetPattern($pattern);
    }

    /**
     * Store request-scoped cache metadata for ApiRequestLogger to pick up.
     */
    private function markCacheStats(
        ?Request $request,
        bool $redisHit,
        bool $dbQueried,
    ): void {
        if (! $request) {
            return;
        }
        $request->attributes->set('_cache_hit', $redisHit);
        $request->attributes->set('_redis_hit', $redisHit);
        $request->attributes->set('_db_queried', $dbQueried);
    }
}
