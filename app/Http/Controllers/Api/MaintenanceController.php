<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MaintenanceController extends Controller
{
    private const COMPANIES = ['skycable', 'globe', 'meralco'];
    private const CACHE_KEY = 'maintenance:status';
    private const CACHE_TTL = 86400 * 30;

    // ── GET /api/v1/maintenance  (public — no auth) ───────────────────────────

    public function status(): JsonResponse
    {
        $state     = Cache::get(self::CACHE_KEY, []);
        $companies = [];

        foreach (self::COMPANIES as $co) {
            $info = $state[$co] ?? null;
            $companies[$co] = [
                'active'     => (bool) ($info['active']     ?? false),
                'message'    => $info['message']    ?? null,
                'started_at' => $info['started_at'] ?? null,
                'set_by'     => $info['set_by']     ?? null,
            ];
        }

        return response()->json([
            'any_active' => collect($companies)->contains('active', true),
            'companies'  => $companies,
        ]);
    }

    // ── POST /api/v1/admin/maintenance  (admin only) ──────────────────────────
    // Body: { company: "skycable|globe|meralco|all", active: true|false, message?: string }

    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company' => 'required|in:skycable,globe,meralco,all',
            'active'  => 'required|boolean',
            'message' => 'nullable|string|max:300',
        ]);

        $state   = Cache::get(self::CACHE_KEY, []);
        $targets = $data['company'] === 'all' ? self::COMPANIES : [$data['company']];

        foreach ($targets as $co) {
            $state[$co] = $data['active'] ? [
                'active'     => true,
                'message'    => $data['message'] ?? 'System is under maintenance. We\'ll be back shortly.',
                'started_at' => now()->toIso8601String(),
                'set_by'     => $request->user()?->name ?? 'Admin',
            ] : [
                'active' => false, 'message' => null, 'started_at' => null, 'set_by' => null,
            ];
        }

        Cache::put(self::CACHE_KEY, $state, self::CACHE_TTL);

        return response()->json(['message' => 'Updated.', 'companies' => $state]);
    }

    // ── DELETE /api/v1/admin/maintenance/lift-all ─────────────────────────────

    public function liftAll(): JsonResponse
    {
        Cache::forget(self::CACHE_KEY);
        return response()->json(['message' => 'All maintenance lifted.']);
    }
}
