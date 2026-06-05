<?php

namespace App\Providers;

use App\Models\Pole;
use App\Models\PoleCableSlot;
use App\Models\PoleReport;
use App\Models\PoleTeardownImage;
use App\Models\SkycableArea;
use App\Models\SkycableDailyReport;
use App\Models\SkycableNode;
use App\Models\SkycablePole;
use App\Models\SkycablePoleTeardownLog;
use App\Models\SkycableSite;
use App\Models\SkycableSpan;
use App\Models\SkycableSpanComponent;
use App\Models\SkycableSpanSummary;
use App\Models\SkycableTeardownPhoto;
use App\Models\SkycableTeardownReport;
use App\Models\SkycableTeardownReportSlot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->autoSelectCacheDriver();
        $this->registerSkycableCacheInvalidation();
    }

    /**
     * Auto-detect best available cache driver.
     *
     * Priority: redis → database → file
     *
     * If CACHE_STORE=redis but Redis is unreachable (e.g. Hostinger shared
     * hosting), silently downgrade to database so the app never crashes.
     */
    private function autoSelectCacheDriver(): void
    {
        if (config('cache.default') !== 'redis') return;

        try {
            \Illuminate\Support\Facades\Redis::connection('cache')->ping();
        } catch (\Throwable) {
            // Redis unavailable — downgrade to database cache automatically
            config(['cache.default' => 'database']);
        }
    }

    private function registerSkycableCacheInvalidation(): void
    {
        foreach ($this->skycableCacheModels() as $modelClass) {
            $modelClass::saved(fn (Model $model) => $this->bumpSkycableCacheVersion());
            $modelClass::deleted(fn (Model $model) => $this->bumpSkycableCacheVersion());
        }
    }

    private function bumpSkycableCacheVersion(): void
    {
        Cache::forever('skycable:data-version', ((int) Cache::get('skycable:data-version', 1)) + 1);
    }

    private function skycableCacheModels(): array
    {
        return [
            SkycableArea::class,
            SkycableSite::class,
            SkycableNode::class,
            SkycablePole::class,
            SkycableSpan::class,
            SkycableSpanSummary::class,
            SkycableSpanComponent::class,
            SkycableTeardownReport::class,
            SkycableTeardownReportSlot::class,
            SkycableTeardownPhoto::class,
            SkycablePoleTeardownLog::class,
            SkycableDailyReport::class,
            Pole::class,
            PoleCableSlot::class,
            PoleReport::class,
            PoleTeardownImage::class,
        ];
    }
}
