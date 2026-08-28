<?php

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\OperationalPlanSeeder;

test('seeds the ordered AY 2025-2026 CPRINT operational plan aggregate', function () {
    $this->seed([
        DepartmentSeeder::class,
        AcademicYearSeeder::class,
        DemoUserSeeder::class,
        OperationalPlanSeeder::class,
    ]);

    $academicYear = AcademicYear::query()
        ->where('start_year', 2025)
        ->where('end_year', 2026)
        ->firstOrFail();
    $department = Department::query()->where('code', 'CPRINT')->firstOrFail();
    $operationalPlan = OperationalPlan::query()
        ->whereBelongsTo($academicYear)
        ->whereBelongsTo($department)
        ->with('keyResultAreas.planItems.coAccountableDepartments')
        ->sole();
    $keyResultArea = $operationalPlan->keyResultAreas->sole();
    $planItems = $keyResultArea->planItems;
    $coAccountablePlanItem = $planItems->firstWhere('sort_order', 2);

    $this->assertDatabaseCount('operational_plans', 1);
    $this->assertDatabaseCount('key_result_areas', 1);
    $this->assertDatabaseCount('plan_items', 11);
    expect($operationalPlan)
        ->accountable_position->toBe('CPRINT Director')
        ->goal->toBe('To ensure the full integration of ethical AI and digital technologies in teaching, learning, research, and administrative processes through 100% adoption, capacity building, and efficient, timely, and high-quality reporting systems.');
    expect($keyResultArea)
        ->code->toBe('KRA 1')
        ->name->toBe('Quality Teaching, Learning, and Student Services')
        ->sort_order->toBe(1);
    expect($planItems->pluck('sort_order')->all())->toBe(range(1, 11));
    expect($coAccountablePlanItem)->not->toBeNull();
    expect($coAccountablePlanItem->coAccountableDepartments->pluck('code')->all())
        ->toBe(['ICT', 'QA']);
    expect($coAccountablePlanItem->manual_co_accountable_units)
        ->toBe(['VPA', 'CEARA']);
});

test('rerunning the operational plan seeder preserves existing aggregate edits', function () {
    $this->seed([
        DepartmentSeeder::class,
        AcademicYearSeeder::class,
        DemoUserSeeder::class,
        OperationalPlanSeeder::class,
    ]);

    $operationalPlan = OperationalPlan::query()->sole();
    $keyResultArea = $operationalPlan->keyResultAreas()->sole();
    $planItem = $keyResultArea->planItems()->where('sort_order', 1)->sole();
    $operationalPlan->update(['goal' => 'Administrator-maintained goal']);
    $keyResultArea->update(['name' => 'Administrator-maintained KRA name']);
    $planItem->update(['kpi_target_text' => 'Administrator-maintained KPI target']);

    $this->seed(OperationalPlanSeeder::class);

    $this->assertDatabaseCount('operational_plans', 1);
    $this->assertDatabaseCount('key_result_areas', 1);
    $this->assertDatabaseCount('plan_items', 11);
    $this->assertDatabaseCount('operational_plan_status_histories', 1);
    expect($operationalPlan->fresh()->goal)->toBe('Administrator-maintained goal');
    expect($keyResultArea->fresh()->name)->toBe('Administrator-maintained KRA name');
    expect($planItem->fresh()->kpi_target_text)->toBe('Administrator-maintained KPI target');
});
