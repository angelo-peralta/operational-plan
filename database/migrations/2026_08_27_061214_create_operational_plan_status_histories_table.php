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
        Schema::create('operational_plan_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_plan_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('remarks')->nullable();
            $table->jsonb('snapshot')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['operational_plan_id', 'created_at']);
            $table->index(['operational_plan_id', 'to_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_plan_status_histories');
    }
};
