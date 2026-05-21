<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkycableNode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'area_id', 'site_id', 'barangay_code', 'subcontractor_id', 'team_id',
        'name', 'label', 'full_label', 'status', 'report_type', 'data_source', 'source_file',
        'date_start', 'due_date', 'date_finished',
        'expected_cable', 'actual_cable', 'progress_percentage',
        'expected_nodes', 'expected_amplifier', 'expected_extender', 'expected_tsc',
        'expected_powersupply', 'expected_ps_housing',
        'actual_node', 'actual_amplifier', 'actual_extender', 'actual_tsc', 'actual_powersupply', 'actual_ps_housing',
        'region', 'province', 'city', 'barangay_name',
        'lat', 'lng',
    ];

    // Prevent NOT NULL constraint errors — numeric fields default to 0
    protected $attributes = [
        'expected_cable'      => 0,
        'actual_cable'        => 0,
        'progress_percentage' => 0,
        'expected_nodes'      => 0,
        'expected_amplifier'  => 0,
        'expected_extender'   => 0,
        'expected_tsc'        => 0,
        'expected_powersupply' => 0,
        'expected_ps_housing' => 0,
        'actual_node'         => 0,
        'actual_amplifier'    => 0,
        'actual_extender'     => 0,
        'actual_tsc'          => 0,
        'actual_powersupply'  => 0,
        'actual_ps_housing'   => 0,
    ];

    protected $casts = [
        'date_start'    => 'date',
        'due_date'      => 'date',
        'date_finished' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (SkycableNode $node) {
            // Auto-assign label (A, B, C...) if duplicate name in same barangay
            $count = static::where('name', $node->name)
                           ->where('barangay_code', $node->barangay_code)
                           ->whereNull('deleted_at')
                           ->count();

            if ($count > 0) {
                $node->label     = chr(65 + $count); // A=65, B=66...
                $node->full_label = $node->name . '-' . $node->label;
            } else {
                $node->full_label = $node->name;
            }
        });
    }

    public function area()        { return $this->belongsTo(SkycableArea::class, 'area_id'); }
    public function site()        { return $this->belongsTo(SkycableSite::class, 'site_id'); }
    public function barangay()    { return $this->belongsTo(PsgcBarangay::class, 'barangay_code', 'code'); }
    public function subcontractor() { return $this->belongsTo(Subcontractor::class); }
    public function team()        { return $this->belongsTo(Team::class); }
    public function skycablePoles() { return $this->hasMany(SkycablePole::class, 'node_id'); }
    public function spans()       { return $this->hasMany(SkycableSpan::class, 'node_id'); }
    public function dailyReports() { return $this->hasMany(SkycableDailyReport::class, 'node_id'); }
    public function latestDailyReport() { return $this->hasOne(SkycableDailyReport::class, 'node_id')->latestOfMany('report_date'); }
    public function spanSummaries() {
        return $this->hasMany(SkycableSpanSummary::class, 'node_id');
    }
}
