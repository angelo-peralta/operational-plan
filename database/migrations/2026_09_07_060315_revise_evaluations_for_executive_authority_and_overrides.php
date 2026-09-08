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
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE evaluations DROP CONSTRAINT evaluations_decision_rating_check');
            DB::statement('ALTER TABLE evaluations DROP CONSTRAINT evaluations_required_remarks_check');
        }

        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropUnique('evaluations_accomplishment_submission_id_unique');
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('reviewer_id')
                ->constrained('organizational_units')
                ->restrictOnDelete();
            $table->boolean('is_override')->default(false)->after('remarks');
            $table->foreignId('overrides_evaluation_id')
                ->nullable()
                ->after('is_override')
                ->constrained('evaluations')
                ->restrictOnDelete();

            $table->unique(
                ['accomplishment_submission_id', 'reviewer_id'],
                'evaluations_submission_reviewer_unique',
            );
            $table->index(
                ['accomplishment_submission_id', 'reviewed_at'],
                'evaluations_submission_reviewed_index',
            );
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE evaluations
                ADD CONSTRAINT evaluations_decision_rating_check
                CHECK (
                    (decision = 'accepted' AND rating BETWEEN 0 AND 3)
                    OR (decision IN ('request_revision', 'request_additional_evidence') AND rating IS NULL)
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
            DB::statement(<<<'SQL'
                ALTER TABLE evaluations
                ADD CONSTRAINT evaluations_override_relationship_check
                CHECK (
                    (is_override = true AND overrides_evaluation_id IS NOT NULL)
                    OR (is_override = false AND overrides_evaluation_id IS NULL)
                )
                SQL);
            DB::statement(<<<'SQL'
                ALTER TABLE evaluations
                ADD CONSTRAINT evaluations_cannot_override_self_check
                CHECK (overrides_evaluation_id IS NULL OR overrides_evaluation_id <> id)
                SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE evaluations DROP CONSTRAINT evaluations_cannot_override_self_check');
            DB::statement('ALTER TABLE evaluations DROP CONSTRAINT evaluations_override_relationship_check');
            DB::statement('ALTER TABLE evaluations DROP CONSTRAINT evaluations_required_remarks_check');
            DB::statement('ALTER TABLE evaluations DROP CONSTRAINT evaluations_decision_rating_check');
        }

        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropIndex('evaluations_submission_reviewed_index');
            $table->dropUnique('evaluations_submission_reviewer_unique');
            $table->dropConstrainedForeignId('overrides_evaluation_id');
            $table->dropConstrainedForeignId('organizational_unit_id');
            $table->dropColumn('is_override');
            $table->unique('accomplishment_submission_id');
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
};
