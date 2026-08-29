<?php

use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Department;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function evidenceContext(): array
{
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $user = User::factory()->departmentUser()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->open()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create();

    return compact('academicYear', 'department', 'user', 'period', 'planItem', 'accomplishment');
}

test('department users upload private evidence with server derived metadata', function () {
    Storage::fake('local');
    extract(evidenceContext());
    $file = UploadedFile::fake()->image('workshop-photo.jpg', 800, 600)->size(512);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.evidence.store', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
            'accomplishment' => $accomplishment,
        ]), [
            'file' => $file,
            'evidence_type' => 'Photo',
            'title' => 'Workshop participants',
            'description' => 'Participants during the first workshop.',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $evidence = Evidence::query()->sole();
    expect($evidence)
        ->accomplishment_id->toBe($accomplishment->id)
        ->uploaded_by->toBe($user->id)
        ->original_filename->toBe('workshop-photo.jpg')
        ->mime_type->toBe('image/jpeg')
        ->checksum->toHaveLength(64)
        ->stored_path->toStartWith("evidence/{$academicYear->id}/{$accomplishment->id}/");
    Storage::disk('local')->assertExists($evidence->stored_path);
    Storage::disk('public')->assertMissing($evidence->stored_path);
});

test('evidence upload rejects unsupported and oversized files', function (UploadedFile $file) {
    Storage::fake('local');
    extract(evidenceContext());

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.evidence.store', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
            'accomplishment' => $accomplishment,
        ]), ['file' => $file]);

    $response->assertInvalid('file');
    $this->assertDatabaseEmpty('evidence');
})->with([
    'unsupported executable' => fn () => UploadedFile::fake()->create('payload.exe', 100, 'application/octet-stream'),
    'file over ten megabytes' => fn () => UploadedFile::fake()->create('large.pdf', 10_241, 'application/pdf'),
]);

test('cross-department users receive 404 when uploading or accessing evidence', function () {
    Storage::fake('local');
    extract(evidenceContext());
    $evidence = Evidence::factory()->for($accomplishment)->for($user, 'uploader')->create();
    Storage::disk('local')->put($evidence->stored_path, 'private evidence');
    $otherUser = User::factory()->departmentUser()->create();
    $parameters = [
        'current_team' => $otherUser->currentTeam,
        'reporting_period' => $period,
        'plan_item' => $planItem,
        'accomplishment' => $accomplishment,
    ];

    $this->actingAs($otherUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.evidence.store', $parameters), [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ])
        ->assertNotFound();
    $this->actingAs($otherUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.accomplishments.evidence.show', [...$parameters, 'evidence' => $evidence]))
        ->assertNotFound();
    expect(Evidence::query()->count())->toBe(1);
});

test('reviewers view and download evidence through authorized routes', function () {
    Storage::fake('local');
    extract(evidenceContext());
    $evidence = Evidence::factory()->for($accomplishment)->for($user, 'uploader')->create([
        'original_filename' => 'semester-report.pdf',
        'mime_type' => 'application/pdf',
    ]);
    Storage::disk('local')->put($evidence->stored_path, 'private report content');
    $reviewer = User::factory()->reviewer()->create();
    $parameters = [
        'current_team' => $reviewer->currentTeam,
        'reporting_period' => $period,
        'plan_item' => $planItem,
        'accomplishment' => $accomplishment,
        'evidence' => $evidence,
    ];

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.accomplishments.evidence.show', $parameters))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.accomplishments.evidence.download', $parameters))
        ->assertDownload('semester-report.pdf');
});

test('closed academic years forbid evidence uploads without deleting existing evidence', function () {
    Storage::fake('local');
    extract(evidenceContext());
    $existingEvidence = Evidence::factory()->for($accomplishment)->for($user, 'uploader')->create();
    $academicYear->update(['status' => 'closed']);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.evidence.store', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
            'accomplishment' => $accomplishment,
        ]), ['file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf')]);

    $response->assertForbidden();
    expect(Evidence::query()->sole()->is($existingEvidence))->toBeTrue();
});

test('evidence routes reject records outside the selected academic year', function () {
    Storage::fake('local');
    extract(evidenceContext());
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $evidence = Evidence::factory()->for($accomplishment)->for($user, 'uploader')->create();

    $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $otherAcademicYear->id])
        ->get(route('monitoring.accomplishments.evidence.show', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
            'accomplishment' => $accomplishment,
            'evidence' => $evidence,
        ]))
        ->assertNotFound();
});
