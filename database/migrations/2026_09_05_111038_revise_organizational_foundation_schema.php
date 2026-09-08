<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('departments', 'organizational_units');
        Schema::table('organizational_units', function (Blueprint $table): void {
            $table->foreignId('parent_id')->nullable()->index()->constrained('organizational_units')->restrictOnDelete();
            $table->string('acronym')->nullable();
            $table->string('type')->default('office')->index();
            $table->unsignedInteger('sort_order')->default(0);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->string('legacy_role')->nullable();
            $table->foreignId('legacy_role_id')->nullable()->constrained('roles')->restrictOnDelete();
            $table->string('role')->default('staff')->change();
        });
        Schema::table('roles', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->index();
        });
        Schema::create('organizational_unit_user_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('organizational_unit_id')->constrained()->restrictOnDelete();
            $table->string('assignment_type');
            $table->string('position_title')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'organizational_unit_id'], 'unit_assignments_user_unit_unique');
            $table->index(['user_id', 'is_active'], 'unit_assignments_user_active_index');
            $table->index('organizational_unit_id', 'unit_assignments_unit_index');
        });
        DB::statement('CREATE UNIQUE INDEX unit_assignments_active_primary_unique ON organizational_unit_user_assignments (user_id) WHERE is_active = true AND is_primary = true');
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE organizational_units ADD CONSTRAINT organizational_units_not_self_parent CHECK (parent_id IS NULL OR parent_id <> id)');
            DB::statement("ALTER TABLE organizational_unit_user_assignments ADD CONSTRAINT unit_assignments_type_check CHECK (assignment_type IN ('executive', 'head', 'staff'))");
            DB::statement('ALTER TABLE organizational_unit_user_assignments ADD CONSTRAINT unit_assignments_dates_check CHECK (starts_on IS NULL OR ends_on IS NULL OR starts_on <= ends_on)');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Organizational foundation preserves live assignments and history; use a forward migration to correct it.');
    }
};
