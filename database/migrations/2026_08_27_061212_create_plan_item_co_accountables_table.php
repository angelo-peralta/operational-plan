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
        Schema::create('plan_item_co_accountables', function (Blueprint $table) {
            $table->foreignId('plan_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('department_id')
                ->constrained()
                ->restrictOnDelete();

            $table->primary(['plan_item_id', 'department_id']);
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_item_co_accountables');
    }
};
