<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcontractor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['subcon', 'staging', 'main'])->default('subcon');
            $table->text('address')->nullable();
            $table->decimal('sqm', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['node', 'amplifier', 'extender', 'tsc', 'cable', 'powersupply']);
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->timestamps();

            $table->unique(['warehouse_id', 'item_type']);
        });

        Schema::create('warehouse_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcontractor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('node_id')->nullable()->constrained('skycable_nodes')->nullOnDelete();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('receipt_date');
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('warehouse_receipts')->cascadeOnDelete();
            $table->enum('item_type', ['node', 'amplifier', 'extender', 'tsc', 'cable', 'powersupply']);
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->timestamps();
        });

        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'dispatched'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('dispatched_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->enum('status', ['pending', 'in_transit', 'arrived', 'accepted', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['node', 'amplifier', 'extender', 'tsc', 'cable', 'powersupply']);
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->timestamps();
        });

        Schema::create('pull_out_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->enum('purpose', ['for_sale', 'for_delivery']);
            $table->foreignId('declared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('destination')->nullable();
            $table->foreignId('arrival_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('arrival_confirmed_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'dispatched', 'delivered'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pull_out_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pull_out_request_id')->constrained('pull_out_requests')->cascadeOnDelete();
            $table->enum('item_type', ['node', 'amplifier', 'extender', 'tsc', 'cable', 'powersupply']);
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pull_out_items');
        Schema::dropIfExists('pull_out_requests');
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('pickup_requests');
        Schema::dropIfExists('warehouse_receipt_items');
        Schema::dropIfExists('warehouse_receipts');
        Schema::dropIfExists('warehouse_stocks');
        Schema::dropIfExists('warehouses');
    }
};
