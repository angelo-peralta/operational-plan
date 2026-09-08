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
        Schema::table('operational_plan_status_histories', function (Blueprint $table) {
            $table->string('action', 30)->nullable()->after('to_status');
            $table->string('actor_role', 50)->nullable()->after('action');
            $table->json('actor_organizational_unit_ids')->nullable()->after('actor_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operational_plan_status_histories', function (Blueprint $table) {
            $table->dropColumn(['action', 'actor_role', 'actor_organizational_unit_ids']);
        });
    }
};
