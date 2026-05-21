<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $query = Team::with(['subcontractor', 'members'])
            ->when($request->company, fn ($q) => $q->where('company', $request->company))
            ->when($request->subcontractor_id, fn ($q) => $q->where('subcontractor_id', $request->subcontractor_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        return response()->json($query->paginate(30));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company'          => 'required|in:skycable,globe',
            'subcontractor_id' => 'nullable|exists:subcontractors,id',
            'name'             => 'required|string|max:255',
            'status'           => 'nullable|in:active,inactive',
        ]);

        $team = Team::create($data);
        AuditLog::record('create', $team, null, $team->toArray());

        return response()->json($team, 201);
    }

    public function show(Team $team)
    {
        return response()->json($team->load(['subcontractor', 'members']));
    }

    public function update(Request $request, Team $team)
    {
        $data = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'subcontractor_id' => 'sometimes|nullable|exists:subcontractors,id',
            'status'           => 'sometimes|in:active,inactive',
        ]);

        $old = $team->toArray();
        $team->update($data);
        AuditLog::record('update', $team, $old, $team->toArray());

        return response()->json($team);
    }

    public function destroy(Team $team)
    {
        AuditLog::record('delete', $team, $team->toArray(), null);
        $team->delete();

        return response()->json(['message' => 'Team deleted.']);
    }

    public function addMember(Request $request, Team $team)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'nullable|string|max:100',
        ]);

        $team->members()->syncWithoutDetaching([
            $data['user_id'] => ['role' => $data['role'] ?? 'member']
        ]);

        AuditLog::record('update', $team, null, ['member_added' => $data['user_id']]);

        return response()->json($team->load('members'));
    }

    public function removeMember(Request $request, Team $team)
    {
        $data = $request->validate(['user_id' => 'required|exists:users,id']);

        $team->members()->detach($data['user_id']);
        AuditLog::record('update', $team, null, ['member_removed' => $data['user_id']]);

        return response()->json(['message' => 'Member removed.']);
    }
}
