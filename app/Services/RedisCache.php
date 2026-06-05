<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralised cache helper — driver-agnostic.
 *
 * Uses Laravel's Cache facade so it works with any configured driver
 * (file, database, redis, memcached).  Switch CACHE_STORE in .env freely.
 *
 * Cache key layout
 *   Private (user-scoped):  cache:user:{userId}:{resource}:{paramHash}
 *   Global (shared):        cache:global:{resource}:{paramHash}
 *   ETag mirror:            <dataKey>:etag
 */
class RedisCache
{
    // ── TTL presets (seconds) ─────────────────────────────────────────────────
    const TTL_PSGC     = 86400;  // 24 h  — static government geography data
    const TTL_ME       = 300;    // 5 min — user profile
    const TTL_MAP_PINS = 60;     // 1 min — live map pins
    const TTL_LIST     = 120;    // 2 min — paginated list endpoints
    const TTL_DETAIL   = 120;    // 2 min — single-resource detail
    const TTL_POLES    = 120;    // 2 min — pole queries

    // ── Key builders ──────────────────────────────────────────────────────────

    public static function userKey(int $userId, string $resource, string $suffix = ''): string
    {
        return $suffix
            ? "cache:user:{$userId}:{$resource}:{$suffix}"
            : "cache:user:{$userId}:{$resource}";
    }

    public static function globalKey(string $resource, string $suffix = ''): string
    {
        return $suffix
            ? "cache:global:{$resource}:{$suffix}"
            : "cache:global:{$resource}";
    }

    public static function etagKey(string $dataKey): string
    {
        return "{$dataKey}:etag";
    }

    public static function paramHash(array $params): string
    {
        ksort($params);
        return md5(json_encode($params));
    }

    // ── Core cache operations ─────────────────────────────────────────────────

    /**
     * Return cached [data, etag, hit] or run $callback, cache result, return same shape.
     */
    public static function rememberWithEtag(string $dataKey, int $ttl, \Closure $callback): array
    {
        $etagKey = self::etagKey($dataKey);

        $cachedEtag = Cache::get($etagKey);
        $cachedData = $cachedEtag ? Cache::get($dataKey) : null;

        if ($cachedEtag && $cachedData !== null) {
            return [
                'data'  => is_string($cachedData) ? json_decode($cachedData, true) : $cachedData,
                'etag'  => $cachedEtag,
                'hit'   => true,
            ];
        }

        $data = $callback();
        [$json, $etag] = self::encode($data);

        Cache::put($dataKey, $json, $ttl);
        Cache::put($etagKey, $etag, $ttl);

        return ['data' => $data, 'etag' => $etag, 'hit' => false];
    }

    /**
     * Read only the ETag — used for 304 pre-check (no data deserialization).
     */
    public static function getEtag(string $dataKey): ?string
    {
        $value = Cache::get(self::etagKey($dataKey));
        return $value ?: null;
    }

    /**
     * Store fresh data and its ETag (used after a write that must refresh cache).
     */
    public static function put(string $dataKey, mixed $data, int $ttl): string
    {
        [$json, $etag] = self::encode($data);
        Cache::put($dataKey, $json, $ttl);
        Cache::put(self::etagKey($dataKey), $etag, $ttl);
        return $etag;
    }

    /**
     * Delete one or more data keys (and their paired ETag keys).
     */
    public static function forget(string ...$dataKeys): void
    {
        foreach ($dataKeys as $key) {
            Cache::forget($key);
            Cache::forget(self::etagKey($key));
        }
    }

    /**
     * Delete all keys matching a prefix pattern (and their :etag mirrors).
     * Uses Cache tags when available (Redis/Memcached), falls back to prefix scan.
     */
    public static function forgetPattern(string $pattern): void
    {
        try {
            $store = Cache::getStore();

            if (method_exists($store, 'connection')) {
                $connection  = $store->connection();
                $cachePrefix = config('cache.prefix', '');

                // Key format in Redis: {redis_conn_prefix}{cache_prefix}{key}
                // predis automatically prepends the redis_conn_prefix on every
                // command, so we only need to include {cache_prefix} in our pattern.
                // KEYS returns full keys (with redis_conn_prefix). DEL must receive
                // those full keys — use executeRaw to bypass predis re-prefixing.
                $searchPattern = $cachePrefix . $pattern;
                $keys = $connection->keys($searchPattern);

                if (!empty($keys)) {
                    foreach (array_chunk($keys, 500) as $chunk) {
                        $connection->executeRaw(array_merge(['DEL'], $chunk));
                    }
                }
                return;
            }
        } catch (\Throwable) {
            // Never crash a mutation because cache invalidation failed
        }

        // Fallback for file/database drivers
        $prefix = rtrim(str_replace('*', '', $pattern), ':');
        Cache::forget($prefix);
        Cache::forget($prefix . ':etag');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function encode(mixed $data): array
    {
        $json = json_encode($data);
        $etag = '"' . md5($json) . '"';
        return [$json, $etag];
    }
}
