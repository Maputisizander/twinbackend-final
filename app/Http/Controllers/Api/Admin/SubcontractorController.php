<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subcontractor;
use Illuminate\Http\Request;

class SubcontractorController extends Controller
{
    public function index(Request $request)
    {
        $query = Subcontractor::with(['teams', 'warehouses'])
            ->when($request->company, fn ($q) => $q->where('company', $request->company))
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        return response()->json($query->paginate(100));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company'       => 'required|in:skycable,globe',
            'name'          => 'required|string|max:255',
            'contact_name'  => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email',
            'address'       => 'nullable|string',
            'status'        => 'nullable|in:active,inactive',
        ]);

        $subcon = Subcontractor::create($data);
        AuditLog::record('create', $subcon, null, $subcon->toArray());

        return response()->json($subcon->load('warehouses'), 201);
    }

    public function show(Subcontractor $subcontractor)
    {
        return response()->json($subcontractor->load(['teams.members', 'warehouses.stocks']));
    }

    public function update(Request $request, Subcontractor $subcontractor)
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'contact_name'  => 'sometimes|nullable|string|max:255',
            'contact_phone' => 'sometimes|nullable|string|max:20',
            'contact_email' => 'sometimes|nullable|email',
            'address'       => 'sometimes|nullable|string',
            'status'        => 'sometimes|in:active,inactive',
        ]);

        $old = $subcontractor->toArray();
        $subcontractor->update($data);
        AuditLog::record('update', $subcontractor, $old, $subcontractor->toArray());

        return response()->json($subcontractor);
    }

    public function destroy(Subcontractor $subcontractor)
    {
        AuditLog::record('delete', $subcontractor, $subcontractor->toArray(), null);
        $subcontractor->delete();

        return response()->json(['message' => 'Subcontractor deleted.']);
    }
}
