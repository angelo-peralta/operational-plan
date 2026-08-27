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
        Schema::create('operational_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('department_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('accountable_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('accountable_name')->nullable();
            $table->string('accountable_position')->nullable();
            $table->text('goal');
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('updated_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('returned_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'department_id']);
            $table->index(['academic_year_id', 'status']);
            $table->index(['department_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_plans');
    }
};
