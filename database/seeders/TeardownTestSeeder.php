<?php

namespace Database\Seeders;

use App\Models\Pole;
use App\Models\SkycablePole;
use App\Models\SkycableSpan;
use App\Models\SkycableSpanComponent;
use App\Models\SkycableTeardownReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TeardownTestSeeder extends Seeder
{
    // Makati Node (id=7), lineman id=4, team id=1
    private int $nodeId   = 7;
    private int $linemanId = 4;
    private int $teamId    = 1;

    public function run(): void
    {
        // 6 poles near Makati CBD with realistic GPS coords
        $poleDefs = [
            ['code' => 'MKT-P001', 'lat' => 14.5547, 'lng' => 121.0244],
            ['code' => 'MKT-P002', 'lat' => 14.5553, 'lng' => 121.0251],
            ['code' => 'MKT-P003', 'lat' => 14.5559, 'lng' => 121.0258],
            ['code' => 'MKT-P004', 'lat' => 14.5565, 'lng' => 121.0265],
            ['code' => 'MKT-P005', 'lat' => 14.5571, 'lng' => 121.0272],
            ['code' => 'MKT-P006', 'lat' => 14.5577, 'lng' => 121.0279],
        ];

        $skycablePoles = [];

        foreach ($poleDefs as $seq => $def) {
            $pole = Pole::firstOrCreate(
                ['pole_code' => $def['code']],
                [
                    'lat'             => $def['lat'],
                    'lng'             => $def['lng'],
                    'skycable_status' => 'cleared',
                    'globe_status'    => 'pending',
                ]
            );

            $sp = SkycablePole::firstOrCreate(
                ['node_id' => $this->nodeId, 'pole_id' => $pole->id],
                ['sequence' => $seq + 1]
            );

            $skycablePoles[] = $sp;
            $this->command->info("Pole: {$def['code']} (skycable_pole id={$sp->id})");
        }

        // 5 spans chained: P001-P002, P002-P003, P003-P004, P004-P005, P005-P006
        $spanDefs = [
            ['from' => 0, 'to' => 1, 'code' => 'MKT-SP-001', 'length' => 48.5,  'expected_cable' => 52.0,  'actual_cable' => 51.8],
            ['from' => 1, 'to' => 2, 'code' => 'MKT-SP-002', 'length' => 51.2,  'expected_cable' => 55.0,  'actual_cable' => 54.5],
            ['from' => 2, 'to' => 3, 'code' => 'MKT-SP-003', 'length' => 44.8,  'expected_cable' => 48.0,  'actual_cable' => 47.9],
            ['from' => 3, 'to' => 4, 'code' => 'MKT-SP-004', 'length' => 53.0,  'expected_cable' => 57.0,  'actual_cable' => 56.5],
            ['from' => 4, 'to' => 5, 'code' => 'MKT-SP-005', 'length' => 49.3,  'expected_cable' => 53.0,  'actual_cable' => 0],
        ];

        // Components layout per span: [type => [expected, actual, unit]]
        $componentLayouts = [
            [
                'node'       => [1, 1, 'pcs'],
                'amplifier'  => [2, 2, 'pcs'],
                'extender'   => [1, 1, 'pcs'],
                'tsc'        => [3, 3, 'pcs'],
                'cable'      => [52.0, 51.8, 'meters'],
                'powersupply'=> [1, 1, 'pcs'],
            ],
            [
                'node'       => [0, 0, 'pcs'],
                'amplifier'  => [3, 3, 'pcs'],
                'extender'   => [2, 2, 'pcs'],
                'tsc'        => [4, 4, 'pcs'],
                'cable'      => [55.0, 54.5, 'meters'],
                'powersupply'=> [1, 1, 'pcs'],
            ],
            [
                'node'       => [1, 1, 'pcs'],
                'amplifier'  => [1, 1, 'pcs'],
                'extender'   => [3, 3, 'pcs'],
                'tsc'        => [2, 2, 'pcs'],
                'cable'      => [48.0, 47.9, 'meters'],
                'powersupply'=> [0, 0, 'pcs'],
            ],
            [
                'node'       => [0, 0, 'pcs'],
                'amplifier'  => [2, 2, 'pcs'],
                'extender'   => [1, 1, 'pcs'],
                'tsc'        => [5, 5, 'pcs'],
                'cable'      => [57.0, 56.5, 'meters'],
                'powersupply'=> [1, 1, 'pcs'],
            ],
            [
                'node'       => [1, 0, 'pcs'],   // pending — not collected yet
                'amplifier'  => [2, 0, 'pcs'],
                'extender'   => [1, 0, 'pcs'],
                'tsc'        => [3, 0, 'pcs'],
                'cable'      => [53.0, 0, 'meters'],
                'powersupply'=> [1, 0, 'pcs'],
            ],
        ];

        $spans = [];
        foreach ($spanDefs as $i => $def) {
            $span = SkycableSpan::firstOrCreate(
                ['span_code' => $def['code']],
                [
                    'node_id'        => $this->nodeId,
                    'from_pole_id'   => $skycablePoles[$def['from']]->id,
                    'to_pole_id'     => $skycablePoles[$def['to']]->id,
                    'length_meters'  => $def['length'],
                    'actual_cable'   => $def['actual_cable'],
                    'status'         => $i < 4 ? 'completed' : 'in_progress',
                    'completed_at'   => $i < 4 ? Carbon::now()->subDays(4 - $i) : null,
                ]
            );

            foreach ($componentLayouts[$i] as $type => $vals) {
                SkycableSpanComponent::firstOrCreate(
                    ['span_id' => $span->id, 'component_type' => $type],
                    [
                        'expected_count' => $vals[0],
                        'actual_count'   => $vals[1],
                        'unit'           => $vals[2],
                    ]
                );
            }

            $spans[] = $span;
            $this->command->info("Span: {$def['code']} (id={$span->id}, status={$span->status})");
        }

        // Teardown reports for spans 0-4
        $reportDefs = [
            ['span' => 0, 'status' => 'backend_approved', 'days_ago' => 5, 'expected_cable' => 52.0,  'actual_cable' => 51.8],
            ['span' => 1, 'status' => 'backend_approved', 'days_ago' => 4, 'expected_cable' => 55.0,  'actual_cable' => 54.5],
            ['span' => 2, 'status' => 'subcon_approved',  'days_ago' => 3, 'expected_cable' => 48.0,  'actual_cable' => 47.9],
            ['span' => 3, 'status' => 'submitted',        'days_ago' => 2, 'expected_cable' => 57.0,  'actual_cable' => 56.5],
            ['span' => 4, 'status' => 'pending',          'days_ago' => 1, 'expected_cable' => 53.0,  'actual_cable' => 0],
        ];

        foreach ($reportDefs as $def) {
            $span    = $spans[$def['span']];
            $start   = Carbon::now()->subDays($def['days_ago'])->setTime(8, 0);
            $end     = $start->copy()->addMinutes(rand(60, 180));
            $duration = $start->diffInMinutes($end);

            $existing = SkycableTeardownReport::where('span_id', $span->id)->first();
            if ($existing) {
                $this->command->info("Report already exists for span {$span->span_code}, skipping.");
                continue;
            }

            $report = SkycableTeardownReport::create([
                'span_id'          => $span->id,
                'team_id'          => $this->teamId,
                'lineman_id'       => $this->linemanId,
                'start_time'       => $start,
                'end_time'         => $def['status'] !== 'pending' ? $end : null,
                'duration_minutes' => $def['status'] !== 'pending' ? $duration : null,
                'expected_cable'   => $def['expected_cable'],
                'actual_cable'     => $def['actual_cable'],
                'status'           => $def['status'],
                'notes'            => "Test teardown for {$span->span_code}",
                'offline_mode'     => false,
                'captured_lat'     => $skycablePoles[$def['span']]->pole->lat ?? null,
                'captured_lng'     => $skycablePoles[$def['span']]->pole->lng ?? null,
            ]);

            $this->command->info("Report: {$span->span_code} → status={$def['status']} (id={$report->id})");
        }

        $this->command->info('TeardownTestSeeder done.');
    }
}
