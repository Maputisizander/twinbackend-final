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
    // ── GET /api/v1/apistatus ─────────────────────────────────────────────────

    public function status(): JsonResponse
    {
        $redisOk   = false;
        $redisInfo = [];

        try {
            $conn    = Redis::connection('default');
            $conn->set('healthcheck', 'ok');
            $redisOk = $conn->get('healthcheck') === 'ok';
            $info    = $conn->info();
            $redisInfo = [
                'version'           => $info['redis_version']            ?? null,
                'uptime_days'       => $info['uptime_in_days']            ?? null,
                'used_memory'       => $info['used_memory_human']         ?? null,
                'peak_memory'       => $info['used_memory_peak_human']    ?? null,
                'connected_clients' => $info['connected_clients']         ?? null,
                'keyspace_hits'     => $info['keyspace_hits']             ?? null,
                'keyspace_misses'   => $info['keyspace_misses']           ?? null,
            ];
        } catch (\Throwable $e) {
            $redisInfo = ['status' => 'unavailable', 'note' => 'Stats stored in database cache'];
        }

        $dbOk = false;
        try { DB::select('SELECT 1'); $dbOk = true; } catch (\Throwable) {}

        $cacheDriver = config('cache.default');

        return response()->json([
            'status'    => $dbOk ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services'  => [
                'database'     => $dbOk    ? 'connected' : 'error',
                'redis'        => $redisOk ? 'connected' : 'unavailable',
                'cache_driver' => $cacheDriver,
            ],
            'redis' => $redisInfo,
        ]);
    }

    // ── GET /api/v1/apiconsumption ────────────────────────────────────────────

    public function consumption(Request $request): JsonResponse
    {
        $useRedis = $this->redisAvailable();
        $dateKey  = now()->format('Y-m-d');
        $hourKey  = now()->format('Y-m-d-H');

        // ── Read totals ───────────────────────────────────────────────────────
        if ($useRedis) {
            $redis       = Redis::connection('default');
            $total       = (int) ($redis->get('stats:calls:total')       ?? 0);
            $hits        = (int) ($redis->get('stats:cache:hits')        ?? 0);
            $misses      = (int) ($redis->get('stats:cache:misses')      ?? 0);
            $uncacheable = (int) ($redis->get('stats:cache:uncacheable') ?? 0);
            $today       = (int) ($redis->get("stats:calls:date:{$dateKey}") ?? 0);
            $thisHour    = (int) ($redis->get("stats:calls:hour:{$hourKey}") ?? 0);
        } else {
            $total       = (int) (Cache::get('stats:calls:total',       0));
            $hits        = (int) (Cache::get('stats:cache:hits',        0));
            $misses      = (int) (Cache::get('stats:cache:misses',      0));
            $uncacheable = (int) (Cache::get('stats:cache:uncacheable', 0));
            $today       = (int) (Cache::get("stats:calls:date:{$dateKey}", 0));
            $thisHour    = (int) (Cache::get("stats:calls:hour:{$hourKey}", 0));
        }

        $cacheable = $hits + $misses;
        $hitRate   = $cacheable > 0 ? round(($hits / $cacheable) * 100, 1) : null;

        // ── Top routes ────────────────────────────────────────────────────────
        if ($useRedis) {
            $routesRaw = $redis->hGetAll('stats:routes') ?: [];
            arsort($routesRaw);
            $topRoutes = array_slice(
                array_map(fn ($k, $v) => ['route' => $k, 'calls' => (int) $v],
                    array_keys($routesRaw), $routesRaw),
                0, 20
            );
        } else {
            $routesRaw = Cache::get('stats:routes', []);
            $topRoutes = array_slice(
                array_map(fn ($k, $v) => ['route' => $k, 'calls' => (int) $v],
                    array_keys($routesRaw), $routesRaw),
                0, 20
            );
        }

        // ── Status codes ──────────────────────────────────────────────────────
        if ($useRedis) {
            $statusesRaw = $redis->hGetAll('stats:statuses') ?: [];
        } else {
            $statusesRaw = Cache::get('stats:statuses', []);
        }
        ksort($statusesRaw);
        $statuses = array_map(fn ($k, $v) => ['status' => (int) $k, 'count' => (int) $v],
            array_keys($statusesRaw), $statusesRaw);

        // ── Recent log ────────────────────────────────────────────────────────
        if ($useRedis) {
            $recentRaw = $redis->lRange('stats:recent', 0, 19) ?: [];
            $recent    = array_map(fn ($r) => json_decode($r, true), $recentRaw);
        } else {
            $recentRaw = Cache::get('stats:recent', []);
            $recent    = array_map(fn ($r) => json_decode($r, true), array_slice($recentRaw, 0, 20));
        }

        // ── Daily chart — last 8 days ─────────────────────────────────────────
        $daily = [];
        for ($i = 7; $i >= 0; $i--) {
            $day     = now()->subDays($i)->format('Y-m-d');
            $daily[] = [
                'date'  => $day,
                'calls' => (int) ($useRedis
                    ? ($redis->get("stats:calls:date:{$day}") ?? 0)
                    : Cache::get("stats:calls:date:{$day}", 0)),
            ];
        }

        // ── Hourly chart — last 24 hours ──────────────────────────────────────
        $hourly = [];
        for ($i = 23; $i >= 0; $i--) {
            $h       = now()->subHours($i)->format('Y-m-d-H');
            $hourly[] = [
                'hour'  => now()->subHours($i)->format('H:00'),
                'calls' => (int) ($useRedis
                    ? ($redis->get("stats:calls:hour:{$h}") ?? 0)
                    : Cache::get("stats:calls:hour:{$h}", 0)),
            ];
        }

        return response()->json([
            'summary' => [
                'total_calls'       => $total,
                'calls_today'       => $today,
                'calls_this_hour'   => $thisHour,
                'cache_hits'        => $hits,
                'cache_misses'      => $misses,
                'uncacheable_calls' => $uncacheable,
                'hit_rate_percent'  => $hitRate,
                'hit_rate_note'     => 'GET cacheable endpoints only',
                'saved_db_queries'  => $hits,
                'stats_driver'      => $useRedis ? 'redis' : config('cache.default'),
            ],
            'top_routes'   => $topRoutes,
            'status_codes' => $statuses,
            'recent'       => $recent,
            'daily_chart'  => $daily,
            'hourly_chart' => $hourly,
        ]);
    }

    // ── DELETE /api/v1/apiconsumption/reset ───────────────────────────────────

    public function reset(): JsonResponse
    {
        try {
            if ($this->redisAvailable()) {
                $redis = Redis::connection('default');
                $keys  = $redis->keys('stats:*');
                foreach ($keys as $key) {
                    $prefix = config('database.redis.options.prefix', '');
                    $bare   = $prefix ? ltrim(str_replace($prefix, '', $key), ':') : $key;
                    $redis->del($bare);
                }
            } else {
                // Cache driver reset
                foreach (['stats:calls:total','stats:cache:hits','stats:cache:misses',
                          'stats:cache:uncacheable','stats:routes','stats:statuses','stats:recent'] as $k) {
                    Cache::forget($k);
                }
                for ($i = 0; $i <= 7; $i++) {
                    Cache::forget('stats:calls:date:' . now()->subDays($i)->format('Y-m-d'));
                }
                for ($i = 0; $i <= 23; $i++) {
                    Cache::forget('stats:calls:hour:' . now()->subHours($i)->format('Y-m-d-H'));
                }
            }

            return response()->json(['message' => 'Stats reset.']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }

    // ── Helper ────────────────────────────────────────────────────────────────

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
