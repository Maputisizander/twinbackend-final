<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs every API request as a structured JSON line.
 *
 * Fields logged:
 *   ts              ISO-8601 timestamp (UTC)
 *   method          HTTP verb
 *   path            URL path (no query string)
 *   query           Query params (for cache-key analysis)
 *   status          HTTP status code
 *   duration_ms     Wall-clock time from first byte in to last byte out
 *   user_id         Authenticated user ID (null for public routes)
 *   company         User company (globe/skycable/meralco/telcovantage)
 *   cache_hit       true  → response served from cache (Redis or ETag 304)
 *   redis_hit       true  → Redis had the data (no DB query)
 *   db_queried      true  → DB was hit for this request
 *   is_304          true  → returned 304 Not Modified
 *   client_version  X-App-Version header value
 *   user_agent      Truncated User-Agent
 *
 * Logs to the 'api' channel (configure in config/logging.php).
 * Falls back to the default log channel if 'api' is not configured.
 */
class ApiRequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $startMs = microtime(true) * 1000;

        /** @var Response $response */
        $response = $next($request);

        $durationMs = round(microtime(true) * 1000 - $startMs, 1);

        // Attributes set by CachesApiResponse trait
        $cacheHit  = (bool) $request->attributes->get('_cache_hit', false);
        $redisHit  = (bool) $request->attributes->get('_redis_hit', false);
        $dbQueried = (bool) $request->attributes->get('_db_queried', ! $cacheHit);

        $status = $response->getStatusCode();
        $is304  = $status === 304;

        try {
            $user = $request->user();
        } catch (\Throwable) {
            $user = null;
        }

        $userAgent = $request->userAgent() ?? '';
        if (strlen($userAgent) > 120) {
            $userAgent = substr($userAgent, 0, 117) . '...';
        }

        $entry = [
            'ts'             => now()->toISOString(),
            'method'         => $request->method(),
            'path'           => $request->path(),
            'query'          => $request->query() ?: null,
            'status'         => $status,
            'duration_ms'    => $durationMs,
            'user_id'        => $user?->id,
            'company'        => $user?->company,
            'cache_hit'      => $is304 ? true : $cacheHit,
            'redis_hit'      => $is304 ? true : $redisHit,
            'db_queried'     => $is304 ? false : $dbQueried,
            'is_304'         => $is304,
            'client_version' => $request->header('X-App-Version'),
            'user_agent'     => $userAgent,
        ];

        try {
            Log::channel('api')->info('api_request', $entry);
        } catch (\Throwable) {
            // Never crash a request because of logging
            Log::info('api_request', $entry);
        }

        return $response;
    }
}
