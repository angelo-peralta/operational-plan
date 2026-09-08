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
        $universityId = DB::table('organizational_units')->where('code', 'SPUP')->value('id');
        $administrativeAreaId = DB::table('organizational_units')->where('code', 'ADMIN')->value('id');

        if (! is_numeric($universityId) || ! is_numeric($administrativeAreaId)) {
            return;
        }

        DB::table('organizational_units')
            ->whereIn('code', ['CPRINT', 'ICT', 'QA', 'HR'])
            ->where('parent_id', (int) $universityId)
            ->update([
                'parent_id' => (int) $administrativeAreaId,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $universityId = DB::table('organizational_units')->where('code', 'SPUP')->value('id');
        $administrativeAreaId = DB::table('organizational_units')->where('code', 'ADMIN')->value('id');

        if (! is_numeric($universityId) || ! is_numeric($administrativeAreaId)) {
            return;
        }

        DB::table('organizational_units')
            ->whereIn('code', ['CPRINT', 'ICT', 'QA', 'HR'])
            ->where('parent_id', (int) $administrativeAreaId)
            ->update([
                'parent_id' => (int) $universityId,
                'updated_at' => now(),
            ]);
    }
};
