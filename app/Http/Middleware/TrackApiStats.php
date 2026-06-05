<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records per-request stats — works on Redis AND database/file cache drivers.
 *
 * Redis available   → pipeline (fast, atomic, in-memory)
 * Redis unavailable → Cache facade (database/file, slightly slower but works)
 */
class TrackApiStats
{
    public function handle(Request $request, Closure $next): Response
    {
        $start    = microtime(true);
        $response = $next($request);

        try {
            $this->record($request, $response, $start);
        } catch (\Throwable) {}

        return $response;
    }

    private const UNCACHEABLE_PATHS = [
        '/api/v1/ping',
        '/api/v1/apistatus',
        '/api/v1/apiconsumption',
    ];

    private const MUTATION_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private function record(Request $request, Response $response, float $start): void
    {
        $now        = now();
        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $status     = $response->getStatusCode();
        $method     = $request->method();
        $path       = '/' . ltrim($request->path(), '/');
        $routeKey   = "{$method} {$path}";
        $dateKey    = $now->format('Y-m-d');
        $hourKey    = $now->format('Y-m-d-H');

        $xCache   = $response->headers->get('X-Cache', '');
        $cacheHit = $xCache === 'HIT' || $request->attributes->get('_cache_hit', false);
        $source   = $cacheHit ? 'redis' : 'db';

        $isMonitoring = in_array($path, self::UNCACHEABLE_PATHS, true);
        $isMutation   = in_array($method, self::MUTATION_METHODS, true);
        $isCacheable  = ! $isMonitoring && ! $isMutation;

        $entry = ! $isMonitoring ? json_encode([
            'ts'     => $now->toIso8601String(),
            'method' => $method,
            'path'   => $path,
            'status' => $status,
            'ms'     => $durationMs,
            'source' => $isMutation ? 'db' : $source,
        ]) : null;

        if ($this->redisAvailable()) {
            $this->recordViaRedis($dateKey, $hourKey, $routeKey, $status, $isCacheable, $cacheHit, $entry);
        } else {
            $this->recordViaCache($dateKey, $hourKey, $routeKey, $status, $isCacheable, $cacheHit, $entry);
        }
    }

    // ── Redis path (fast pipeline) ────────────────────────────────────────────

    private function recordViaRedis(
        string $dateKey, string $hourKey, string $routeKey,
        int $status, bool $isCacheable, bool $cacheHit, ?string $entry
    ): void {
        $redis = Redis::connection('default');
        $pipe  = $redis->pipeline();

        $pipe->incr('stats:calls:total');
        $pipe->incr("stats:calls:date:{$dateKey}");
        $pipe->expire("stats:calls:date:{$dateKey}", 8 * 86400);
        $pipe->incr("stats:calls:hour:{$hourKey}");
        $pipe->expire("stats:calls:hour:{$hourKey}", 49 * 3600);

        if ($isCacheable) {
            $pipe->incr($cacheHit ? 'stats:cache:hits' : 'stats:cache:misses');
        } else {
            $pipe->incr('stats:cache:uncacheable');
        }

        $pipe->hIncrBy('stats:routes',   $routeKey,       1);
        $pipe->hIncrBy('stats:statuses', (string) $status, 1);

        if ($entry) {
            $pipe->lPush('stats:recent', $entry);
            $pipe->lTrim('stats:recent', 0, 99);
        }

        $pipe->execute();
    }

    // ── Cache facade path (database / file) ───────────────────────────────────

    private function recordViaCache(
        string $dateKey, string $hourKey, string $routeKey,
        int $status, bool $isCacheable, bool $cacheHit, ?string $entry
    ): void {
        Cache::increment('stats:calls:total');

        $this->incrementWithTtl("stats:calls:date:{$dateKey}", 8 * 86400);
        $this->incrementWithTtl("stats:calls:hour:{$hourKey}", 49 * 3600);

        if ($isCacheable) {
            Cache::increment($cacheHit ? 'stats:cache:hits' : 'stats:cache:misses');
        } else {
            Cache::increment('stats:cache:uncacheable');
        }

        // Routes hash — stored as PHP array in a single cache key
        $routes = Cache::get('stats:routes', []);
        $routes[$routeKey] = ($routes[$routeKey] ?? 0) + 1;
        arsort($routes);
        Cache::put('stats:routes', array_slice($routes, 0, 200, true), 86400 * 30);

        // Statuses hash
        $statuses = Cache::get('stats:statuses', []);
        $statuses[(string) $status] = ($statuses[(string) $status] ?? 0) + 1;
        Cache::put('stats:statuses', $statuses, 86400 * 30);

        // Recent ring buffer — array of last 100 JSON strings
        if ($entry) {
            $recent = Cache::get('stats:recent', []);
            array_unshift($recent, $entry);
            Cache::put('stats:recent', array_slice($recent, 0, 100), 86400 * 7);
        }
    }

    private function incrementWithTtl(string $key, int $ttl): void
    {
        $val = Cache::get($key, 0);
        Cache::put($key, $val + 1, $ttl);
    }

    // ── Detect Redis ──────────────────────────────────────────────────────────

    private static ?bool $redisOk = null;

    private function redisAvailable(): bool
    {
        if (self::$redisOk !== null) return self::$redisOk;

        try {
            Redis::connection('default')->ping();
            self::$redisOk = true;
        } catch (\Throwable) {
            self::$redisOk = false;
        }

        return self::$redisOk;
    }
}
