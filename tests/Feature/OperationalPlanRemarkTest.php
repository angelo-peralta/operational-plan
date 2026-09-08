<?php

use App\Enums\AccomplishmentStatus;
use App\Enums\OperationalPlanStatus;
use App\Enums\RemarkStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Department;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\Remark;
use App\Models\ReportingPeriod;
use App\Models\User;

test('executives add field remarks and owning staff address them before an executive resolves them', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $unit = Department::factory()->create();
    $owner = User::factory()->departmentUser()->forDepartment($unit)->create();
    $reviewer = User::factory()->executiveOver($unit)->create();
    $plan = OperationalPlan::factory()->submitted()->for($academicYear, 'academicYear')->for($unit)->create();
    $kra = KeyResultArea::factory()->for($plan)->create();
    $item = PlanItem::factory()->for($kra)->create();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.remarks.store', ['current_team' => $reviewer->currentTeam, 'operational_plan' => $plan]), [
            'target_type' => 'plan_item', 'target_id' => $item->id, 'field_key' => 'kpi_target_text',
            'category' => 'clarification', 'content' => 'Explain how this target will be measured.',
        ])->assertSessionHasNoErrors()->assertRedirect();

    $remark = Remark::query()->sole();
    expect($remark)->status->toBe(RemarkStatus::Open)
        ->and($remark->academic_year_id)->toBe($academicYear->id)
        ->and($remark->remarkable->is($item))->toBeTrue()
        ->and($remark->statusHistories)->toHaveCount(1);

    $plan->update(['status' => OperationalPlanStatus::Returned]);
    $this->actingAs($owner)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.remarks.update', ['current_team' => $owner->currentTeam, 'operational_plan' => $plan, 'remark' => $remark]), [
            'status' => 'addressed', 'note' => 'Target language updated.',
        ])->assertSessionHasNoErrors()->assertRedirect();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.remarks.update', ['current_team' => $reviewer->currentTeam, 'operational_plan' => $plan, 'remark' => $remark]), [
            'status' => 'resolved',
        ])->assertSessionHasNoErrors()->assertRedirect();

    expect($remark->fresh())->status->toBe(RemarkStatus::Resolved)
        ->and($remark->statusHistories()->count())->toBe(3);
});

test('remarks cannot target records or reporting periods from another academic year', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $plan = OperationalPlan::factory()->submitted()->for($academicYear, 'academicYear')->for(Department::factory())->create();
    $otherItem = PlanItem::factory()->create();
    $reviewer = User::factory()->executiveOver($plan->department)->create();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.remarks.store', ['current_team' => $reviewer->currentTeam, 'operational_plan' => $plan]), [
            'target_type' => 'plan_item', 'target_id' => $otherItem->id,
            'category' => 'correction', 'content' => 'Cross-scope attempt.',
        ])->assertSessionHasErrors('target_id');

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $otherAcademicYear->id])
        ->post(route('operational-plans.remarks.store', ['current_team' => $reviewer->currentTeam, 'operational_plan' => $plan]), [
            'target_type' => 'operational_plan', 'target_id' => $plan->id,
            'category' => 'correction', 'content' => 'Cross-year route attempt.',
        ])->assertNotFound();

    $this->assertDatabaseEmpty('remarks');
});

test('closed academic years preserve remark history and forbid every remark mutation', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $unit = Department::factory()->create();
    $owner = User::factory()->departmentUser()->forDepartment($unit)->create();
    $reviewer = User::factory()->executiveOver($unit)->create();
    $plan = OperationalPlan::factory()->returned()->for($academicYear, 'academicYear')->for($unit)->create();
    $item = PlanItem::factory()->for(KeyResultArea::factory()->for($plan))->create();
    $openRemark = $item->remarks()->create([
        'academic_year_id' => $academicYear->id,
        'operational_plan_id' => $plan->id,
        'author_id' => $reviewer->id,
        'field_key' => 'objective',
        'category' => 'clarification',
        'content' => 'Open historical review remark.',
        'status' => RemarkStatus::Open,
    ]);
    $openRemark->statusHistories()->create([
        'actor_id' => $reviewer->id,
        'from_status' => null,
        'to_status' => RemarkStatus::Open,
        'created_at' => now(),
    ]);
    $addressedRemark = $item->remarks()->create([
        'academic_year_id' => $academicYear->id,
        'operational_plan_id' => $plan->id,
        'author_id' => $reviewer->id,
        'field_key' => 'strategy',
        'category' => 'clarification',
        'content' => 'Addressed historical review remark.',
        'status' => RemarkStatus::Addressed,
        'addressed_by' => $owner->id,
        'addressed_at' => now(),
    ]);
    $addressedRemark->statusHistories()->create([
        'actor_id' => $owner->id,
        'from_status' => RemarkStatus::Open,
        'to_status' => RemarkStatus::Addressed,
        'created_at' => now(),
    ]);
    $academicYear->update(['status' => 'closed']);
    $routeParameters = [
        'current_team' => $reviewer->currentTeam,
        'operational_plan' => $plan,
    ];

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.remarks.store', $routeParameters), [
            'target_type' => 'plan_item',
            'target_id' => $item->id,
            'field_key' => 'kpi_target_text',
            'category' => 'clarification',
            'content' => 'Closed-year mutation attempt.',
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.remarks.update', [
            'current_team' => $owner->currentTeam,
            'operational_plan' => $plan,
            'remark' => $openRemark,
        ]), ['status' => RemarkStatus::Addressed->value])
        ->assertForbidden();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.remarks.update', [
            ...$routeParameters,
            'remark' => $addressedRemark,
        ]), ['status' => RemarkStatus::Resolved->value])
        ->assertForbidden();

    expect(Remark::query()->count())->toBe(2)
        ->and($openRemark->fresh()->status)->toBe(RemarkStatus::Open)
        ->and($openRemark->statusHistories()->count())->toBe(1)
        ->and($addressedRemark->fresh()->status)->toBe(RemarkStatus::Addressed)
        ->and($addressedRemark->statusHistories()->count())->toBe(1);
});

test('semester accomplishment and evidence remarks survive addressing and resubmission', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $unit = Department::factory()->create();
    $owner = User::factory()->officeHead()->forDepartment($unit)->create();
    $reviewer = User::factory()->executiveOver($unit)->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($unit)->create();
    $item = PlanItem::factory()->for(KeyResultArea::factory()->for($plan))->create();
    $accomplishment = Accomplishment::factory()->returned()->for($item)->for($period)->create([
        'created_by' => $owner->id,
        'updated_by' => $reviewer->id,
        'submitted_by' => $owner->id,
        'submitted_at' => now()->subDay(),
    ]);
    $originalEvidence = Evidence::factory()->for($accomplishment)->for($owner, 'uploader')->create();
    AccomplishmentSubmission::factory()->for($accomplishment)->for($owner, 'submitter')->create([
        'revision' => 1,
        'evidence_count' => 1,
    ]);

    foreach ([
        ['target_type' => 'accomplishment', 'target_id' => $accomplishment->id, 'field_key' => 'accomplishment_text'],
        ['target_type' => 'evidence', 'target_id' => $originalEvidence->id, 'field_key' => 'evidence'],
    ] as $target) {
        $this->actingAs($reviewer)
            ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
            ->post(route('operational-plans.remarks.store', [
                'current_team' => $reviewer->currentTeam,
                'operational_plan' => $plan,
            ]), [
                ...$target,
                'category' => 'clarification',
                'content' => 'Provide the signed completion details.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    $remarks = Remark::query()->orderBy('id')->get();
    expect($remarks)->toHaveCount(2)
        ->and($remarks->pluck('reporting_period_id')->unique()->all())->toBe([$period->id])
        ->and($remarks[0]->remarkable->is($accomplishment))->toBeTrue()
        ->and($remarks[1]->remarkable->is($originalEvidence))->toBeTrue();

    $this->actingAs($owner)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.remarks.update', [
            'current_team' => $owner->currentTeam,
            'operational_plan' => $plan,
            'remark' => $remarks[0],
        ]), [
            'status' => 'addressed',
            'note' => 'The accomplishment was updated and additional proof was uploaded.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    Evidence::factory()->for($accomplishment)->for($owner, 'uploader')->create();
    $this->actingAs($owner)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.submit', [
            'current_team' => $owner->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $item,
            'accomplishment' => $accomplishment,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($accomplishment->fresh())
        ->status->toBe(AccomplishmentStatus::UnderReview)
        ->and(Remark::query()->count())->toBe(2)
        ->and($remarks[0]->fresh()->status)->toBe(RemarkStatus::Addressed)
        ->and($remarks[0]->statusHistories()->count())->toBe(2);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.remarks.update', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $plan,
            'remark' => $remarks[0],
        ]), ['status' => 'resolved'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($remarks[0]->fresh()->status)->toBe(RemarkStatus::Resolved)
        ->and($remarks[0]->statusHistories()->count())->toBe(3);
});

test('monitoring remarks enforce their accomplishment reporting period and plan', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $unit = Department::factory()->create();
    $reviewer = User::factory()->executiveOver($unit)->create();
    $firstSemester = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $secondSemester = ReportingPeriod::factory()->open()->secondSemester()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($unit)->create();
    $item = PlanItem::factory()->for(KeyResultArea::factory()->for($plan))->create();
    $accomplishment = Accomplishment::factory()->readyForReview()->for($item)->for($firstSemester)->create();
    $otherEvidence = Evidence::factory()->create();

    $route = route('operational-plans.remarks.store', [
        'current_team' => $reviewer->currentTeam,
        'operational_plan' => $plan,
    ]);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post($route, [
            'target_type' => 'accomplishment',
            'target_id' => $accomplishment->id,
            'field_key' => 'accomplishment_text',
            'reporting_period_id' => $secondSemester->id,
            'category' => 'correction',
            'content' => 'Wrong semester attempt.',
        ])
        ->assertSessionHasErrors('reporting_period_id');

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post($route, [
            'target_type' => 'evidence',
            'target_id' => $otherEvidence->id,
            'field_key' => 'evidence',
            'category' => 'correction',
            'content' => 'Wrong plan attempt.',
        ])
        ->assertSessionHasErrors('target_id');

    $this->assertDatabaseEmpty('remarks');
});
