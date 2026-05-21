<?php

namespace App\Http\Controllers\Api\Meralco;

use App\Http\Controllers\Controller;
use App\Models\Pole;
use Illuminate\Http\Request;

class PoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Pole::with(['barangay.city.province.region', 'cableSlots', 'napBoxes'])
            ->when($request->barangay_code, fn ($q) => $q->where('barangay_code', $request->barangay_code))
            ->when($request->skycable_status, fn ($q) => $q->where('skycable_status', $request->skycable_status))
            ->when($request->globe_status, fn ($q) => $q->where('globe_status', $request->globe_status))
            ->when($request->search, fn ($q) => $q->where('pole_code', 'like', '%' . $request->search . '%'));

        return response()->json($query->paginate(50));
    }

    public function show(Pole $pole)
    {
        return response()->json($pole->load([
            'barangay.city.province.region',
            'cableSlots',
            'napBoxes.ports',
        ]));
    }

    public function teardownProof(Pole $pole)
    {
        $skycableProof = null;
        $globeProof    = null;

        if ($pole->skycable_status === 'cleared') {
            $skycableSpanPoles = \App\Models\SkycablePole::where('pole_id', $pole->id)->with('node.spans.teardownReports')->get();
            $skycableProof = [
                'cleared_at' => $pole->skycable_cleared_at,
                'spans'      => $skycableSpanPoles->flatMap(fn ($sp) => $sp->node->spans)
                    ->map(fn ($span) => [
                        'span_code'         => $span->span_code,
                        'teardown_reports'  => $span->teardownReports->where('status', 'backend_approved')->values(),
                    ])->values(),
            ];
        }

        if ($pole->globe_status === 'cleared') {
            $globeProof = [
                'cleared_at' => $pole->globe_cleared_at,
                'tickets'    => \App\Models\GlobeTicket::where('pole_id', $pole->id)
                    ->where('status', 'completed')
                    ->with('teardownReport')
                    ->get(),
            ];
        }

        return response()->json([
            'pole'          => $pole->only(['id', 'pole_code', 'skycable_status', 'globe_status']),
            'skycable_proof' => $skycableProof,
            'globe_proof'    => $globeProof,
        ]);
    }

    public function summary()
    {
        return response()->json([
            'skycable' => [
                'active'   => Pole::where('skycable_status', 'active')->count(),
                'cleared'  => Pole::where('skycable_status', 'cleared')->count(),
                'pending'  => Pole::where('skycable_status', 'pending')->count(),
            ],
            'globe' => [
                'active'   => Pole::where('globe_status', 'active')->count(),
                'cleared'  => Pole::where('globe_status', 'cleared')->count(),
                'pending'  => Pole::where('globe_status', 'pending')->count(),
            ],
        ]);
    }
}
