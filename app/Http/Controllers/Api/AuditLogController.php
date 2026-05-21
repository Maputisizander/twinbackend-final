<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')
            ->when($request->company, fn ($q) => $q->where('company', $request->company))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->action, fn ($q) => $q->where('action', $request->action))
            ->when($request->model_type, fn ($q) => $q->where('model_type', $request->model_type))
            ->when($request->model_id, fn ($q) => $q->where('model_id', $request->model_id))
            ->when($request->from, fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('created_at', '<=', $request->to));

        return response()->json($query->latest()->paginate(50));
    }

    public function show(AuditLog $auditLog)
    {
        return response()->json($auditLog->load('user'));
    }
}
