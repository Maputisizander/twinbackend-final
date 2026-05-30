<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Concerns\StoresPhotos;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    use StoresPhotos;
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
            'warehouse_id'     => 'required|exists:warehouses,id',
            'subcontractor_id' => 'nullable|exists:subcontractors,id',
            'node_id'          => 'nullable|exists:skycable_nodes,id',
            'receipt_date'     => 'required|date',
            'items'            => 'required|array|min:1',
            'items.*.item_type' => 'required|string',
            'items.*.quantity'  => 'required|numeric|min:0.01',
            'items.*.unit'      => 'nullable|string',
            'submitted_lat'    => 'nullable|numeric|between:-90,90',
            'submitted_lng'    => 'nullable|numeric|between:-180,180',
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

            AuditLog::record('create', $receipt, null, $receipt->toArray());
            return $receipt;
        });

        return response()->json($receipt->load(['items', 'receivedBy']), 201);
    }

    public function showReceipt(WarehouseReceipt $receipt)
    {
        return response()->json($receipt->load(['items', 'receivedBy', 'approvedBy', 'node', 'warehouse']));
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

        $old = $receipt->toArray();
        $receipt->status = 'arrived';
        $receipt->save();
        AuditLog::record('update', $receipt, $old, $receipt->fresh()->toArray());

        return response()->json($receipt->fresh()->load(['items', 'receivedBy', 'node', 'warehouse']));
    }

    /**
     * Warehouse in-charge verifies actual quantities, optionally adjusts them,
     * attaches a proof image, then approves the receipt and increments stock.
     * Status: arrived → approved
     *
     * POST /skycable/warehouse-receipts/{receipt}/verify
     * multipart/form-data: items[0][item_type], items[0][quantity], proof_image (required)
     */
    public function verifyAndApprove(Request $request, WarehouseReceipt $receipt)
    {
        if ($receipt->status !== 'arrived') {
            return response()->json(['message' => 'Receipt must be marked as arrived before approving.'], 422);
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

            // Store proof image
            $proofPath = null;
            if ($request->hasFile('proof_image')) {
                $proofPath = $this->storePhoto($request->file('proof_image'), 'warehouse-proofs');
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

        return response()->json($receipt->fresh()->load(['items', 'receivedBy', 'approvedBy']));
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
