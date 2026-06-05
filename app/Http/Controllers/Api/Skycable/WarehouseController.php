<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Concerns\StoresPhotos;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LinemanLocation;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptSource;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    use StoresPhotos;

    private const ARRIVAL_RADIUS_METERS = 150;
    public function index(Request $request)
    {
        $user = $request->user();

        // Non-admin users are scoped to their own subcontractor's warehouses.
        // Admins/telcovantage can pass ?subcontractor_id= to filter, or see all.
        $subconId = $user->subcontractor_id
            ?? ($request->subcontractor_id ?: null);

        $query = Warehouse::with(['subcontractor', 'stocks'])
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($subconId, fn ($q) => $q->where('subcontractor_id', $subconId));

        return response()->json($query->get());
    }

    public function show(Warehouse $warehouse)
    {
        return response()->json($warehouse->load(['subcontractor', 'stocks', 'receipts.items']));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'sqm'    => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
            'lat'    => 'sometimes|nullable|numeric|between:-90,90',
            'lng'    => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        $old = $warehouse->toArray();
        $warehouse->update($data);
        AuditLog::record('update', $warehouse, $old, $warehouse->toArray());

        return response()->json($warehouse);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'subcontractor_id' => 'required|exists:subcontractors,id',
            'type'             => 'nullable|string|max:100',
            'sqm'              => 'nullable|numeric|min:0',
            'status'           => 'sometimes|in:active,inactive',
            'lat'              => 'nullable|numeric|between:-90,90',
            'lng'              => 'nullable|numeric|between:-180,180',
        ]);

        $warehouse = Warehouse::create(array_merge(['status' => 'active'], $data));
        AuditLog::record('create', $warehouse, null, $warehouse->toArray());

        return response()->json($warehouse->load(['subcontractor', 'stocks']), 201);
    }

    public function receiveStock(Request $request)
    {
        $data = $request->validate([
            'warehouse_id'          => 'required|exists:warehouses,id',
            'subcontractor_id'      => 'nullable|exists:subcontractors,id',
            'node_id'               => 'nullable|exists:skycable_nodes,id',
            'receipt_date'          => 'required|date',
            'items'                 => 'required|array|min:1',
            'items.*.item_type'     => 'required|string',
            'items.*.quantity'      => 'required|numeric|min:0.01',
            'items.*.unit'          => 'nullable|string',
            'submitted_lat'         => 'nullable|numeric|between:-90,90',
            'submitted_lng'         => 'nullable|numeric|between:-180,180',
            'teardown_local_ids'    => 'nullable|array',
            'teardown_local_ids.*'  => 'string|max:64',
        ]);

        $receipt = DB::transaction(function () use ($request, $data) {
            $receipt = WarehouseReceipt::create([
                'warehouse_id'     => $data['warehouse_id'],
                'subcontractor_id' => $data['subcontractor_id'] ?? null,
                'node_id'          => $data['node_id'] ?? null,
                'received_by'      => $request->user()->id,
                'receipt_date'     => $data['receipt_date'],
                'status'           => 'pending',
                'submitted_lat'    => $data['submitted_lat'] ?? null,
                'submitted_lng'    => $data['submitted_lng'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                WarehouseReceiptItem::create(array_merge($item, ['receipt_id' => $receipt->id]));
            }

            // Store provenance — link original teardown tokens to this receipt
            foreach (array_unique($data['teardown_local_ids'] ?? []) as $localId) {
                if ($localId) {
                    WarehouseReceiptSource::firstOrCreate([
                        'receipt_id'        => $receipt->id,
                        'teardown_local_id' => $localId,
                    ]);
                }
            }

            AuditLog::record('create', $receipt, null, $receipt->toArray());
            return $receipt;
        });

        return response()->json($receipt->load(['items', 'receivedBy']), 201);
    }

    public function showReceipt(WarehouseReceipt $receipt)
    {
        return response()->json($this->withLiveLocation(
            $receipt->load(['items', 'receivedBy', 'approvedBy', 'node', 'warehouse'])
        ));
    }

    public function approveReceipt(Request $request, WarehouseReceipt $receipt)
    {
        $request->validate(['action' => 'required|in:approve,reject']);

        $old = $receipt->toArray();

        if ($request->action === 'approve') {
            DB::transaction(function () use ($request, $receipt) {
                $receipt->update([
                    'status'      => 'approved',
                    'approved_by' => $request->user()->id,
                ]);

                foreach ($receipt->items as $item) {
                    WarehouseStock::updateOrCreate(
                        ['warehouse_id' => $receipt->warehouse_id, 'item_type' => $item->item_type],
                        ['quantity' => DB::raw('quantity + ' . $item->quantity), 'unit' => $item->unit]
                    );
                }
            });
        } else {
            $receipt->update(['status' => 'rejected', 'approved_by' => $request->user()->id]);
        }

        AuditLog::record('update', $receipt, $old, $receipt->fresh()->toArray());

        return response()->json($receipt->fresh()->load(['items', 'receivedBy', 'approvedBy', 'node']));
    }

    /**
     * Field staff or warehouse in-charge marks a pending receipt as arrived.
     * Status: pending → arrived
     *
     * PUT /skycable/warehouse-receipts/{receipt}/arrive
     */
    public function arrive(WarehouseReceipt $receipt)
    {
        if ($receipt->status !== 'pending') {
            return response()->json(['message' => 'Receipt is not in pending status.'], 422);
        }

        if (! $this->receiptLinemanIsInsideWarehouseZone($receipt)) {
            return response()->json(['message' => 'Lineman must be inside the warehouse arrival zone before this receipt can be marked as arrived.'], 422);
        }

        $old = $receipt->toArray();
        $receipt->status = 'arrived';
        $receipt->save();
        AuditLog::record('update', $receipt, $old, $receipt->fresh()->toArray());

        return response()->json($this->withLiveLocation(
            $receipt->fresh()->load(['items', 'receivedBy', 'node', 'warehouse'])
        ));
    }

    /**
     * Warehouse in-charge starts physical unloading.
     * Status: arrived → unloading
     *
     * PUT /skycable/warehouse-receipts/{receipt}/start-unload
     */
    public function startUnload(WarehouseReceipt $receipt)
    {
        if ($receipt->status !== 'arrived') {
            return response()->json(['message' => 'Receipt must be arrived before unloading.'], 422);
        }

        $old = $receipt->toArray();
        $receipt->status = 'unloading';
        $receipt->save();
        AuditLog::record('update', $receipt, $old, $receipt->fresh()->toArray());

        return response()->json($this->withLiveLocation(
            $receipt->fresh()->load(['items', 'receivedBy', 'node', 'warehouse'])
        ));
    }

    /**
     * Warehouse in-charge verifies actual quantities, optionally adjusts them,
     * attaches a proof image, then approves the receipt and increments stock.
     * Status: unloading → approved
     *
     * POST /skycable/warehouse-receipts/{receipt}/verify
     * multipart/form-data: items[0][item_type], items[0][quantity], proof_image (required)
     */
    public function verifyAndApprove(Request $request, WarehouseReceipt $receipt)
    {
        if (! in_array($receipt->status, ['arrived', 'unloading'], true)) {
            return response()->json(['message' => 'Receipt must be unloading before approving.'], 422);
        }

        $request->validate([
            'items'               => 'nullable|array',
            'items.*.item_type'   => 'required_with:items|string',
            'items.*.quantity'    => 'required_with:items|numeric|min:0',
            'proof_image'         => 'required|file|mimes:jpg,jpeg,png|max:15360',
            'notes'               => 'nullable|string|max:500',
        ]);

        $old = $receipt->toArray();

        DB::transaction(function () use ($request, $receipt) {
            // Update item quantities if the warehouse adjusted them
            if ($request->has('items')) {
                foreach ($request->items as $adj) {
                    WarehouseReceiptItem::where('receipt_id', $receipt->id)
                        ->where('item_type', $adj['item_type'])
                        ->update(['quantity' => $adj['quantity']]);
                }
            }

            // Store proof image under warehouse-proof/{node-slug}/{receipt-id}/{user-id}.jpg
            $proofPath = null;
            if ($request->hasFile('proof_image')) {
                $receipt->loadMissing('node');
                $nodeSlug   = Str::slug($receipt->node?->name ?? 'unknown');
                $customPath = "warehouse-proof/{$nodeSlug}/{$receipt->id}/{$request->user()->id}.jpg";
                $proofPath  = $this->storePhoto($request->file('proof_image'), 'warehouse-proof', 1280, $customPath);
            }

            $receipt->update([
                'status'      => 'approved',
                'approved_by' => $request->user()->id,
                'notes'       => $request->input('notes', $receipt->notes),
                'proof_image' => $proofPath ?? $receipt->proof_image,
            ]);

            // Refresh items (possibly updated) then increment stock
            foreach ($receipt->fresh()->items as $item) {
                WarehouseStock::updateOrCreate(
                    ['warehouse_id' => $receipt->warehouse_id, 'item_type' => $item->item_type],
                    ['quantity' => DB::raw('quantity + ' . (float) $item->quantity), 'unit' => $item->unit]
                );
            }
        });

        AuditLog::record('update', $receipt, $old, $receipt->fresh()->toArray());

        return response()->json($this->withLiveLocation(
            $receipt->fresh()->load(['items', 'receivedBy', 'approvedBy'])
        ));
    }

    private function withLiveLocation(WarehouseReceipt $receipt): WarehouseReceipt
    {
        $location = $receipt->received_by
            ? LinemanLocation::where('user_id', $receipt->received_by)->first()
            : null;

        $receipt->setAttribute('live_location', $location ? [
            'lat'       => (float) $location->latitude,
            'lng'       => (float) $location->longitude,
            'accuracy'  => $location->accuracy !== null ? (float) $location->accuracy : null,
            'pinged_at' => $location->pinged_at?->toIso8601String(),
        ] : null);

        return $receipt;
    }

    private function receiptLinemanIsInsideWarehouseZone(WarehouseReceipt $receipt): bool
    {
        if (! $receipt->received_by) {
            return false;
        }

        $receipt->loadMissing('warehouse');
        $warehouse = $receipt->warehouse;
        if (! $warehouse || $warehouse->lat === null || $warehouse->lng === null) {
            return false;
        }

        $location = LinemanLocation::where('user_id', $receipt->received_by)->first();
        if (! $location) {
            return false;
        }

        return $this->distanceMeters(
            (float) $location->latitude,
            (float) $location->longitude,
            (float) $warehouse->lat,
            (float) $warehouse->lng
        ) <= self::ARRIVAL_RADIUS_METERS;
    }

    private function distanceMeters(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadiusM = 6371000;
        $dLat = deg2rad($toLat - $fromLat);
        $dLng = deg2rad($toLng - $fromLng);
        $lat1 = deg2rad($fromLat);
        $lat2 = deg2rad($toLat);

        $a = sin($dLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return $earthRadiusM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function stocks(Warehouse $warehouse)
    {
        return response()->json($warehouse->stocks);
    }

    public function receipts(Request $request, Warehouse $warehouse)
    {
        $query = $warehouse->receipts()->with(['items', 'receivedBy', 'node'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        return response()->json($query->latest()->paginate(30));
    }
}
