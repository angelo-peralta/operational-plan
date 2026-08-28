<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AcademicYearSelectionController;
use App\Http\Controllers\AccomplishmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\KeyResultAreaController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\OperationalPlanApprovalController;
use App\Http\Controllers\OperationalPlanCloseController;
use App\Http\Controllers\OperationalPlanController;
use App\Http\Controllers\OperationalPlanOfficialViewController;
use App\Http\Controllers\OperationalPlanReopenController;
use App\Http\Controllers\OperationalPlanReturnController;
use App\Http\Controllers\OperationalPlanSubmissionController;
use App\Http\Controllers\PlanItemController;
use App\Http\Controllers\ReportingPeriodController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('academic-years', [AcademicYearController::class, 'index'])->name('academic-years.index');
        Route::post('academic-years', [AcademicYearController::class, 'store'])->name('academic-years.store');
        Route::patch('academic-years/{academic_year}', [AcademicYearController::class, 'update'])->name('academic-years.update');
        Route::put('academic-years/{academic_year}/selection', AcademicYearSelectionController::class)->name('academic-years.select');
        Route::patch(
            'academic-years/{academic_year}/reporting-periods/{reporting_period}',
            [ReportingPeriodController::class, 'update'],
        )->name('academic-years.reporting-periods.update');

        Route::get('operational-plans', [OperationalPlanController::class, 'index'])
            ->name('operational-plans.index');
        Route::post('operational-plans', [OperationalPlanController::class, 'store'])
            ->name('operational-plans.store');
        Route::get('operational-plans/{operational_plan}', [OperationalPlanController::class, 'show'])
            ->name('operational-plans.show');
        Route::patch('operational-plans/{operational_plan}', [OperationalPlanController::class, 'update'])
            ->name('operational-plans.update');
        Route::get('operational-plans/{operational_plan}/official', OperationalPlanOfficialViewController::class)
            ->name('operational-plans.official');
        Route::post('operational-plans/{operational_plan}/submit', OperationalPlanSubmissionController::class)
            ->name('operational-plans.submit');
        Route::post('operational-plans/{operational_plan}/approve', OperationalPlanApprovalController::class)
            ->name('operational-plans.approve');
        Route::post('operational-plans/{operational_plan}/return', OperationalPlanReturnController::class)
            ->name('operational-plans.return');
        Route::post('operational-plans/{operational_plan}/close', OperationalPlanCloseController::class)
            ->name('operational-plans.close');
        Route::post('operational-plans/{operational_plan}/reopen', OperationalPlanReopenController::class)
            ->name('operational-plans.reopen');

        Route::post(
            'operational-plans/{operational_plan}/key-result-areas',
            [KeyResultAreaController::class, 'store'],
        )->name('operational-plans.key-result-areas.store');
        Route::patch(
            'operational-plans/{operational_plan}/key-result-areas/reorder',
            [KeyResultAreaController::class, 'reorder'],
        )->name('operational-plans.key-result-areas.reorder');
        Route::patch(
            'operational-plans/{operational_plan}/key-result-areas/{key_result_area}',
            [KeyResultAreaController::class, 'update'],
        )->name('operational-plans.key-result-areas.update');

        Route::post(
            'operational-plans/{operational_plan}/key-result-areas/{key_result_area}/plan-items',
            [PlanItemController::class, 'store'],
        )->name('operational-plans.key-result-areas.plan-items.store');
        Route::patch(
            'operational-plans/{operational_plan}/key-result-areas/{key_result_area}/plan-items/reorder',
            [PlanItemController::class, 'reorder'],
        )->name('operational-plans.key-result-areas.plan-items.reorder');
        Route::patch(
            'operational-plans/{operational_plan}/key-result-areas/{key_result_area}/plan-items/{plan_item}',
            [PlanItemController::class, 'update'],
        )->name('operational-plans.key-result-areas.plan-items.update');
        Route::delete(
            'operational-plans/{operational_plan}/key-result-areas/{key_result_area}/plan-items/{plan_item}',
            [PlanItemController::class, 'destroy'],
        )->name('operational-plans.key-result-areas.plan-items.destroy');

        Route::get('monitoring', MonitoringController::class)
            ->name('monitoring.index');
        Route::post(
            'monitoring/reporting-periods/{reporting_period}/plan-items/{plan_item}/accomplishments',
            [AccomplishmentController::class, 'store'],
        )->name('monitoring.accomplishments.store');
        Route::patch(
            'monitoring/reporting-periods/{reporting_period}/plan-items/{plan_item}/accomplishments/{accomplishment}',
            [AccomplishmentController::class, 'update'],
        )->name('monitoring.accomplishments.update');

        Route::get('administration/departments', [DepartmentController::class, 'index'])->name('administration.departments.index');
        Route::post('administration/departments', [DepartmentController::class, 'store'])->name('administration.departments.store');
        Route::patch('administration/departments/{department}', [DepartmentController::class, 'update'])->name('administration.departments.update');

        Route::get('administration/users', [UserController::class, 'index'])->name('administration.users.index');
        Route::post('administration/users', [UserController::class, 'store'])->name('administration.users.store');
        Route::patch('administration/users/{user}', [UserController::class, 'update'])->name('administration.users.update');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
