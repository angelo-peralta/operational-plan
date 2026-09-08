<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('key_result_area_templates')->insertOrIgnore([
            'code' => 'KRA 1',
            'name' => 'Quality Teaching, Learning, and Student Services',
            'description' => null,
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('key_result_area_templates')
            ->where('code', 'KRA 1')
            ->where('name', 'Quality Teaching, Learning, and Student Services')
            ->delete();
    }
};
