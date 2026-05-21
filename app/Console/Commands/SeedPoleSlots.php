<?php

namespace App\Console\Commands;

use App\Models\Pole;
use App\Models\PoleCableSlot;
use Illuminate\Console\Command;

class SeedPoleSlots extends Command
{
    protected $signature   = 'poles:seed-slots {--force : Re-seed even if slots already exist}';
    protected $description = 'Create standard cable slots (C1–C5, DA) for every pole that is missing them.';

    public function handle(): int
    {
        $poles = Pole::withTrashed()->get();
        $now   = now();
        $added = 0;

        foreach ($poles as $pole) {
            $existing = PoleCableSlot::where('pole_id', $pole->id)
                ->pluck('slot_label')
                ->all();

            foreach (Pole::STANDARD_SLOTS as $label) {
                if (in_array($label, $existing, true)) {
                    continue;
                }

                PoleCableSlot::create([
                    'pole_id'     => $pole->id,
                    'slot_label'  => $label,
                    'occupied_by' => 'free',
                    'status'      => 'free',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);

                $added++;
            }
        }

        $this->info("Done. Added {$added} slot(s) across {$poles->count()} pole(s).");

        return self::SUCCESS;
    }
}
