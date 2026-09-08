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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('entra_tenant_id')->nullable()->after('email_verified_at');
            $table->uuid('entra_object_id')->nullable()->after('entra_tenant_id');

            $table->unique(['entra_tenant_id', 'entra_object_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['entra_tenant_id', 'entra_object_id']);
            $table->dropColumn(['entra_tenant_id', 'entra_object_id']);
        });
    }
};
