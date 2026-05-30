<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\CachesApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PsgcBarangay;
use App\Models\PsgcCity;
use App\Models\PsgcProvince;
use App\Models\PsgcRegion;
use App\Services\RedisCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PSGC (Philippine Standard Geographic Code) endpoints.
 *
 * This data is static government-issued geography — it virtually never changes.
 * All responses are cached in Redis for 24 hours with ETag support.
 * Cache key is global (not user-scoped) because the data is identical for everyone.
 */
class PsgcController extends Controller
{
    use CachesApiResponse;

    public function regions(Request $request): JsonResponse
    {
        $key = RedisCache::globalKey('psgc:regions');

        return $this->cachedResponse(
            cacheKey:   $key,
            ttl:        RedisCache::TTL_PSGC,
            callback:   fn () => PsgcRegion::orderBy('name')->get(['code', 'name']),
            request:    $request,
            visibility: 'public',
        );
    }

    public function provinces(Request $request): JsonResponse
    {
        $params  = $request->only(['region_code']);
        $key     = RedisCache::globalKey('psgc:provinces', RedisCache::paramHash($params));

        return $this->cachedResponse(
            cacheKey:   $key,
            ttl:        RedisCache::TTL_PSGC,
            callback:   function () use ($request) {
                $query = PsgcProvince::orderBy('name');
                if ($request->filled('region_code')) {
                    $query->where('region_code', $request->region_code);
                }
                return $query->get(['code', 'name', 'region_code']);
            },
            request:    $request,
            visibility: 'public',
        );
    }

    public function cities(Request $request): JsonResponse
    {
        $params  = $request->only(['province_code']);
        $key     = RedisCache::globalKey('psgc:cities', RedisCache::paramHash($params));

        return $this->cachedResponse(
            cacheKey:   $key,
            ttl:        RedisCache::TTL_PSGC,
            callback:   function () use ($request) {
                $query = PsgcCity::orderBy('name');
                if ($request->filled('province_code')) {
                    $query->where('province_code', $request->province_code);
                }
                return $query->get(['code', 'name', 'province_code']);
            },
            request:    $request,
            visibility: 'public',
        );
    }

    public function barangays(Request $request): JsonResponse
    {
        $params  = $request->only(['city_code']);
        $key     = RedisCache::globalKey('psgc:barangays', RedisCache::paramHash($params));

        return $this->cachedResponse(
            cacheKey:   $key,
            ttl:        RedisCache::TTL_PSGC,
            callback:   function () use ($request) {
                $query = PsgcBarangay::orderBy('name');
                if ($request->filled('city_code')) {
                    $query->where('city_code', $request->city_code);
                }
                return $query->get(['code', 'name', 'city_code']);
            },
            request:    $request,
            visibility: 'public',
        );
    }
}
