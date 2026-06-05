<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ApiStatusController extends Controller
{
    /**
     * GET /api/v1/apistatus
     *
     * Quick health-check + Redis connection info.
     */
    public function status(): JsonResponse
    {
        $redisOk   = false;
        $redisInfo = [];

        try {
            $conn = Redis::connection('default');
            $conn->set('healthcheck', 'ok');
            $redisOk = $conn->get('healthcheck') === 'ok';

            $info = $conn->info();
            $redisInfo = [
                'version'            => $info['redis_version']       ?? null,
                'uptime_days'        => $info['uptime_in_days']       ?? null,
                'used_memory'        => $info['used_memory_human']    ?? null,
                'peak_memory'        => $info['used_memory_peak_human'] ?? null,
                'connected_clients'  => $info['connected_clients']    ?? null,
                'total_commands'     => $info['total_commands_processed'] ?? null,
                'keyspace_hits'      => $info['keyspace_hits']        ?? null,
                'keyspace_misses'    => $info['keyspace_misses']      ?? null,
            ];
        } catch (\Throwable $e) {
            $redisInfo = ['error' => $e->getMessage()];
        }

        $dbOk = false;
        try {
            DB::select('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {}

        return response()->json([
            'status'      => ($redisOk && $dbOk) ? 'ok' : 'degraded',
            'timestamp'   => now()->toIso8601String(),
            'services'    => [
                'database' => $dbOk   ? 'connected' : 'error',
                'redis'    => $redisOk ? 'connected' : 'error',
                'cache_driver' => config('cache.default'),
            ],
            'redis' => $redisInfo,
        ]);
    }

    /**
     * GET /api/v1/apiconsumption
     *
     * Full API call statistics from Redis:
     *   - total / today / this hour
     *   - cache hit rate
     *   - top routes
     *   - status code breakdown
     *   - last 20 requests
     *   - last 8 days chart data
     */
    public function consumption(Request $request): JsonResponse
    {
        try {
            $redis = Redis::connection('default');

            // ── Totals ────────────────────────────────────────────────────────
            $total       = (int) ($redis->get('stats:calls:total')          ?? 0);
            $hits        = (int) ($redis->get('stats:cache:hits')           ?? 0);
            $misses      = (int) ($redis->get('stats:cache:misses')         ?? 0);
            $uncacheable = (int) ($redis->get('stats:cache:uncacheable')    ?? 0);

            $dateKey  = now()->format('Y-m-d');
            $hourKey  = now()->format('Y-m-d-H');
            $today    = (int) ($redis->get("stats:calls:date:{$dateKey}")   ?? 0);
            $thisHour = (int) ($redis->get("stats:calls:hour:{$hourKey}")   ?? 0);

            // Hit rate = Redis hits / (Redis hits + DB misses) for cacheable GETs only
            // Excludes: ping, apistatus, apiconsumption, POST/PUT/PATCH/DELETE
            $cacheable = $hits + $misses;
            $hitRate   = $cacheable > 0 ? round(($hits / $cacheable) * 100, 1) : null;

            // ── Top routes (sorted by count, top 20) ─────────────────────────
            $routesRaw = $redis->hGetAll('stats:routes') ?: [];
            arsort($routesRaw);
            $topRoutes = array_slice(
                array_map(fn ($k, $v) => ['route' => $k, 'calls' => (int) $v], array_keys($routesRaw), $routesRaw),
                0, 20
            );

            // ── Status code breakdown ─────────────────────────────────────────
            $statusesRaw = $redis->hGetAll('stats:statuses') ?: [];
            ksort($statusesRaw);
            $statuses = array_map(fn ($k, $v) => ['status' => (int) $k, 'count' => (int) $v],
                array_keys($statusesRaw), $statusesRaw);

            // ── Last 20 requests ──────────────────────────────────────────────
            $recentRaw = $redis->lRange('stats:recent', 0, 19) ?: [];
            $recent = array_map(fn ($r) => json_decode($r, true), $recentRaw);

            // ── Daily chart — last 8 days ─────────────────────────────────────
            $daily = [];
            for ($i = 7; $i >= 0; $i--) {
                $day = now()->subDays($i)->format('Y-m-d');
                $daily[] = [
                    'date'  => $day,
                    'calls' => (int) ($redis->get("stats:calls:date:{$day}") ?? 0),
                ];
            }

            // ── Hourly chart — last 24 hours ──────────────────────────────────
            $hourly = [];
            for ($i = 23; $i >= 0; $i--) {
                $h = now()->subHours($i)->format('Y-m-d-H');
                $label = now()->subHours($i)->format('H:00');
                $hourly[] = [
                    'hour'  => $label,
                    'calls' => (int) ($redis->get("stats:calls:hour:{$h}") ?? 0),
                ];
            }

            return response()->json([
                'summary' => [
                    'total_calls'          => $total,
                    'calls_today'          => $today,
                    'calls_this_hour'      => $thisHour,
                    'cache_hits'           => $hits,
                    'cache_misses'         => $misses,
                    'uncacheable_calls'    => $uncacheable,
                    'hit_rate_percent'     => $hitRate,
                    'hit_rate_note'        => 'GET cacheable endpoints only (excludes ping, monitoring, mutations)',
                    'saved_db_queries'     => $hits,
                ],
                'top_routes'    => $topRoutes,
                'status_codes'  => $statuses,
                'recent'        => $recent,
                'daily_chart'   => $daily,
                'hourly_chart'  => $hourly,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Redis unavailable — stats require Redis.',
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * DELETE /api/v1/apiconsumption/reset
     * Clear all stats counters (admin only).
     */
    public function reset(): JsonResponse
    {
        try {
            $redis = Redis::connection('default');
            $keys  = $redis->keys('stats:*');
            foreach ($keys as $key) {
                // Strip Laravel cache prefix before deleting
                $prefix = config('database.redis.options.prefix', '');
                $bare   = $prefix ? ltrim(str_replace($prefix, '', $key), ':') : $key;
                $redis->del($bare);
            }
            return response()->json(['message' => 'Stats reset.']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }
}
