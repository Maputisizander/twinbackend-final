<?php

use App\Models\User;
use App\Models\SkycableNode;
use App\Models\SkycableSpan;
use App\Models\SkycableTeardownReport;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use Illuminate\Support\Facades\DB;

// ── Config ───────────────────────────────────────────────────────────────────
$MARK_ID      = 2;   // marklaurence.tomenio@telcovantage.com
$TEAM_ID      = 1;   // REYMOND TEAM'S
$SUB_ID       = 1;   // MRTEL
$WAREHOUSE_ID = 1;   // MRTEL Warehouse
$APPROVER_ID  = 2;   // Mark approves his own for demo purposes

$today     = now()->setTimezone('Asia/Manila')->toDateString();
$yesterday = now()->setTimezone('Asia/Manila')->subDay()->toDateString();
$twoDaysAgo= now()->setTimezone('Asia/Manila')->subDays(2)->toDateString();

// Make sure Mark has a subcontractor + team so he appears correctly
User::where('id', $MARK_ID)->update([
    'subcontractor_id' => $SUB_ID,
    'team_id'          => $TEAM_ID,
    'role'             => 'lineman',
]);
echo "Mark Laurence (id={$MARK_ID}) updated → sub={$SUB_ID} team={$TEAM_ID}\n\n";

// ── Helper ───────────────────────────────────────────────────────────────────
function teardown(int $spanId, int $teamId, int $linemanId, string $date, string $status, ?int $approverId): SkycableTeardownReport
{
    return SkycableTeardownReport::firstOrCreate(
        ['span_id' => $spanId, 'lineman_id' => $linemanId, 'status' => $status],
        [
            'team_id'               => $teamId,
            'start_time'            => $date . ' 07:30:00',
            'end_time'              => $date . ' 11:00:00',
            'expected_cable'        => rand(80, 250),
            'actual_cable'          => rand(70, 230),
            'nodes_collected'       => rand(1, 4),
            'amplifiers_collected'  => rand(0, 3),
            'extenders_collected'   => rand(0, 2),
            'tsc_collected'         => rand(0, 1),
            'powersupply_collected' => rand(0, 1),
            'ps_housing_collected'  => 0,
            'status'                => $status,
            'offline_mode'          => false,
            'backend_approved_by'   => in_array($status, ['backend_approved']) ? $approverId : null,
            'backend_approved_at'   => in_array($status, ['backend_approved']) ? $date . ' 15:00:00' : null,
            'subcon_reviewed_by'    => in_array($status, ['subcon_approved', 'backend_approved']) ? $approverId : null,
            'subcon_reviewed_at'    => in_array($status, ['subcon_approved', 'backend_approved']) ? $date . ' 13:00:00' : null,
            'created_at'            => $date . ' 12:00:00',
            'updated_at'            => $date . ' 16:00:00',
        ]
    );
}

function receipt(int $warehouseId, int $nodeId, int $subId, int $receivedBy, ?int $approvedBy, string $date, string $status, string $notes): WarehouseReceipt
{
    return WarehouseReceipt::firstOrCreate(
        ['node_id' => $nodeId, 'receipt_date' => $date, 'received_by' => $receivedBy],
        [
            'warehouse_id'   => $warehouseId,
            'subcontractor_id' => $subId,
            'approved_by'    => $approvedBy,
            'approved_at'    => $approvedBy ? $date . ' 16:00:00' : null,
            'status'         => $status,
            'notes'          => $notes,
            'created_at'     => $date . ' 12:30:00',
            'updated_at'     => $date . ' 16:30:00',
        ]
    );
}

// ── Node 1: DEMO MARK — spans 1-7 (skip 84,85 already used) ─────────────────
echo "=== Node 1: DEMO MARK ===\n";
$demoSpans = SkycableSpan::where('node_id', 1)
    ->whereNotIn('id', [84, 85])
    ->take(5)->pluck('id')->toArray();

echo "Spans: " . implode(', ', $demoSpans) . "\n";

// 2 days ago — backend_approved (fully done)
$r1 = teardown($demoSpans[0], $TEAM_ID, $MARK_ID, $twoDaysAgo, 'backend_approved', $APPROVER_ID);
echo "  [backend_approved] TD#{$r1->id} span={$demoSpans[0]} date={$twoDaysAgo}\n";

$r2 = teardown($demoSpans[1], $TEAM_ID, $MARK_ID, $twoDaysAgo, 'backend_approved', $APPROVER_ID);
echo "  [backend_approved] TD#{$r2->id} span={$demoSpans[1]} date={$twoDaysAgo}\n";

// Yesterday — subcon_approved (waiting backend)
$r3 = teardown($demoSpans[2], $TEAM_ID, $MARK_ID, $yesterday, 'subcon_approved', $APPROVER_ID);
echo "  [subcon_approved]  TD#{$r3->id} span={$demoSpans[2]} date={$yesterday}\n";

// Today — submitted (fresh submission)
$r4 = teardown($demoSpans[3], $TEAM_ID, $MARK_ID, $today, 'submitted', null);
echo "  [submitted]        TD#{$r4->id} span={$demoSpans[3]} date={$today}\n";

$r5 = teardown($demoSpans[4], $TEAM_ID, $MARK_ID, $today, 'submitted', null);
echo "  [submitted]        TD#{$r5->id} span={$demoSpans[4]} date={$today}\n";

// For Delivery receipt — 2 days ago (approved, items already in warehouse)
$rec1 = receipt($WAREHOUSE_ID, 1, $SUB_ID, $MARK_ID, $APPROVER_ID, $twoDaysAgo, 'approved',
    'Collected from DEMO MARK node — 2 spans cleared. All items counted and verified.');
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec1->id, 'item_type' => 'cable'],     ['quantity' => 340, 'unit' => 'm']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec1->id, 'item_type' => 'node'],      ['quantity' => 5,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec1->id, 'item_type' => 'amplifier'], ['quantity' => 4,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec1->id, 'item_type' => 'extender'],  ['quantity' => 2,   'unit' => 'pc']);
echo "  [receipt APPROVED] #{$rec1->id} date={$twoDaysAgo}\n";

// For Delivery receipt — yesterday (pending, subcon submitted, admin reviewing)
$rec2 = receipt($WAREHOUSE_ID, 1, $SUB_ID, $MARK_ID, null, $yesterday, 'pending',
    'DEMO MARK span 3 materials. Awaiting admin approval.');
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec2->id, 'item_type' => 'cable'],     ['quantity' => 190, 'unit' => 'm']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec2->id, 'item_type' => 'node'],      ['quantity' => 2,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec2->id, 'item_type' => 'amplifier'], ['quantity' => 1,   'unit' => 'pc']);
echo "  [receipt PENDING]  #{$rec2->id} date={$yesterday}\n\n";

// ── Node 2: Sampaguita St. ───────────────────────────────────────────────────
echo "=== Node 2: Sampaguita St. ===\n";
$sampSpans = SkycableSpan::where('node_id', 2)->take(4)->pluck('id')->toArray();
echo "Spans: " . implode(', ', $sampSpans) . "\n";

// 2 days ago — backend_approved
$s1 = teardown($sampSpans[0], $TEAM_ID, $MARK_ID, $twoDaysAgo, 'backend_approved', $APPROVER_ID);
echo "  [backend_approved] TD#{$s1->id} span={$sampSpans[0]} date={$twoDaysAgo}\n";

$s2 = teardown($sampSpans[1], $TEAM_ID, $MARK_ID, $twoDaysAgo, 'backend_approved', $APPROVER_ID);
echo "  [backend_approved] TD#{$s2->id} span={$sampSpans[1]} date={$twoDaysAgo}\n";

// Yesterday — submitted
$s3 = teardown($sampSpans[2], $TEAM_ID, $MARK_ID, $yesterday, 'submitted', null);
echo "  [submitted]        TD#{$s3->id} span={$sampSpans[2]} date={$yesterday}\n";

$s4 = teardown($sampSpans[3], $TEAM_ID, $MARK_ID, $today, 'submitted', null);
echo "  [submitted]        TD#{$s4->id} span={$sampSpans[3]} date={$today}\n";

// For Delivery — Sampaguita approved
$rec3 = receipt($WAREHOUSE_ID, 2, $SUB_ID, $MARK_ID, $APPROVER_ID, $twoDaysAgo, 'approved',
    'Sampaguita St. node — 2 spans cleared. Cable and passive equipment received.');
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec3->id, 'item_type' => 'cable'],     ['quantity' => 520, 'unit' => 'm']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec3->id, 'item_type' => 'node'],      ['quantity' => 6,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec3->id, 'item_type' => 'amplifier'], ['quantity' => 3,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec3->id, 'item_type' => 'extender'],  ['quantity' => 1,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec3->id, 'item_type' => 'tsc'],       ['quantity' => 1,   'unit' => 'pc']);
echo "  [receipt APPROVED] #{$rec3->id} date={$twoDaysAgo}\n";

// For Delivery — today pending (just submitted by Mark)
$rec4 = receipt($WAREHOUSE_ID, 2, $SUB_ID, $MARK_ID, null, $today, 'pending',
    'Sampaguita span 3 & 4 materials. Submitted today by Mark Laurence.');
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec4->id, 'item_type' => 'cable'],     ['quantity' => 280, 'unit' => 'm']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec4->id, 'item_type' => 'node'],      ['quantity' => 3,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rec4->id, 'item_type' => 'powersupply'], ['quantity' => 1, 'unit' => 'pc']);
echo "  [receipt PENDING]  #{$rec4->id} date={$today}\n\n";

// ── Warehouse stock totals ────────────────────────────────────────────────────
foreach ([['cable', 1330], ['node', 16], ['amplifier', 8], ['extender', 3], ['tsc', 1], ['powersupply', 1]] as [$type, $qty]) {
    DB::table('warehouse_stocks')->updateOrInsert(
        ['warehouse_id' => $WAREHOUSE_ID, 'item_type' => $type],
        ['quantity' => $qty, 'updated_at' => now()]
    );
}
echo "Warehouse stocks updated.\n\n";

// ── Summary ──────────────────────────────────────────────────────────────────
echo "========================================\n";
echo "DONE — Mark Laurence Teardown Data\n";
echo "----------------------------------------\n";
$total  = SkycableTeardownReport::where('lineman_id', $MARK_ID)->count();
$done   = SkycableTeardownReport::where('lineman_id', $MARK_ID)->where('status', 'backend_approved')->count();
$sub    = SkycableTeardownReport::where('lineman_id', $MARK_ID)->where('status', 'submitted')->count();
$sc     = SkycableTeardownReport::where('lineman_id', $MARK_ID)->where('status', 'subcon_approved')->count();
$rAppr  = WarehouseReceipt::where('received_by', $MARK_ID)->where('status', 'approved')->count();
$rPend  = WarehouseReceipt::where('received_by', $MARK_ID)->where('status', 'pending')->count();
echo "Teardowns total  : {$total}\n";
echo "  backend_approved : {$done}\n";
echo "  subcon_approved  : {$sc}\n";
echo "  submitted        : {$sub}\n";
echo "Receipts approved : {$rAppr}\n";
echo "Receipts pending  : {$rPend}\n";
echo "========================================\n";
