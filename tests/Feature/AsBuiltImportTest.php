<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Skycable\NodeController;
use App\Models\Pole;
use App\Models\SkycableArea;
use App\Models\SkycableNode;
use App\Models\SkycablePole;
use App\Models\SkycableSpan;
use App\Models\SkycableSpanSummary;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AsBuiltImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.asbuilt_api_key' => 'test-asbuilt-key']);
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSchema();

        parent::tearDown();
    }

    public function test_duplicate_pt_npt_codes_are_kept_node_scoped(): void
    {
        $area = SkycableArea::create(['name' => 'NCR']);
        $nodeOne = SkycableNode::create(['area_id' => $area->id, 'name' => 'Node 1']);
        $nodeTwo = SkycableNode::create(['area_id' => $area->id, 'name' => 'Node 2']);

        $this->postJson('/api/v1/asbuilt/import', [
            'node_id' => $nodeOne->id,
            'poles' => [
                ['pole_code' => 'NPT', 'latitude' => 14.100001, 'longitude' => 121.100001],
                ['pole_code' => 'NPT', 'latitude' => 14.100002, 'longitude' => 121.100002],
                ['pole_code' => 'PT', 'latitude' => 14.100003, 'longitude' => 121.100003],
            ],
            'spans' => [],
        ], ['X-AsBuilt-Key' => 'test-asbuilt-key'])->assertCreated();

        $this->postJson('/api/v1/asbuilt/import', [
            'node_id' => $nodeTwo->id,
            'poles' => [
                ['pole_code' => 'NPT', 'latitude' => 14.200001, 'longitude' => 121.200001],
                ['pole_code' => 'PT', 'latitude' => 14.200002, 'longitude' => 121.200002],
            ],
            'spans' => [],
        ], ['X-AsBuilt-Key' => 'test-asbuilt-key'])->assertCreated();

        $nodeOnePoles = SkycablePole::with('pole')
            ->where('node_id', $nodeOne->id)
            ->orderBy('sequence')
            ->get();
        $nodeTwoPoles = SkycablePole::with('pole')
            ->where('node_id', $nodeTwo->id)
            ->orderBy('sequence')
            ->get();

        $this->assertCount(3, $nodeOnePoles);
        $this->assertCount(2, $nodeTwoPoles);
        $this->assertSame([14.100001, 14.100002, 14.100003], $nodeOnePoles->map(fn ($sp) => (float) $sp->pole->lat)->all());
        $this->assertSame([14.200001, 14.200002], $nodeTwoPoles->map(fn ($sp) => (float) $sp->pole->lat)->all());
        $this->assertSame(3, Pole::where('pole_code', 'NPT')->count());
        $this->assertSame(2, Pole::where('pole_code', 'PT')->count());
    }

    public function test_reimport_splits_a_legacy_shared_pole_without_moving_other_nodes(): void
    {
        $area = SkycableArea::create(['name' => 'NCR']);
        $nodeOne = SkycableNode::create(['area_id' => $area->id, 'name' => 'Node 1']);
        $nodeTwo = SkycableNode::create(['area_id' => $area->id, 'name' => 'Node 2']);
        $sharedPole = Pole::create(['pole_code' => 'NPT', 'lat' => 14.200001, 'lng' => 121.200001]);

        $nodeOnePole = SkycablePole::create(['node_id' => $nodeOne->id, 'pole_id' => $sharedPole->id, 'sequence' => 1]);
        $nodeTwoPole = SkycablePole::create(['node_id' => $nodeTwo->id, 'pole_id' => $sharedPole->id, 'sequence' => 1]);

        $this->postJson('/api/v1/asbuilt/import', [
            'node_id' => $nodeOne->id,
            'poles' => [
                ['pole_code' => 'NPT', 'latitude' => 14.100001, 'longitude' => 121.100001],
            ],
            'spans' => [],
        ], ['X-AsBuilt-Key' => 'test-asbuilt-key'])->assertCreated();

        $this->assertNotSame($sharedPole->id, $nodeOnePole->fresh()->pole_id);
        $this->assertSame($sharedPole->id, $nodeTwoPole->fresh()->pole_id);
        $this->assertSame(14.100001, (float) $nodeOnePole->fresh('pole')->pole->lat);
        $this->assertSame(14.200001, (float) $nodeTwoPole->fresh('pole')->pole->lat);
    }

    public function test_deleting_a_node_removes_its_spans_and_node_poles(): void
    {
        $area = SkycableArea::create(['name' => 'NCR']);
        $nodeOne = SkycableNode::create(['area_id' => $area->id, 'name' => 'Node 1']);
        $nodeTwo = SkycableNode::create(['area_id' => $area->id, 'name' => 'Node 2']);
        $ownPole = Pole::create(['pole_code' => 'NPT']);
        $sharedPole = Pole::create(['pole_code' => 'PT']);

        $nodeOneOwnPole = SkycablePole::create(['node_id' => $nodeOne->id, 'pole_id' => $ownPole->id, 'sequence' => 1]);
        $nodeOneSharedPole = SkycablePole::create(['node_id' => $nodeOne->id, 'pole_id' => $sharedPole->id, 'sequence' => 2]);
        $nodeTwoSharedPole = SkycablePole::create(['node_id' => $nodeTwo->id, 'pole_id' => $sharedPole->id, 'sequence' => 1]);

        $span = SkycableSpan::create([
            'node_id' => $nodeOne->id,
            'from_pole_id' => $nodeOneOwnPole->id,
            'to_pole_id' => $nodeOneSharedPole->id,
            'status' => 'pending',
        ]);

        SkycableSpanSummary::create([
            'span_id' => $span->id,
            'node_id' => $nodeOne->id,
        ]);

        (new NodeController)->destroy($nodeOne);

        $this->assertSame(0, SkycableSpan::withTrashed()->where('node_id', $nodeOne->id)->count());
        $this->assertSame(0, SkycableSpanSummary::where('node_id', $nodeOne->id)->count());
        $this->assertSame(0, SkycablePole::where('node_id', $nodeOne->id)->count());
        $this->assertTrue(Pole::withTrashed()->find($ownPole->id)->trashed());
        $this->assertFalse(Pole::withTrashed()->find($sharedPole->id)->trashed());
        $this->assertDatabaseHas('skycable_poles', ['id' => $nodeTwoSharedPole->id]);
    }

    private function createSchema(): void
    {
        $this->dropSchema();

        Schema::create('skycable_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('skycable_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_id');
            $table->string('barangay_code', 20)->nullable();
            $table->unsignedBigInteger('subcontractor_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->string('label')->nullable();
            $table->string('full_label')->nullable();
            $table->string('status')->default('pending');
            $table->string('report_type')->nullable();
            $table->string('data_source')->default('manual');
            $table->decimal('expected_cable', 12, 2)->default(0);
            $table->decimal('actual_cable', 12, 2)->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->integer('expected_nodes')->default(0);
            $table->integer('expected_amplifier')->default(0);
            $table->integer('expected_extender')->default(0);
            $table->integer('expected_tsc')->default(0);
            $table->integer('expected_powersupply')->default(0);
            $table->integer('expected_ps_housing')->default(0);
            $table->integer('actual_node')->default(0);
            $table->integer('actual_amplifier')->default(0);
            $table->integer('actual_extender')->default(0);
            $table->integer('actual_tsc')->default(0);
            $table->integer('actual_powersupply')->default(0);
            $table->integer('actual_ps_housing')->default(0);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('poles', function (Blueprint $table) {
            $table->id();
            $table->string('pole_code');
            $table->string('barangay_code', 20)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('skycable_status')->default('pending');
            $table->timestamp('skycable_cleared_at')->nullable();
            $table->string('globe_status')->default('pending');
            $table->timestamp('globe_cleared_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('pole_cable_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pole_id');
            $table->string('slot_label');
            $table->string('occupied_by')->default('free');
            $table->string('status')->default('free');
            $table->timestamps();
        });

        Schema::create('skycable_poles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('node_id');
            $table->unsignedBigInteger('pole_id');
            $table->integer('sequence')->default(0);
            $table->timestamp('date_start')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->string('status')->default('pending');
            $table->integer('duration')->nullable();
            $table->timestamps();
        });

        Schema::create('skycable_spans', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->nullable();
            $table->unsignedBigInteger('node_id');
            $table->unsignedBigInteger('from_pole_id');
            $table->unsignedBigInteger('to_pole_id');
            $table->string('span_code')->nullable();
            $table->decimal('length_meters', 10, 2)->default(0);
            $table->decimal('strand_length', 10, 2)->nullable();
            $table->integer('number_of_runs')->nullable();
            $table->decimal('actual_cable', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('skycable_span_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('span_id');
            $table->unsignedBigInteger('node_id');
            $table->decimal('expected_cable', 10, 2)->default(0);
            $table->unsignedInteger('expected_node')->default(0);
            $table->unsignedInteger('expected_amplifier')->default(0);
            $table->unsignedInteger('expected_extender')->default(0);
            $table->unsignedInteger('expected_tsc')->default(0);
            $table->unsignedInteger('expected_powersupply')->default(0);
            $table->unsignedInteger('expected_ps_housing')->default(0);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('company')->nullable();
            $table->string('action');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    private function dropSchema(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('skycable_span_summaries');
        Schema::dropIfExists('skycable_spans');
        Schema::dropIfExists('skycable_poles');
        Schema::dropIfExists('pole_cable_slots');
        Schema::dropIfExists('poles');
        Schema::dropIfExists('skycable_nodes');
        Schema::dropIfExists('skycable_areas');
    }
}
