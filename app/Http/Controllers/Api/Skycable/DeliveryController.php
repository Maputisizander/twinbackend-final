<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\PickupRequest;
use App\Models\PullOutItem;
use App\Models\PullOutRequest;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    // ── Pickup Requests ───────────────────────────────────────────────────────

    public function pickupRequests(Request $request)
    {
        $query = PickupRequest::with(['fromWarehouse', 'toWarehouse', 'requestedBy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        return response()->json($query->latest()->paginate(30));
    }

    public function createPickupRequest(Request $request)
    {
        $data = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id'   => 'required|exists:warehouses,id',
        ]);

        $pr = PickupRequest::create(array_merge($data, [
            'requested_by' => $request->user()->id,
            'status'       => 'pending',
        ]));

        AuditLog::record('create', $pr, null, $pr->toArray());

        return response()->json($pr->load(['fromWarehouse', 'toWarehouse']), 201);
    }

    public function approvePickupRequest(Request $request, PickupRequest $pickupRequest)
    {
        $request->validate(['action' => 'required|in:approve,reject']);

        $old = $pickupRequest->toArray();
        $pickupRequest->update([
            'status'      => $request->action === 'approve' ? 'approved' : 'rejected',
            'approved_by' => $request->user()->id,
        ]);

        AuditLog::record('update', $pickupRequest, $old, $pickupRequest->toArray());

        return response()->json($pickupRequest);
    }

    // ── Deliveries ────────────────────────────────────────────────────────────

    public function deliveries(Request $request)
    {
        $query = Delivery::with(['fromWarehouse', 'toWarehouse', 'dispatchedBy', 'items'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        return response()->json($query->latest()->paginate(30));
    }

    public function dispatch(Request $request, PickupRequest $pickupRequest)
    {
        $data = $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.item_type' => 'required|string',
            'items.*.quantity'  => 'required|numeric|min:0.01',
            'items.*.unit'      => 'nullable|string',
        ]);

        $delivery = DB::transaction(function () use ($request, $pickupRequest, $data) {
            $delivery = Delivery::create([
                'pickup_request_id'  => $pickupRequest->id,
                'from_warehouse_id'  => $pickupRequest->from_warehouse_id,
                'to_warehouse_id'    => $pickupRequest->to_warehouse_id,
                'dispatched_by'      => $request->user()->id,
                'dispatched_at'      => now(),
                'status'             => 'in_transit',
            ]);

            foreach ($data['items'] as $item) {
                DeliveryItem::create(array_merge($item, ['delivery_id' => $delivery->id]));

                WarehouseStock::where('warehouse_id', $pickupRequest->from_warehouse_id)
                    ->where('item_type', $item['item_type'])
                    ->decrement('quantity', $item['quantity']);
            }

            $pickupRequest->update(['status' => 'dispatched']);
            AuditLog::record('create', $delivery, null, $delivery->toArray());

            return $delivery;
        });

        return response()->json($delivery->load('items'), 201);
    }

    public function accept(Request $request, Delivery $delivery)
    {
        DB::transaction(function () use ($request, $delivery) {
            $old = $delivery->toArray();
            $delivery->update([
                'status'      => 'accepted',
                'accepted_by' => $request->user()->id,
                'accepted_at' => now(),
                'arrived_at'  => now(),
            ]);

            foreach ($delivery->items as $item) {
                WarehouseStock::updateOrCreate(
                    ['warehouse_id' => $delivery->to_warehouse_id, 'item_type' => $item->item_type],
                    ['quantity' => DB::raw('quantity + ' . $item->quantity), 'unit' => $item->unit]
                );
            }

            AuditLog::record('update', $delivery, $old, $delivery->toArray());
        });

        return response()->json($delivery->fresh()->load('items'));
    }

    // ── Pull-Out Requests ─────────────────────────────────────────────────────

    public function pullOutRequests(Request $request)
    {
        $query = PullOutRequest::with(['warehouse', 'declaredBy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        return response()->json($query->latest()->paginate(30));
    }

    public function createPullOut(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'purpose'      => 'required|in:for_sale,for_delivery',
            'destination'  => 'nullable|string',
            'items'        => 'required|array|min:1',
            'items.*.item_type' => 'required|string',
            'items.*.quantity'  => 'required|numeric|min:0.01',
            'items.*.unit'      => 'nullable|string',
        ]);

        $pr = DB::transaction(function () use ($request, $data) {
            $pr = PullOutRequest::create([
                'warehouse_id' => $data['warehouse_id'],
                'purpose'      => $data['purpose'],
                'destination'  => $data['destination'] ?? null,
                'declared_by'  => $request->user()->id,
                'status'       => 'pending',
            ]);

            foreach ($data['items'] as $item) {
                PullOutItem::create(array_merge($item, ['pull_out_request_id' => $pr->id]));
            }

            AuditLog::record('create', $pr, null, $pr->toArray());
            return $pr;
        });

        return response()->json($pr->load('items'), 201);
    }

    public function approvePullOut(Request $request, PullOutRequest $pullOutRequest)
    {
        $request->validate(['action' => 'required|in:approve,reject']);

        $old = $pullOutRequest->toArray();
        $pullOutRequest->update([
            'status'      => $request->action === 'approve' ? 'approved' : 'rejected',
            'approved_by' => $request->user()->id,
        ]);

        AuditLog::record('update', $pullOutRequest, $old, $pullOutRequest->toArray());

        return response()->json($pullOutRequest);
    }
}
