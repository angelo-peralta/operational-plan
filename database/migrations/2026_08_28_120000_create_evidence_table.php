<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accomplishment_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('evidence_type')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('original_filename');
            $table->string('stored_path')->unique();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['accomplishment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};
