<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 36)->index();
            $table->string('syncable_type');
            $table->unsignedBigInteger('syncable_id');
            $table->json('local_data');
            $table->json('server_data');
            $table->enum('resolution', ['pending', 'server_wins', 'local_wins', 'merged'])->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['syncable_type', 'syncable_id']);
            $table->index('resolution');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
    }
};
