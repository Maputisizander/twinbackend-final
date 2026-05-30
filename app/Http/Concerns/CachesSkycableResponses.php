<?php

namespace App\Http\Concerns;

use App\Services\RedisCache;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Skycable-specific caching on top of CachesApiResponse.
 *
 * Uses a global Skycable version counter so any mutation instantly
 * invalidates all cached Skycable responses — no per-key tracking needed.
 *
 * Cache key format:
 *   cache:skycable:v{version}:{scope}:{requestHash}
 */
trait CachesSkycableResponses
{
    use CachesApiResponse;

    /**
     * Wrap a Skycable controller action with caching + ETag support.
     */
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

    /**
     * Increment the Skycable version counter.
     * Called by every store / update / destroy action.
     */
    protected function bumpSkycableCacheVersion(): void
    {
        $version = (int) (Cache::get('skycable:data-version') ?? 0);
        Cache::put('skycable:data-version', $version + 1, 86400);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function skycableCacheKey(string $scope, ?Request $request = null): string
    {
        $requestPart = $request ? md5($request->fullUrl()) : 'static';

        return implode(':', [
            'cache:skycable',
            'v' . $this->skycableCacheVersion(),
            $scope,
            $requestPart,
        ]);
    }

    private function skycableCacheVersion(): int
    {
        return (int) (Cache::get('skycable:data-version') ?? 1);
    }
}
