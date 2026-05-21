<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompany
{
    public function handle(Request $request, Closure $next, string ...$companies): mixed
    {
        $user = $request->user();

        if (! $user || (! in_array($user->company, $companies) && $user->company !== 'telcovantage')) {
            return response()->json(['message' => 'Access denied for this platform.'], 403);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Your account is ' . $user->status . '. Please contact your administrator.'], 403);
        }

        if ($user->password_reset_required) {
            $currentPath = $request->path();
            if (! str_ends_with($currentPath, 'change-password')) {
                return response()->json([
                    'message'                => 'Password change required.',
                    'password_reset_required' => true,
                ], 403);
            }
        }

        // Update GPS if provided (mobile only)
        if ($request->filled('gps_lat') && $request->filled('gps_lng')) {
            $user->update([
                'current_gps_lat' => $request->gps_lat,
                'current_gps_lng' => $request->gps_lng,
            ]);
        }

        // Update last_seen_at
        $user->update(['last_seen_at' => now()]);

        return $next($request);
    }
}
