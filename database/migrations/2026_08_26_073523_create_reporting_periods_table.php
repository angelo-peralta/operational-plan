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
        Schema::create('reporting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedSmallInteger('sequence');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();

            $table->unique(['academic_year_id', 'code']);
            $table->unique(['academic_year_id', 'sequence']);
            $table->index(['academic_year_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporting_periods');
    }
};
