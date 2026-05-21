<?php

namespace App\Http\Controllers\Api\Skycable;

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
    public function index(Request $request)
    {
        $query = Warehouse::with(['subcontractor', 'stocks'])
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->subcontractor_id, fn ($q) => $q->where('subcontractor_id', $request->subcontractor_id));

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
        ]);

        $old = $warehouse->toArray();
        $warehouse->update($data);
        AuditLog::record('update', $warehouse, $old, $warehouse->toArray());

        return response()->json($warehouse);
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
        ]);

        $receipt = DB::transaction(function () use ($request, $data) {
            $receipt = WarehouseReceipt::create([
                'warehouse_id'     => $data['warehouse_id'],
                'subcontractor_id' => $data['subcontractor_id'] ?? null,
                'node_id'          => $data['node_id'] ?? null,
                'received_by'      => $request->user()->id,
                'receipt_date'     => $data['receipt_date'],
                'status'           => 'pending',
            ]);

            foreach ($data['items'] as $item) {
                WarehouseReceiptItem::create(array_merge($item, ['receipt_id' => $receipt->id]));
            }

            AuditLog::record('create', $receipt, null, $receipt->toArray());
            return $receipt;
        });

        return response()->json($receipt->load('items'), 201);
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

        return response()->json($receipt->fresh());
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
