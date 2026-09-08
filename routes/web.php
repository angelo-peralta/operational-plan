<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AcademicYearSelectionController;
use App\Http\Controllers\AccomplishmentController;
use App\Http\Controllers\AccomplishmentEvaluationController;
use App\Http\Controllers\AccomplishmentSubmissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EvaluationQueueController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\KeyResultAreaController;
use App\Http\Controllers\KeyResultAreaManagementController;
use App\Http\Controllers\KeyResultAreaTemplateController;
use App\Http\Controllers\MicrosoftEntraController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\OperationalPlanApprovalController;
use App\Http\Controllers\OperationalPlanCloseController;
use App\Http\Controllers\OperationalPlanController;
use App\Http\Controllers\OperationalPlanOfficialViewController;
use App\Http\Controllers\OperationalPlanReopenController;
use App\Http\Controllers\OperationalPlanReturnController;
use App\Http\Controllers\OperationalPlanSubmissionController;
use App\Http\Controllers\OrganizationalUnitController;
use App\Http\Controllers\PlanItemController;
use App\Http\Controllers\RemarkController;
use App\Http\Controllers\ReportingPeriodController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('auth/microsoft/redirect', [MicrosoftEntraController::class, 'redirect'])
        ->name('auth.microsoft.redirect');
    Route::get('auth/microsoft/callback', [MicrosoftEntraController::class, 'callback'])
        ->name('auth.microsoft.callback');
});

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
        Route::get('key-results', KeyResultAreaManagementController::class)
            ->name('key-results.index');
        Route::post('key-results', [KeyResultAreaTemplateController::class, 'store'])
            ->name('key-results.store');
        Route::patch('key-results/{key_result_area_template}', [KeyResultAreaTemplateController::class, 'update'])
            ->name('key-results.update');
        Route::delete('key-results/{key_result_area_template}', [KeyResultAreaTemplateController::class, 'destroy'])
            ->name('key-results.destroy');
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
        Route::post('operational-plans/{operational_plan}/remarks', [RemarkController::class, 'store'])
            ->name('operational-plans.remarks.store');
        Route::patch('operational-plans/{operational_plan}/remarks/{remark}', [RemarkController::class, 'update'])
            ->name('operational-plans.remarks.update');

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
        Route::delete(
            'operational-plans/{operational_plan}/key-result-areas/{key_result_area}',
            [KeyResultAreaController::class, 'destroy'],
        )->name('operational-plans.key-result-areas.destroy');

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
        Route::get('evaluations', EvaluationQueueController::class)
            ->name('evaluations.index');
        Route::get('reports', ReportsController::class)
            ->name('reports.index');
        Route::post(
            'monitoring/reporting-periods/{reporting_period}/plan-items/{plan_item}/accomplishments',
            [AccomplishmentController::class, 'store'],
        )->name('monitoring.accomplishments.store');
        Route::patch(
            'monitoring/reporting-periods/{reporting_period}/plan-items/{plan_item}/accomplishments/{accomplishment}',
            [AccomplishmentController::class, 'update'],
        )->name('monitoring.accomplishments.update');
        Route::post(
            'monitoring/reporting-periods/{reporting_period}/plan-items/{plan_item}/accomplishments/{accomplishment}/submit',
            AccomplishmentSubmissionController::class,
        )->name('monitoring.accomplishments.submit');
        Route::post(
            'evaluations/reporting-periods/{reporting_period}/plan-items/{plan_item}/accomplishments/{accomplishment}',
            AccomplishmentEvaluationController::class,
        )->name('evaluations.store');
        Route::post(
            'monitoring/reporting-periods/{reporting_period}/plan-items/{plan_item}/accomplishments/{accomplishment}/evidence',
            [EvidenceController::class, 'store'],
        )->name('monitoring.accomplishments.evidence.store');
        Route::get(
            'monitoring/reporting-periods/{reporting_period}/plan-items/{plan_item}/accomplishments/{accomplishment}/evidence/{evidence}',
            [EvidenceController::class, 'show'],
        )->name('monitoring.accomplishments.evidence.show');
        Route::get(
            'monitoring/reporting-periods/{reporting_period}/plan-items/{plan_item}/accomplishments/{accomplishment}/evidence/{evidence}/download',
            [EvidenceController::class, 'download'],
        )->name('monitoring.accomplishments.evidence.download');

        Route::get('administration/organizational-units', [OrganizationalUnitController::class, 'index'])->name('administration.organizational-units.index');
        Route::post('administration/organizational-units', [OrganizationalUnitController::class, 'store'])->name('administration.organizational-units.store');
        Route::patch('administration/organizational-units/{organizational_unit}', [OrganizationalUnitController::class, 'update'])->name('administration.organizational-units.update');

        Route::get('administration/departments', [DepartmentController::class, 'index'])->name('administration.departments.index');
        Route::post('administration/departments', [DepartmentController::class, 'store'])->name('administration.departments.store');
        Route::patch('administration/departments/{department}', [DepartmentController::class, 'update'])->name('administration.departments.update');

        Route::get('administration/roles', [RoleController::class, 'index'])->name('administration.roles.index');
        Route::post('administration/roles', [RoleController::class, 'store'])->name('administration.roles.store');

        Route::get('administration/users', [UserController::class, 'index'])->name('administration.users.index');
        Route::post('administration/users', [UserController::class, 'store'])->name('administration.users.store');
        Route::patch('administration/users/{user}', [UserController::class, 'update'])->name('administration.users.update');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
