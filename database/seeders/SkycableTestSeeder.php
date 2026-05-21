<?php

namespace Database\Seeders;

use App\Models\SkycableArea;
use App\Models\SkycableNode;
use Illuminate\Database\Seeder;

class SkycableTestSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'North Luzon'  => ['Ilocos Norte Node', 'Cagayan Valley Node', 'Pangasinan Node'],
            'South Luzon'  => ['Laguna Node', 'Batangas Node', 'Quezon Node'],
            'Metro Manila' => ['Makati Node', 'Quezon City Node', 'Manila Node', 'Pasig Node'],
            'Visayas'      => ['Cebu Node', 'Iloilo Node', 'Bacolod Node'],
            'Mindanao'     => ['Davao Node', 'Cagayan de Oro Node', 'Zamboanga Node'],
        ];

        $seq = 1;
        foreach ($areas as $areaName => $nodeNames) {
            $area = SkycableArea::firstOrCreate(['name' => $areaName]);

            foreach ($nodeNames as $i => $nodeName) {
                $code = strtoupper(substr(preg_replace('/[^A-Z]/i', '', $areaName), 0, 3))
                      . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);

                SkycableNode::firstOrCreate(
                    ['name' => $nodeName, 'area_id' => $area->id],
                    [
                        'full_label'    => $code,
                        'status'        => 'pending',
                        'barangay_code' => null,
                    ]
                );
                $seq++;
            }

            $this->command->info("Area seeded: {$areaName} (" . count($nodeNames) . " nodes)");
        }
    }
}
