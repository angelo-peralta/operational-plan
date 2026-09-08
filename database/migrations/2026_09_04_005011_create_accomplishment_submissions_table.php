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
        Schema::create('accomplishment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accomplishment_id')
                ->constrained()
                ->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->unsignedInteger('evidence_count');
            $table->jsonb('snapshot');
            $table->timestamp('submitted_at')->useCurrent();

            $table->unique(['accomplishment_id', 'revision']);
            $table->index(['accomplishment_id', 'submitted_at']);
            $table->index(['to_status', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accomplishment_submissions');
    }
};
