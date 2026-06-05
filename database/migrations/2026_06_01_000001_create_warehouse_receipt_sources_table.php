<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provenance tracking for warehouse receipts.
 *
 * Links every warehouse receipt back to its original teardown report tokens,
 * regardless of how many warehouse-to-warehouse transfers happened in between.
 *
 * Flow:
 *   teardown_report (local_id)
 *     → warehouse_receipt + receipt_sources rows (direct field arrival)
 *     → delivery + delivery carries source rows
 *     → new warehouse_receipt + receipt_sources rows (copied from delivery)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_receipt_sources', function (Blueprint $table) {
            $table->id();

            // The warehouse receipt this token contributed to
            $table->foreignId('receipt_id')
                ->constrained('warehouse_receipts')
                ->cascadeOnDelete();

            // Original teardown report token (local_id from skycable_teardown_reports)
            $table->string('teardown_local_id', 64)->index();

            // Which delivery carried this token here (null = came directly from field)
            $table->foreignId('via_delivery_id')
                ->nullable()
                ->constrained('deliveries')
                ->nullOnDelete();

            // Which prior warehouse receipt this came from (null = came directly from field)
            $table->foreignId('from_receipt_id')
                ->nullable()
                ->constrained('warehouse_receipts')
                ->nullOnDelete();

            $table->timestamps();

            // A teardown token can only appear once per receipt
            $table->unique(['receipt_id', 'teardown_local_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_receipt_sources');
    }
};
