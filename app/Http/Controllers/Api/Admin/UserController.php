<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withTrashed()
            ->with(['subcontractor', 'team'])
            ->when($request->company, fn ($q) => $q->where('company', $request->company))
            ->when($request->role, fn ($q) => $q->where('role', $request->role))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->subcontractor_id, fn ($q) => $q->where(function ($inner) use ($request) {
                $inner->where('subcontractor_id', $request->subcontractor_id)
                      ->orWhereHas('team', fn ($t) => $t->where('subcontractor_id', $request->subcontractor_id));
            }))
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('first_name', 'like', '%' . $request->search . '%')
                   ->orWhere('last_name', 'like', '%' . $request->search . '%')
                   ->orWhere('email', 'like', '%' . $request->search . '%');
            }));

        $perPage = min((int) ($request->per_page ?? 30), 200);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company'          => 'required|in:skycable,globe,meralco,telcovantage',
            'role'             => 'required|string',
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'email'            => 'required|email|unique:users,email',
            'cellphone'        => 'nullable|string|max:20',
            'address'          => 'nullable|string',
            'subcontractor_id' => 'nullable|exists:subcontractors,id',
            'team_id'          => 'nullable|exists:teams,id',
            'project_access'       => 'nullable|array',
            'status'               => 'nullable|in:active,inactive,on_hold',
            'can_approve_delivery' => 'nullable|boolean',
        ]);

        $tempPassword = Str::random(12);

        $user = User::create(array_merge($data, [
            'password'                => Hash::make($tempPassword),
            'password_reset_required' => true,
            'temp_password_set_at'    => now(),
            'status'                  => $data['status'] ?? 'active',
        ]));

        AuditLog::record('create', $user, null, array_merge($user->toArray(), ['temp_password' => $tempPassword]));

        return response()->json([
            'user'          => $user,
            'temp_password' => $tempPassword,
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load(['subcontractor', 'team']));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role'             => 'sometimes|string',
            'first_name'       => 'sometimes|string|max:100',
            'last_name'        => 'sometimes|string|max:100',
            'cellphone'        => 'sometimes|nullable|string|max:20',
            'address'          => 'sometimes|nullable|string',
            'subcontractor_id' => 'sometimes|nullable|exists:subcontractors,id',
            'team_id'          => 'sometimes|nullable|exists:teams,id',
            'project_access'       => 'sometimes|nullable|array',
            'can_approve_delivery' => 'sometimes|boolean',
        ]);

        $old = $user->toArray();
        $user->update($data);
        AuditLog::record('update', $user, $old, $user->toArray());

        return response()->json($user->fresh()->load(['subcontractor', 'team']));
    }

    public function updateStatus(Request $request, User $user)
    {
        $data = $request->validate(['status' => 'required|in:active,inactive,on_hold']);

        $old = $user->toArray();
        $user->update($data);
        AuditLog::record('update', $user, $old, $user->toArray());

        return response()->json($user);
    }

    public function resetPassword(User $user)
    {
        $tempPassword = Str::random(12);

        $old = $user->toArray();
        $user->update([
            'password'                => Hash::make($tempPassword),
            'password_reset_required' => true,
            'temp_password_set_at'    => now(),
        ]);
        $user->tokens()->delete();

        AuditLog::record('update', $user, $old, ['password_reset' => true]);

        return response()->json(['temp_password' => $tempPassword]);
    }

    public function destroy(User $user)
    {
        AuditLog::record('delete', $user, $user->toArray(), null);
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    public function restore(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        AuditLog::record('update', $user, ['deleted_at' => $user->deleted_at], ['restored' => true]);

        return response()->json($user);
    }
}
