<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AcademicYearSelectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
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
