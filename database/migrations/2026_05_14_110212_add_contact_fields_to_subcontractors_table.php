<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subcontractors', function (Blueprint $table) {
            $table->renameColumn('contact_person', 'contact_name');
            $table->renameColumn('contact_number', 'contact_phone');
            $table->string('contact_email')->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('subcontractors', function (Blueprint $table) {
            $table->renameColumn('contact_name', 'contact_person');
            $table->renameColumn('contact_phone', 'contact_number');
            $table->dropColumn('contact_email');
        });
    }
};
