<?php

namespace App\Http\Controllers\Api\Globe;

use App\Http\Concerns\CachesApiResponse;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pole;
use App\Services\RedisCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PoleController extends Controller
{
    use CachesApiResponse;

    public function index(Request $request): JsonResponse
    {
        $params  = $request->only(['barangay_code', 'globe_status', 'search', 'page', 'per_page']);
        $cacheKey = RedisCache::globalKey('globe:poles', RedisCache::paramHash($params));

        return $this->cachedResponse(
            cacheKey:   $cacheKey,
            ttl:        RedisCache::TTL_POLES,
            callback:   function () use ($request) {
                return Pole::with(['barangay'])
                    ->when($request->barangay_code, fn ($q) => $q->where('barangay_code', $request->barangay_code))
                    ->when($request->globe_status,   fn ($q) => $q->where('globe_status', $request->globe_status))
                    ->when($request->search,         fn ($q) => $q->where('pole_code', 'like', '%' . $request->search . '%'))
                    ->paginate(50);
            },
            request:    $request,
            visibility: 'public',
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pole_code'     => 'required|string|unique:poles,pole_code',
            'barangay_code' => 'nullable|string|max:20|exists:barangays,code',
            'lat'           => 'nullable|numeric',
            'lng'           => 'nullable|numeric',
        ]);

        $pole = Pole::create($data);
        AuditLog::record('create', $pole, null, $pole->toArray());

        // Bust all globe:poles cache keys so the new pole appears in listings
        $this->bustCachePattern('cache:global:globe:poles:*');

        return response()->json($pole->load('barangay'), 201);
    }

    public function show(Request $request, Pole $pole): JsonResponse
    {
        $cacheKey = RedisCache::globalKey('globe:pole', (string) $pole->id);

        return $this->cachedResponse(
            cacheKey:   $cacheKey,
            ttl:        RedisCache::TTL_DETAIL,
            callback:   fn () => $pole->load('barangay'),
            request:    $request,
            visibility: 'public',
        );
    }
}
