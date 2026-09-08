<?php

use App\Enums\AccomplishmentStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('first and second semester accomplishments remain isolated and calculate independently', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    $unit = Department::factory()->create();
    $staff = User::factory()->departmentUser()->forDepartment($unit)->create();
    $firstSemester = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $secondSemester = ReportingPeriod::factory()->open()->secondSemester()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($unit)->create();
    $item = PlanItem::factory()->for(KeyResultArea::factory()->for($plan))->create([
        'target_value' => 20,
        'target_unit' => 'participants',
        'target_operator' => 'at_least',
    ]);

    foreach ([
        [$firstSemester, '25', 'First-semester delivery was completed.'],
        [$secondSemester, '10', 'Second-semester delivery is in progress.'],
    ] as [$period, $reportedValue, $description]) {
        $this->actingAs($staff)
            ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
            ->post(route('monitoring.accomplishments.store', [
                'current_team' => $staff->currentTeam,
                'reporting_period' => $period,
                'plan_item' => $item,
            ]), [
                'reported_value' => $reportedValue,
                'accomplishment_text' => $description,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    $accomplishments = Accomplishment::query()->orderBy('reporting_period_id')->get()->keyBy('reporting_period_id');
    expect($accomplishments)->toHaveCount(2)
        ->and($accomplishments[$firstSemester->id])
        ->percentage_accomplished->toBe('125.0000')
        ->status->toBe(AccomplishmentStatus::Draft)
        ->created_by->toBe($staff->id)
        ->and($accomplishments[$secondSemester->id])
        ->percentage_accomplished->toBe('50.0000')
        ->status->toBe(AccomplishmentStatus::Draft)
        ->created_by->toBe($staff->id);

    $this->actingAs($staff)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', [
            'current_team' => $staff->currentTeam,
            'reporting_period' => $secondSemester,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedReportingPeriod.name', 'Second Semester')
            ->where('operationalPlans.0.organizationalUnit.id', $unit->id)
            ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.id', $accomplishments[$secondSemester->id]->id)
            ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.percentageAccomplished', '50.0000'));
});
