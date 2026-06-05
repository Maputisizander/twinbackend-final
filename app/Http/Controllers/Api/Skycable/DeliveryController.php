<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\LinemanLocation;
use App\Models\PullOutItem;
use App\Models\PullOutRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptSource;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    // ── Pull-out requests ─────────────────────────────────────────────────────

    /** GET /skycable/pull-out-requests */
    public function pullOutRequests(Request $request)
    {
        $user  = $request->user();
        $query = PullOutRequest::with(['warehouse', 'toWarehouse', 'declaredBy', 'approvedBy', 'items']);

        if (!($user->is_admin ?? false) && !($user->is_executive ?? false)) {
            if ($user->subcontractor_id) {
                // Subcon: only their own warehouses' pull-outs
                $query->whereHas('warehouse', fn($q) => $q->where('subcontractor_id', $user->subcontractor_id));
            } else {
                // Non-admin with no subcontractor sees nothing
                $query->whereRaw('0 = 1');
            }
        }

        return response()->json($query->latest()->get());
    }

    /** POST /skycable/pull-out-requests */
    public function createPullOut(Request $request)
    {
        $data = $request->validate([
            'warehouse_id'      => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'nullable|exists:warehouses,id',
            'items'             => 'required|array|min:1',
            'items.*.item_type' => 'required|string',
            'items.*.quantity'  => 'required|numeric|min:0.01',
            'items.*.unit'      => 'nullable|string',
            'notes'             => 'nullable|string|max:500',
        ]);

        $toWarehouseId = $data['to_warehouse_id']
            ?? Warehouse::where('type', 'main')->value('id');

        if (!$toWarehouseId) {
            return response()->json(['message' => 'No main warehouse configured.'], 422);
        }

        $pullOut = DB::transaction(function () use ($request, $data, $toWarehouseId) {
            $pullOut = PullOutRequest::create([
                'warehouse_id'    => $data['warehouse_id'],
                'to_warehouse_id' => $toWarehouseId,
                'purpose'         => 'for_delivery',
                'declared_by'     => $request->user()->id,
                'notes'           => $data['notes'] ?? null,
                'status'          => 'pending',
            ]);

            foreach ($data['items'] as $item) {
                PullOutItem::create([
                    'pull_out_request_id' => $pullOut->id,
                    'item_type'           => $item['item_type'],
                    'quantity'            => $item['quantity'],
                    'unit'                => $item['unit'] ?? ($item['item_type'] === 'cable' ? 'm' : 'pcs'),
                ]);
            }

            AuditLog::record('create', $pullOut, null, $pullOut->toArray());
            return $pullOut;
        });

        // Notify all admins about new pull-out request
        $fromWarehouse = \App\Models\Warehouse::find($pullOut->warehouse_id);
        AppNotification::notifyAdmins(
            'pull_out_new',
            'New Pull-Out Request',
            'From ' . ($fromWarehouse?->name ?? "Warehouse #{$pullOut->warehouse_id}") . ' · ' . count($data['items']) . ' item type(s)',
            ['pull_out_id' => $pullOut->id, 'warehouse_id' => $pullOut->warehouse_id]
        );

        return response()->json($pullOut->load(['warehouse', 'toWarehouse', 'items', 'declaredBy']), 201);
    }

    /** PUT /skycable/pull-out-requests/{pullOutRequest}/approve */
    public function approvePullOut(Request $request, PullOutRequest $pullOutRequest)
    {
        $data = $request->validate([
            'action'    => 'required|in:approve,reject',
            'driver_id' => 'nullable|exists:users,id',
            'notes'     => 'nullable|string|max:500',
        ]);
        $old = $pullOutRequest->toArray();

        if ($data['action'] === 'reject') {
            $pullOutRequest->update([
                'status'      => 'rejected',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'notes'       => $data['notes'] ?? $pullOutRequest->notes,
            ]);

            // Notify requester
            AppNotification::send(
                $pullOutRequest->declared_by,
                'pull_out_rejected',
                'Pull-Out Request Rejected',
                'Your request from ' . ($pullOutRequest->warehouse?->name ?? "Warehouse #{$pullOutRequest->warehouse_id}") . ' was rejected.' . ($data['notes'] ? ' Note: ' . $data['notes'] : ''),
                ['pull_out_id' => $pullOutRequest->id]
            );

            return response()->json($pullOutRequest->fresh()->load(['warehouse', 'toWarehouse', 'items']));
        }

        $delivery = DB::transaction(function () use ($request, $data, $pullOutRequest, $old) {
            $pullOutRequest->update([
                'status'      => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $delivery = Delivery::create([
                'pickup_request_id' => null,
                'from_warehouse_id' => $pullOutRequest->warehouse_id,
                'to_warehouse_id'   => $pullOutRequest->to_warehouse_id,
                'dispatched_by'     => $request->user()->id,
                'driver_id'         => $data['driver_id'] ?? null,
                'status'            => 'pending',
            ]);

            foreach ($pullOutRequest->items as $item) {
                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'item_type'   => $item->item_type,
                    'quantity'    => $item->quantity,
                    'unit'        => $item->unit,
                ]);
            }

            AuditLog::record('update', $pullOutRequest, $old, $pullOutRequest->fresh()->toArray());
            return $delivery;
        });

        // Notify requester their pull-out was approved
        AppNotification::send(
            $pullOutRequest->declared_by,
            'pull_out_approved',
            'Pull-Out Request Approved',
            'Your request from ' . ($pullOutRequest->warehouse?->name ?? "Warehouse #{$pullOutRequest->warehouse_id}") . ' has been approved. Delivery is being arranged.',
            ['pull_out_id' => $pullOutRequest->id, 'delivery_id' => $delivery->id]
        );

        // Notify assigned driver if one was set
        if ($data['driver_id'] ?? null) {
            AppNotification::send(
                $data['driver_id'],
                'pull_out_approved',
                'New Delivery Assigned',
                'You have been assigned a delivery from ' . ($pullOutRequest->warehouse?->name ?? "Warehouse #{$pullOutRequest->warehouse_id}") . '.',
                ['delivery_id' => $delivery->id]
            );
        }

        return response()->json([
            'pull_out' => $pullOutRequest->fresh()->load(['warehouse', 'toWarehouse', 'items', 'approvedBy']),
            'delivery' => $delivery->load(['fromWarehouse', 'toWarehouse', 'items', 'driver']),
        ]);
    }

    // ── Deliveries ────────────────────────────────────────────────────────────

    /** GET /skycable/deliveries */
    public function deliveries(Request $request)
    {
        $user  = $request->user();
        $query = Delivery::with(['fromWarehouse', 'toWarehouse', 'items', 'dispatchedBy', 'acceptedBy']);

        if (!($user->is_admin ?? false) && !($user->is_executive ?? false)) {
            if ($user->subcontractor_id) {
                // Subcon: only deliveries from/to their own warehouses
                $query->where(function ($q) use ($user) {
                    $q->whereHas('fromWarehouse', fn($wq) => $wq->where('subcontractor_id', $user->subcontractor_id))
                      ->orWhereHas('toWarehouse', fn($wq) => $wq->where('subcontractor_id', $user->subcontractor_id));
                });
            } else {
                // Non-admin with no subcontractor sees nothing
                $query->whereRaw('0 = 1');
            }
        }

        return response()->json($query->latest()->get());
    }

    /** POST /skycable/deliveries/{pullOutRequest}/dispatch */
    public function dispatch(Request $request, PullOutRequest $pullOutRequest)
    {
        if ($pullOutRequest->status !== 'approved') {
            return response()->json(['message' => 'Pull-out must be approved before dispatch.'], 422);
        }

        $delivery = Delivery::where('from_warehouse_id', $pullOutRequest->warehouse_id)
            ->where('status', 'pending')
            ->latest()->first();

        if (!$delivery) {
            return response()->json(['message' => 'No pending delivery found for this pull-out.'], 404);
        }

        $delivery->update([
            'status'        => 'in_transit',
            'dispatched_by' => $request->user()->id,
            'dispatched_at' => now(),
        ]);

        $pullOutRequest->update(['status' => 'dispatched']);

        return response()->json($delivery->fresh()->load(['fromWarehouse', 'toWarehouse', 'items']));
    }

    /** POST /skycable/deliveries/{delivery}/arrive — driver marks self as arrived */
    public function arrive(Request $request, Delivery $delivery)
    {
        if (!in_array($delivery->status, ['in_transit', 'pending'])) {
            return response()->json(['message' => 'Delivery is not active.'], 422);
        }

        $delivery->update([
            'status'     => 'arrived',
            'arrived_at' => now(),
        ]);

        // Sync pull-out status
        PullOutRequest::where('warehouse_id', $delivery->from_warehouse_id)
            ->where('to_warehouse_id', $delivery->to_warehouse_id)
            ->whereIn('status', ['approved', 'dispatched'])
            ->latest()
            ->first()
            ?->update(['status' => 'dispatched']);

        // Notify warehouse users + admins that driver has arrived
        $driverName = $delivery->driver?->name ?? 'Driver';
        $fromName   = $delivery->fromWarehouse?->name ?? "Warehouse #{$delivery->from_warehouse_id}";
        AppNotification::notifyWarehouseUsers(
            $delivery->to_warehouse_id,
            'driver_arrived',
            'Driver Has Arrived',
            "{$driverName} has arrived with items from {$fromName}. Please verify and accept the delivery.",
            ['delivery_id' => $delivery->id, 'warehouse_id' => $delivery->to_warehouse_id]
        );

        return response()->json(
            $delivery->fresh()->load(['fromWarehouse', 'toWarehouse', 'items', 'driver'])
        );
    }

    /** GET /skycable/deliveries/incoming/{warehouseId} — arrived deliveries for a warehouse */
    public function incomingDeliveries(Request $request, int $warehouseId)
    {
        $deliveries = Delivery::with(['fromWarehouse', 'items', 'driver', 'dispatchedBy'])
            ->where('to_warehouse_id', $warehouseId)
            ->whereIn('status', ['arrived', 'in_transit'])
            ->latest()
            ->get();

        return response()->json($deliveries);
    }

    /** PUT /skycable/deliveries/{delivery}/accept */
    public function accept(Request $request, Delivery $delivery)
    {
        if (!in_array($delivery->status, ['in_transit', 'arrived'])) {
            return response()->json(['message' => 'Delivery must be in transit or arrived to accept.'], 422);
        }

        $receipt = DB::transaction(function () use ($request, $delivery) {
            $delivery->update([
                'status'      => 'accepted',
                'accepted_by' => $request->user()->id,
                'accepted_at' => now(),
                'arrived_at'  => now(),
            ]);

            $receipt = WarehouseReceipt::create([
                'warehouse_id' => $delivery->to_warehouse_id,
                'received_by'  => $request->user()->id,
                'receipt_date' => now()->toDateString(),
                'status'       => 'approved',
                'approved_by'  => $request->user()->id,
                'notes'        => "Transfer from WH#{$delivery->from_warehouse_id} via delivery #{$delivery->id}",
            ]);

            foreach ($delivery->items as $item) {
                WarehouseReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'item_type'  => $item->item_type,
                    'quantity'   => $item->quantity,
                    'unit'       => $item->unit,
                ]);

                // Add to destination stocks
                WarehouseStock::updateOrCreate(
                    ['warehouse_id' => $delivery->to_warehouse_id, 'item_type' => $item->item_type],
                    ['quantity' => DB::raw('quantity + ' . (float) $item->quantity), 'unit' => $item->unit]
                );

                // Deduct from source stocks
                WarehouseStock::where('warehouse_id', $delivery->from_warehouse_id)
                    ->where('item_type', $item->item_type)
                    ->decrement('quantity', (float) $item->quantity);
            }

            // Carry forward provenance: copy all teardown source tokens from source warehouse
            $sourceTokens = WarehouseReceiptSource::whereHas(
                'receipt',
                fn($q) => $q->where('warehouse_id', $delivery->from_warehouse_id)
                             ->where('status', 'approved')
            )->pluck('teardown_local_id')->unique();

            foreach ($sourceTokens as $localId) {
                WarehouseReceiptSource::firstOrCreate([
                    'receipt_id'        => $receipt->id,
                    'teardown_local_id' => $localId,
                ], ['via_delivery_id'  => $delivery->id]);
            }

            AuditLog::record('create', $receipt, null, $receipt->toArray());
            return $receipt;
        });

        // Notify driver + admins that delivery was accepted
        $toName = $delivery->fresh()->toWarehouse?->name ?? "Warehouse #{$delivery->to_warehouse_id}";
        if ($delivery->driver_id) {
            AppNotification::send(
                $delivery->driver_id,
                'delivery_accepted',
                'Delivery Accepted',
                "Your delivery has been accepted at {$toName}. Stocks have been updated.",
                ['delivery_id' => $delivery->id]
            );
        }
        AppNotification::notifyAdmins(
            'delivery_accepted',
            'Delivery Accepted',
            "Delivery #{$delivery->id} was accepted at {$toName}. Warehouse stocks updated.",
            ['delivery_id' => $delivery->id, 'receipt_id' => $receipt->id]
        );

        return response()->json([
            'delivery' => $delivery->fresh()->load(['fromWarehouse', 'toWarehouse', 'items']),
            'receipt'  => $receipt->load(['items', 'sources']),
        ]);
    }

    // ── Driver endpoints ──────────────────────────────────────────────────────

    /** GET /skycable/drivers — users with is_driver = true */
    public function drivers(Request $request)
    {
        $drivers = User::where('is_driver', true)
            ->select('id', 'first_name', 'last_name', 'email', 'is_driver')
            ->get()
            ->map(fn($u) => array_merge($u->toArray(), ['name' => $u->name]));

        return response()->json($drivers);
    }

    /** PUT /skycable/users/{user}/toggle-driver — toggle is_driver flag */
    public function toggleDriverRole(Request $request, User $user)
    {
        $user->update(['is_driver' => !$user->is_driver]);
        return response()->json(array_merge($user->fresh()->toArray(), ['name' => $user->fresh()->name]));
    }

    /** POST /skycable/deliveries/{delivery}/start — driver starts the delivery */
    public function startDelivery(Request $request, Delivery $delivery)
    {
        if ($delivery->status !== 'pending') {
            return response()->json(['message' => 'Delivery is not in pending state.'], 422);
        }

        $user = $request->user();
        if ($delivery->driver_id && $delivery->driver_id !== $user->id && !($user->is_admin ?? false)) {
            return response()->json(['message' => 'You are not assigned to this delivery.'], 403);
        }

        $delivery->update([
            'status'        => 'in_transit',
            'dispatched_at' => now(),
            'dispatched_by' => $user->id,
            'driver_id'     => $delivery->driver_id ?? $user->id,
        ]);

        // Mark pull-out as dispatched
        PullOutRequest::where('warehouse_id', $delivery->from_warehouse_id)
            ->where('status', 'approved')
            ->latest()
            ->first()
            ?->update(['status' => 'dispatched']);

        return response()->json(
            $delivery->fresh()->load(['fromWarehouse', 'toWarehouse', 'items', 'driver'])
        );
    }

    /** GET /skycable/driver/deliveries */
    public function driverDeliveries(Request $request)
    {
        $deliveries = Delivery::with(['fromWarehouse', 'toWarehouse', 'items', 'dispatchedBy'])
            ->where('driver_id', $request->user()->id)
            ->whereIn('status', ['pending', 'in_transit'])
            ->latest()
            ->get();

        return response()->json($deliveries);
    }

    /** PUT /skycable/deliveries/{delivery}/assign-driver */
    public function assignDriver(Request $request, Delivery $delivery)
    {
        $data = $request->validate(['driver_id' => 'required|exists:users,id']);
        $delivery->update(['driver_id' => $data['driver_id']]);
        return response()->json(
            $delivery->fresh()->load(['fromWarehouse', 'toWarehouse', 'items', 'driver'])
        );
    }

    /** GET /skycable/deliveries/{delivery}/tracking */
    public function tracking(Request $request, Delivery $delivery)
    {
        $delivery->load(['fromWarehouse', 'toWarehouse', 'items', 'driver', 'dispatchedBy']);

        $driverLocation = null;
        if ($delivery->driver_id) {
            $driverLocation = LinemanLocation::where('user_id', $delivery->driver_id)
                ->latest('pinged_at')
                ->first();
        }

        return response()->json([
            'delivery'        => $delivery,
            'driver_location' => $driverLocation,
        ]);
    }

    /** GET /skycable/pull-out-requests/{pullOutRequest}/delivery */
    public function pullOutDelivery(Request $request, PullOutRequest $pullOutRequest)
    {
        $delivery = Delivery::with(['fromWarehouse', 'toWarehouse', 'items', 'driver'])
            ->where('from_warehouse_id', $pullOutRequest->warehouse_id)
            ->whereIn('status', ['pending', 'in_transit', 'accepted'])
            ->latest()
            ->first();

        return response()->json($delivery);
    }

    // ── Legacy stubs ──────────────────────────────────────────────────────────

    public function pickupRequests(Request $request)      { return response()->json([]); }
    public function createPickupRequest(Request $request) { return response()->json(['message' => 'Use pull-out-requests.'], 422); }
}
