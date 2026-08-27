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
        Schema::create('plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_result_area_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->text('objective');
            $table->text('strategy')->nullable();
            $table->text('kpi_target_text');
            $table->decimal('target_value', 15, 4)->nullable();
            $table->string('target_unit', 100)->nullable();
            $table->string('target_operator', 50)->nullable();
            $table->string('target_frequency', 100)->nullable();
            $table->text('resources_needed')->nullable();
            $table->jsonb('documentary_evidence_requirements')->nullable();
            $table->jsonb('manual_co_accountable_units')->nullable();
            $table->unsignedInteger('sort_order');
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('updated_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['key_result_area_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_items');
    }
};
