<?php

namespace App\Http\Controllers\Api\Globe;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pole;
use Illuminate\Http\Request;

class PoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Pole::with(['barangay'])
            ->when($request->barangay_code, fn ($q) => $q->where('barangay_code', $request->barangay_code))
            ->when($request->globe_status,   fn ($q) => $q->where('globe_status', $request->globe_status))
            ->when($request->search,         fn ($q) => $q->where('pole_code', 'like', '%' . $request->search . '%'));

        return response()->json($query->paginate(50));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pole_code'     => 'required|string|unique:poles,pole_code',
            'barangay_code' => 'nullable|string|max:20|exists:barangays,code',
            'lat'           => 'nullable|numeric',
            'lng'           => 'nullable|numeric',
        ]);

        $pole = Pole::create($data);
        AuditLog::record('create', $pole, null, $pole->toArray());

        return response()->json($pole->load('barangay'), 201);
    }

    public function show(Pole $pole)
    {
        return response()->json($pole->load('barangay'));
    }
}
