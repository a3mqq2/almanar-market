<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 36)->unique();
            $table->string('device_name');
            $table->string('license_key', 64);
            $table->string('api_token', 64)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->enum('status', ['active', 'suspended', 'revoked'])->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index('license_key');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_registrations');
    }
};
