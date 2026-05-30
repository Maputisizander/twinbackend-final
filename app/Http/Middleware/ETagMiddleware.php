<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Passive ETag middleware for endpoints that do NOT use CachesApiResponse.
 *
 * On every 200 GET/HEAD response:
 *   1. Generate ETag from the response body (MD5).
 *   2. Compare with the client's If-None-Match header.
 *   3. If they match → replace response with 304 (same headers, no body).
 *   4. Otherwise → attach ETag header and pass through unchanged.
 *
 * This is a fallback — controllers using CachesApiResponse already handle
 * ETag negotiation before hitting the database, which is far cheaper.
 * This middleware only catches endpoints that skip the trait.
 */
class ETagMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $response;
        }

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        // Controllers using CachesApiResponse already set ETag — skip re-hashing.
        $etag = $response->headers->get('ETag');

        if (! $etag) {
            $etag = '"' . md5((string) $response->getContent()) . '"';
            $response->headers->set('ETag', $etag);
        }

        if ($request->headers->has('If-None-Match')) {
            $clientEtag = $request->header('If-None-Match');

            if ($clientEtag === $etag) {
                $cacheControl = $response->headers->get('Cache-Control', 'private, must-revalidate');

                return response('', 304)
                    ->header('ETag', $etag)
                    ->header('Cache-Control', $cacheControl);
            }
        }

        return $response;
    }
}
