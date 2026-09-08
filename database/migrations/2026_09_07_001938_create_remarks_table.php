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
        Schema::create('remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('operational_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('remarkable_type', 50);
            $table->unsignedBigInteger('remarkable_id');
            $table->string('field_key', 100)->nullable();
            $table->foreignId('reporting_period_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('category', 50);
            $table->text('content');
            $table->string('status', 30)->default('open');
            $table->foreignId('addressed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('addressed_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['remarkable_type', 'remarkable_id']);
            $table->index(['operational_plan_id', 'status']);
            $table->index(['academic_year_id', 'author_id']);
            $table->index(['field_key', 'reporting_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remarks');
    }
};
