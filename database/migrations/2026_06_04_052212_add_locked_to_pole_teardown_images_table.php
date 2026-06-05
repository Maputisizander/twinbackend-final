<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pole_teardown_images', function (Blueprint $table) {
            // Locked by backend team — prevents lineman from retaking the before photo
            $table->boolean('locked')->default(false)->after('inventory_type');
            $table->unsignedBigInteger('locked_by')->nullable()->after('locked');
            $table->timestamp('locked_at')->nullable()->after('locked_by');
        });
    }

    public function down(): void
    {
        Schema::table('pole_teardown_images', function (Blueprint $table) {
            $table->dropColumn(['locked', 'locked_by', 'locked_at']);
        });
    }
};
