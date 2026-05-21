<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PsgcBarangay;
use App\Models\PsgcCity;
use App\Models\PsgcProvince;
use App\Models\PsgcRegion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PsgcController extends Controller
{
    public function regions(): JsonResponse
    {
        return response()->json(PsgcRegion::orderBy('name')->get(['code', 'name']));
    }

    public function provinces(Request $request): JsonResponse
    {
        $query = PsgcProvince::orderBy('name');

        if ($request->filled('region_code')) {
            $query->where('region_code', $request->region_code);
        }

        return response()->json($query->get(['code', 'name', 'region_code']));
    }

    public function cities(Request $request): JsonResponse
    {
        $query = PsgcCity::orderBy('name');

        if ($request->filled('province_code')) {
            $query->where('province_code', $request->province_code);
        }

        return response()->json($query->get(['code', 'name', 'province_code']));
    }

    public function barangays(Request $request): JsonResponse
    {
        $query = PsgcBarangay::orderBy('name');

        if ($request->filled('city_code')) {
            $query->where('city_code', $request->city_code);
        }

        return response()->json($query->get(['code', 'name', 'city_code']));
    }
}
