<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        DB::table('expense_categories')->insert([
            ['name' => 'إيجار', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'كهرباء', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ماء', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'رواتب', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'صيانة', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'إنترنت', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'نقل', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'أخرى', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
