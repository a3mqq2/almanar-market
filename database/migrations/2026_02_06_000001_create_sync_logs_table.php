<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 36)->index();
            $table->string('syncable_type');
            $table->unsignedBigInteger('syncable_id');
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->json('payload');
            $table->timestamp('local_timestamp');
            $table->timestamp('synced_at')->nullable();
            $table->unsignedBigInteger('server_id')->nullable();
            $table->enum('sync_status', ['pending', 'synced', 'conflict', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamps();

            $table->index(['syncable_type', 'syncable_id']);
            $table->index(['sync_status', 'device_id']);
            $table->index('local_timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
