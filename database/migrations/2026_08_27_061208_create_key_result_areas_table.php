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
        Schema::create('key_result_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_plan_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order');
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('updated_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['operational_plan_id', 'sort_order']);
            $table->index(['operational_plan_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_result_areas');
    }
};
