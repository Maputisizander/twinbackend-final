<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('company', ['telcovantage', 'skycable', 'globe', 'meralco']);
            $table->enum('role', ['admin', 'executive', 'project_manager', 'back_office', 'subcon_pm', 'lineman']);
            $table->json('project_access')->nullable();
            $table->unsignedBigInteger('subcontractor_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('cellphone')->nullable();
            $table->text('address')->nullable();
            $table->string('profile_photo')->nullable();

            $table->decimal('current_gps_lat', 10, 7)->nullable();
            $table->decimal('current_gps_lng', 10, 7)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_login')->nullable();

            $table->enum('status', ['active', 'inactive', 'on_hold'])->default('active');
            $table->boolean('password_reset_required')->default(false);
            $table->timestamp('temp_password_set_at')->nullable();

            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
