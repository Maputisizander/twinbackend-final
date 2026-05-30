<?php
use App\Models\User;
use App\Models\Subcontractor;
use App\Models\Warehouse;
use App\Models\SkycableArea;
use App\Models\SkycableNode;
use App\Models\Pole;
use App\Models\SkycablePole;
use App\Models\SkycableSpan;
use App\Models\SkycableTeardownReport;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use Illuminate\Support\Facades\DB;

$sub = Subcontractor::firstOrCreate(
    ['name' => 'Vantage Field Crew'],
    ['contact_person' => 'Mark Laurence', 'contact_number' => '09170000001']
);
echo "Subcontractor: {$sub->id} {$sub->name}\n";

$warehouse = Warehouse::where('subcontractor_id', $sub->id)->first();
if (!$warehouse) {
    $warehouse = Warehouse::create([
        'subcontractor_id' => $sub->id,
        'name' => 'Vantage Main Warehouse',
        'location' => 'BGC, Taguig',
    ]);
}
echo "Warehouse: {$warehouse->id} {$warehouse->name}\n";

$team = DB::table('teams')->where('subcontractor_id', $sub->id)->first();
if (!$team) {
    $teamId = DB::table('teams')->insertGetId([
        'subcontractor_id' => $sub->id,
        'name' => 'Vantage Team Alpha',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $team = DB::table('teams')->find($teamId);
}
echo "Team: {$team->id} {$team->name}\n";

$lineman = User::firstOrCreate(
    ['email' => 'lineman.rarar@telcovantage.com'],
    [
        'first_name' => 'Juan',
        'last_name'  => 'dela Cruz',
        'password' => bcrypt('password'),
        'role' => 'lineman',
        'team_id' => $team->id,
        'subcontractor_id' => $sub->id,
    ]
);
echo "Lineman: {$lineman->id} {$lineman->first_name} {$lineman->last_name}\n";

$area = SkycableArea::firstOrCreate(
    ['name' => 'Taguig Cluster'],
    ['description' => 'BGC and surrounding areas']
);
echo "Area: {$area->id} {$area->name}\n";

// skycable_area_teams table not present — skipping

$today     = now()->setTimezone('Asia/Manila')->toDateString();
$yesterday = now()->setTimezone('Asia/Manila')->subDay()->toDateString();

function makeNode(string $nodeName, string $prefix, int $areaId, int $spanCount = 3): array {
    $node = SkycableNode::firstOrCreate(
        ['name' => $nodeName],
        ['area_id' => $areaId, 'status' => 'active']
    );
    echo "  Node: {$node->id} {$node->name}\n";

    $poleIds = [];
    for ($i = 1; $i <= $spanCount + 1; $i++) {
        $code = "{$prefix}-P" . str_pad($i, 3, '0', STR_PAD_LEFT);
        $pole = Pole::firstOrCreate(
            ['pole_code' => $code],
            [
                'lat'             => 14.5 + (rand(-100, 100) / 10000),
                'lng'             => 121.0 + (rand(-100, 100) / 10000),
                'skycable_status' => 'in_progress',
            ]
        );
        SkycablePole::firstOrCreate(
            ['node_id' => $node->id, 'pole_id' => $pole->id],
            ['sequence' => $i]
        );
        $poleIds[] = $pole->id;
    }

    $spans = [];
    for ($i = 0; $i < $spanCount; $i++) {
        $code = "{$prefix}-S" . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
        $span = SkycableSpan::firstOrCreate(
            ['span_code' => $code],
            [
                'node_id'      => $node->id,
                'from_pole_id' => $poleIds[$i],
                'to_pole_id'   => $poleIds[$i + 1],
                'status'       => 'in_progress',
            ]
        );
        $spans[] = $span;
    }

    return [$node, $spans];
}

function makeTeardownReports(array $spans, int $teamId, int $linemanId, string $date): array {
    $reports = [];
    foreach ($spans as $span) {
        $report = SkycableTeardownReport::firstOrCreate(
            ['span_id' => $span->id, 'created_at' => $date],
            [
                'span_id'                => $span->id,
                'team_id'               => $teamId,
                'lineman_id'            => $linemanId,
                'start_time'            => $date . ' 08:00:00',
                'end_time'              => $date . ' 11:00:00',
                'actual_cable'          => rand(50, 200),
                'nodes_collected'       => rand(1, 3),
                'amplifiers_collected'  => rand(0, 2),
                'extenders_collected'   => rand(0, 2),
                'tsc_collected'         => rand(0, 1),
                'powersupply_collected' => rand(0, 1),
                'ps_housing_collected'  => 0,
                'status'               => 'submitted',
                'created_at'           => $date . ' 12:00:00',
                'updated_at'           => $date . ' 12:00:00',
            ]
        );
        $reports[] = $report;
        echo "    TeardownReport: {$report->id} span={$span->id} date={$date}\n";
    }
    return $reports;
}

echo "\n--- Creating BGC Node ---\n";
[$bgcNode, $bgcSpans] = makeNode('BGC', 'BGC', $area->id, 3);

echo "\n--- Creating Demo Mark Node ---\n";
[$demoNode, $demoSpans] = makeNode('Demo Mark', 'DMK', $area->id, 2);

echo "\n--- Creating RARAR Node ---\n";
[$rararNode, $rararSpans] = makeNode('RARAR', 'RAR', $area->id, 4);

echo "\n--- RARAR: creating teardown reports ---\n";
makeTeardownReports($rararSpans, $team->id, $lineman->id, $today);
makeTeardownReports(array_slice($rararSpans, 0, 2), $team->id, $lineman->id, $yesterday);

$admin = User::where('email', 'admin@telcovantage.com')->first();

$rararReceiptYest = WarehouseReceipt::firstOrCreate(
    ['node_id' => $rararNode->id, 'receipt_date' => $yesterday],
    [
        'warehouse_id' => $warehouse->id,
        'node_id'      => $rararNode->id,
        'received_by'  => $lineman->id,
        'approved_by'  => $admin?->id,
        'receipt_date' => $yesterday,
        'status'       => 'approved',
        'notes'        => 'All items verified and added to stock.',
        'created_at'   => $yesterday . ' 14:00:00',
        'updated_at'   => $yesterday . ' 16:00:00',
    ]
);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rararReceiptYest->id, 'item_type' => 'cable'],     ['quantity' => 320, 'unit' => 'm']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rararReceiptYest->id, 'item_type' => 'node'],      ['quantity' => 4,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rararReceiptYest->id, 'item_type' => 'amplifier'], ['quantity' => 2,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rararReceiptYest->id, 'item_type' => 'extender'],  ['quantity' => 1,   'unit' => 'pc']);
echo "RARAR yesterday receipt: {$rararReceiptYest->id} status={$rararReceiptYest->status}\n";

$rararReceiptToday = WarehouseReceipt::firstOrCreate(
    ['node_id' => $rararNode->id, 'receipt_date' => $today],
    [
        'warehouse_id' => $warehouse->id,
        'node_id'      => $rararNode->id,
        'received_by'  => $lineman->id,
        'approved_by'  => $admin?->id,
        'receipt_date' => $today,
        'status'       => 'approved',
        'notes'        => 'Fast-tracked approval for RARAR.',
        'created_at'   => $today . ' 13:00:00',
        'updated_at'   => $today . ' 15:00:00',
    ]
);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rararReceiptToday->id, 'item_type' => 'cable'],     ['quantity' => 480, 'unit' => 'm']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rararReceiptToday->id, 'item_type' => 'node'],      ['quantity' => 6,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $rararReceiptToday->id, 'item_type' => 'amplifier'], ['quantity' => 3,   'unit' => 'pc']);
echo "RARAR today receipt: {$rararReceiptToday->id} status={$rararReceiptToday->status}\n";

foreach ([['cable', 800], ['node', 10], ['amplifier', 5], ['extender', 1]] as [$type, $qty]) {
    DB::table('warehouse_stocks')->updateOrInsert(
        ['warehouse_id' => $warehouse->id, 'item_type' => $type],
        ['quantity' => $qty, 'updated_at' => now()]
    );
}
echo "Warehouse stocks updated\n";

echo "\n--- BGC: creating teardown reports ---\n";
makeTeardownReports($bgcSpans, $team->id, $lineman->id, $today);

$bgcReceipt = WarehouseReceipt::firstOrCreate(
    ['node_id' => $bgcNode->id, 'receipt_date' => $today],
    [
        'warehouse_id' => $warehouse->id,
        'node_id'      => $bgcNode->id,
        'received_by'  => $lineman->id,
        'approved_by'  => null,
        'receipt_date' => $today,
        'status'       => 'pending',
        'notes'        => null,
        'created_at'   => $today . ' 10:30:00',
        'updated_at'   => $today . ' 10:30:00',
    ]
);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $bgcReceipt->id, 'item_type' => 'cable'],     ['quantity' => 150, 'unit' => 'm']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $bgcReceipt->id, 'item_type' => 'node'],      ['quantity' => 3,   'unit' => 'pc']);
WarehouseReceiptItem::firstOrCreate(['receipt_id' => $bgcReceipt->id, 'item_type' => 'amplifier'], ['quantity' => 1,   'unit' => 'pc']);
echo "BGC receipt: {$bgcReceipt->id} status={$bgcReceipt->status}\n";

echo "\n--- Demo Mark: creating teardown reports (no receipt) ---\n";
makeTeardownReports($demoSpans, $team->id, $lineman->id, $today);
echo "Demo Mark has NO receipt — shows as Unsubmitted\n";

echo "\n========================================\n";
echo "DONE!\n";
echo "  RARAR  → 2 receipts APPROVED (In Stock)\n";
echo "  BGC    → 1 receipt PENDING (Awaiting Approval)\n";
echo "  Demo Mark → no receipt (Unsubmitted)\n";
echo "========================================\n";
