<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accomplishment_submission_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('decision');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reviewer_id', 'reviewed_at']);
            $table->index(['decision', 'reviewed_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE evaluations
                ADD CONSTRAINT evaluations_decision_rating_check
                CHECK (
                    (decision = 'accepted' AND rating BETWEEN 0 AND 3)
                    OR (decision = 'request_additional_evidence' AND rating IS NULL)
                    OR (decision = 'rejected' AND (rating IS NULL OR rating BETWEEN 0 AND 3))
                )
                SQL);
            DB::statement(<<<'SQL'
                ALTER TABLE evaluations
                ADD CONSTRAINT evaluations_required_remarks_check
                CHECK (
                    (decision = 'accepted' AND rating IN (2, 3))
                    OR (remarks IS NOT NULL AND length(btrim(remarks)) > 0)
                )
                SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
