<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsBuiltApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-AsBuilt-Key')
            ?? $request->query('api_key');

        if (!$key || $key !== config('app.asbuilt_api_key')) {
            return response()->json([
                'message' => 'Unauthorized. Provide a valid X-AsBuilt-Key header.',
            ], 401);
        }

        return $next($request);
    }
}
