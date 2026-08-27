<?php

use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use Illuminate\Database\QueryException;

test('the database prevents more than one current academic year', function () {
    AcademicYear::factory()->current()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
    ]);

    expect(fn () => AcademicYear::factory()->current()->create([
        'name' => 'AY 2026-2027',
        'start_year' => 2026,
        'end_year' => 2027,
    ]))->toThrow(QueryException::class);
});

test('department editing is available only for open academic years', function () {
    $openAcademicYear = AcademicYear::factory()->open()->create();
    $closedAcademicYear = AcademicYear::factory()->closed()->create();
    $archivedAcademicYear = AcademicYear::factory()->archived()->create();

    expect($openAcademicYear->isEditableForDepartment())->toBeTrue()
        ->and($closedAcademicYear->isEditableForDepartment())->toBeFalse()
        ->and($archivedAcademicYear->isEditableForDepartment())->toBeFalse();
});

test('reporting periods accept submissions only when both period and academic year are open', function () {
    $openAcademicYear = AcademicYear::factory()->open()->create();
    $closedAcademicYear = AcademicYear::factory()->closed()->create();
    $openPeriod = ReportingPeriod::factory()->for($openAcademicYear)->open()->create();
    $closedPeriod = ReportingPeriod::factory()->for($openAcademicYear)->closed()->create();
    $periodInClosedYear = ReportingPeriod::factory()->for($closedAcademicYear)->open()->create();

    expect($openPeriod->acceptsSubmissions())->toBeTrue()
        ->and($closedPeriod->acceptsSubmissions())->toBeFalse()
        ->and($periodInClosedYear->acceptsSubmissions())->toBeFalse();
});
