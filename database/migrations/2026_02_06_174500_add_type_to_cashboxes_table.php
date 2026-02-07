<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cashboxes', 'type')) {
            Schema::table('cashboxes', function (Blueprint $table) {
                $table->string('type', 20)->default('cash')->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cashboxes', 'type')) {
            Schema::table('cashboxes', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
