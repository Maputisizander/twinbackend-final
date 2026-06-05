<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records per-request stats to Redis.
 *
 * Cache hit rate only counts GET requests to cacheable business endpoints.
 * Monitoring paths (ping, apistatus, apiconsumption) and mutations
 * (POST/PUT/PATCH/DELETE) are counted in totals/routes but excluded
 * from the hit/miss ratio so the hit rate reflects real cache performance.
 *
 * Keys:
 *   stats:calls:total              — lifetime total
 *   stats:calls:date:{Y-m-d}       — daily (TTL 8d)
 *   stats:calls:hour:{Y-m-d-H}     — hourly (TTL 49h)
 *   stats:cache:hits               — GET → Redis HIT
 *   stats:cache:misses             — GET cacheable → DB MISS
 *   stats:cache:uncacheable        — POST/PUT/PATCH/DELETE + monitoring (excluded from rate)
 *   stats:routes (HASH)            — calls per route
 *   stats:statuses (HASH)          — calls per HTTP status
 *   stats:recent (LIST)            — last 100 real requests (newest first)
 */
class TrackApiStats
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        try {
            $this->record($request, $response, $start);
        } catch (\Throwable) {}

        return $response;
    }

    // These paths are intentionally not cached — exclude from hit/miss ratio
    private const UNCACHEABLE_PATHS = [
        '/api/v1/ping',
        '/api/v1/apistatus',
        '/api/v1/apiconsumption',
    ];

    // Mutations are always DB — exclude from hit/miss ratio
    private const MUTATION_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private function record(Request $request, Response $response, float $start): void
    {
        $redis      = Redis::connection('default');
        $now        = now();
        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $status     = $response->getStatusCode();
        $method     = $request->method();
        $path       = '/' . ltrim($request->path(), '/');
        $routeKey   = "{$method} {$path}";
        $dateKey    = $now->format('Y-m-d');
        $hourKey    = $now->format('Y-m-d-H');

        // Detect cache source from response header set by CachesApiResponse
        $xCache   = $response->headers->get('X-Cache', '');
        $cacheHit = $xCache === 'HIT' || $request->attributes->get('_cache_hit', false);
        $source   = $cacheHit ? 'redis' : 'db';

        // Is this request cacheable (GET to a business endpoint)?
        $isMonitoring = in_array($path, self::UNCACHEABLE_PATHS, true);
        $isMutation   = in_array($method, self::MUTATION_METHODS, true);
        $isCacheable  = !$isMonitoring && !$isMutation;

        $pipe = $redis->pipeline();

        // ── Totals (every request) ────────────────────────────────────────────
        $pipe->incr('stats:calls:total');
        $pipe->incr("stats:calls:date:{$dateKey}");
        $pipe->expire("stats:calls:date:{$dateKey}", 8 * 86400);
        $pipe->incr("stats:calls:hour:{$hourKey}");
        $pipe->expire("stats:calls:hour:{$hourKey}", 49 * 3600);

        // ── Hit/miss — only for cacheable GET endpoints ───────────────────────
        if ($isCacheable) {
            if ($cacheHit) {
                $pipe->incr('stats:cache:hits');
            } else {
                $pipe->incr('stats:cache:misses');
            }
        } else {
            // Count separately so the dashboard can show "uncacheable calls"
            $pipe->incr('stats:cache:uncacheable');
        }

        // ── Per-route + status (every request) ───────────────────────────────
        $pipe->hIncrBy('stats:routes', $routeKey, 1);
        $pipe->hIncrBy('stats:statuses', (string) $status, 1);

        // ── Recent log — skip monitoring noise ────────────────────────────────
        if (!$isMonitoring) {
            $entry = json_encode([
                'ts'     => $now->toIso8601String(),
                'method' => $method,
                'path'   => $path,
                'status' => $status,
                'ms'     => $durationMs,
                'source' => $isMutation ? 'db' : $source,
            ]);
            $pipe->lPush('stats:recent', $entry);
            $pipe->lTrim('stats:recent', 0, 99);
        }

        $pipe->execute();
    }
}
