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
        Schema::table('operational_plans', function (Blueprint $table) {
            $table->renameColumn('department_id', 'organizational_unit_id');
        });

        Schema::table('plan_item_co_accountables', function (Blueprint $table) {
            $table->renameColumn('department_id', 'organizational_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_item_co_accountables', function (Blueprint $table) {
            $table->renameColumn('organizational_unit_id', 'department_id');
        });

        Schema::table('operational_plans', function (Blueprint $table) {
            $table->renameColumn('organizational_unit_id', 'department_id');
        });
    }
};
