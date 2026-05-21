<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ActiveUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->with(['team', 'subcontractor']);

        // Scope to company for non-admin requests
        if ($request->company) {
            $query->where('company', $request->company);
        }

        $users = $query->get()->map(fn ($u) => [
            'id'         => $u->id,
            'name'       => $u->full_name,
            'role'       => $u->role,
            'company'    => $u->company,
            'team'       => optional($u->team)->name,
            'last_seen'  => $u->last_seen_at,
            'lat'        => $u->current_lat,
            'lng'        => $u->current_lng,
        ]);

        return response()->json(['count' => $users->count(), 'users' => $users]);
    }
}
