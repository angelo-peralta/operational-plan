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
        Schema::create('accomplishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_item_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('reporting_period_id')
                ->constrained()
                ->restrictOnDelete();
            $table->decimal('reported_value', 15, 4)->nullable();
            $table->text('accomplishment_text')->nullable();
            $table->decimal('percentage_accomplished', 21, 4)->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('resubmitted_at')->nullable();
            $table->timestamps();

            $table->unique(['plan_item_id', 'reporting_period_id']);
            $table->index(['reporting_period_id', 'status']);
            $table->index(['plan_item_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accomplishments');
    }
};
