<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_driver')->default(false)->after('can_approve_delivery');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('driver_id')
                ->nullable()
                ->after('dispatched_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_driver');
        });
    }
};
