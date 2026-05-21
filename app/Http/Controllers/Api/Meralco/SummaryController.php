<?php

namespace App\Http\Controllers\Api\Meralco;

use App\Http\Controllers\Controller;
use App\Models\GlobeTicket;
use App\Models\Pole;
use App\Models\SkycableNode;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function index()
    {
        return response()->json([
            'poles' => [
                'total'           => Pole::count(),
                'skycable_active' => Pole::where('skycable_status', 'active')->count(),
                'skycable_cleared'=> Pole::where('skycable_status', 'cleared')->count(),
                'globe_active'    => Pole::where('globe_status', 'active')->count(),
                'globe_cleared'   => Pole::where('globe_status', 'cleared')->count(),
                'fully_cleared'   => Pole::where('skycable_status', 'cleared')
                                         ->where('globe_status', 'cleared')
                                         ->count(),
            ],
            'skycable' => [
                'nodes_total'     => SkycableNode::count(),
                'nodes_completed' => SkycableNode::where('status', 'completed')->count(),
            ],
            'globe' => [
                'tickets_total'     => GlobeTicket::count(),
                'tickets_completed' => GlobeTicket::where('status', 'completed')->count(),
            ],
        ]);
    }
}
