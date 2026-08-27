<?php

use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('reporting period permissions match the system role matrix', function () {
    $reportingPeriod = ReportingPeriod::factory()->create();
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $departmentUser = User::factory()->departmentUser()->create();

    expect([
        'administrator.viewAny' => Gate::forUser($administrator)->allows('viewAny', ReportingPeriod::class),
        'reviewer.viewAny' => Gate::forUser($reviewer)->allows('viewAny', ReportingPeriod::class),
        'departmentUser.viewAny' => Gate::forUser($departmentUser)->allows('viewAny', ReportingPeriod::class),
        'administrator.view' => Gate::forUser($administrator)->allows('view', $reportingPeriod),
        'reviewer.view' => Gate::forUser($reviewer)->allows('view', $reportingPeriod),
        'departmentUser.view' => Gate::forUser($departmentUser)->allows('view', $reportingPeriod),
        'administrator.create' => Gate::forUser($administrator)->allows('create', ReportingPeriod::class),
        'reviewer.create' => Gate::forUser($reviewer)->allows('create', ReportingPeriod::class),
        'departmentUser.create' => Gate::forUser($departmentUser)->allows('create', ReportingPeriod::class),
        'administrator.update' => Gate::forUser($administrator)->allows('update', $reportingPeriod),
        'reviewer.update' => Gate::forUser($reviewer)->allows('update', $reportingPeriod),
        'departmentUser.update' => Gate::forUser($departmentUser)->allows('update', $reportingPeriod),
        'administrator.delete' => Gate::forUser($administrator)->allows('delete', $reportingPeriod),
        'reviewer.delete' => Gate::forUser($reviewer)->allows('delete', $reportingPeriod),
        'departmentUser.delete' => Gate::forUser($departmentUser)->allows('delete', $reportingPeriod),
    ])->toBe([
        'administrator.viewAny' => true,
        'reviewer.viewAny' => true,
        'departmentUser.viewAny' => true,
        'administrator.view' => true,
        'reviewer.view' => true,
        'departmentUser.view' => true,
        'administrator.create' => true,
        'reviewer.create' => false,
        'departmentUser.create' => false,
        'administrator.update' => true,
        'reviewer.update' => false,
        'departmentUser.update' => false,
        'administrator.delete' => false,
        'reviewer.delete' => false,
        'departmentUser.delete' => false,
    ]);
});
