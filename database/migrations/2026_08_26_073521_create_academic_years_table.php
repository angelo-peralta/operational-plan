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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('start_year');
            $table->unsignedSmallInteger('end_year');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();

            $table->unique(['start_year', 'end_year']);
        });

        match (Schema::getConnection()->getDriverName()) {
            'pgsql' => DB::statement(
                'CREATE UNIQUE INDEX academic_years_one_current_unique ON academic_years (is_current) WHERE is_current = true'
            ),
            'sqlite' => DB::statement(
                'CREATE UNIQUE INDEX academic_years_one_current_unique ON academic_years (is_current) WHERE is_current = 1'
            ),
            default => null,
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
