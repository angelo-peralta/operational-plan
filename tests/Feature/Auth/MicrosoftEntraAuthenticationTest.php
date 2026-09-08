<?php

use App\Actions\Authentication\BeginMicrosoftEntraLogin;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.microsoft', [
        'tenant_id' => '11111111-1111-4111-8111-111111111111',
        'client_id' => '22222222-2222-4222-8222-222222222222',
        'client_secret' => 'testing-client-secret',
        'redirect_uri' => 'https://operational-plan.test/auth/microsoft/callback',
        'authority' => 'https://login.microsoftonline.com',
        'graph_url' => 'https://graph.microsoft.com/v1.0',
    ]);
});

test('Microsoft login redirects to the tenant authorization endpoint with state and PKCE', function () {
    $response = $this->get(route('auth.microsoft.redirect'));

    $response->assertRedirect();
    $location = $response->headers->get('Location');
    expect($location)->toBeString();

    $parts = parse_url($location);
    parse_str($parts['query'] ?? '', $query);

    expect($parts['host'] ?? null)->toBe('login.microsoftonline.com')
        ->and($parts['path'] ?? null)->toBe('/11111111-1111-4111-8111-111111111111/oauth2/v2.0/authorize')
        ->and($query['client_id'] ?? null)->toBe('22222222-2222-4222-8222-222222222222')
        ->and($query['response_type'] ?? null)->toBe('code')
        ->and($query['code_challenge_method'] ?? null)->toBe('S256')
        ->and($query['state'] ?? null)->toBe(session(BeginMicrosoftEntraLogin::STATE_SESSION_KEY))
        ->and(session(BeginMicrosoftEntraLogin::CODE_VERIFIER_SESSION_KEY))->toBeString();
});

test('Microsoft login refuses a non tenant-specific authority configuration', function () {
    config()->set('services.microsoft.tenant_id', 'common');

    $response = $this->get(route('auth.microsoft.redirect'));

    $response->assertServiceUnavailable();
    $response->assertSessionMissing(BeginMicrosoftEntraLogin::STATE_SESSION_KEY);
});

test('Microsoft callback rejects invalid state without contacting Microsoft', function () {
    Http::preventStrayRequests();

    $response = $this
        ->withSession(entraCallbackSession())
        ->get(route('auth.microsoft.callback', [
            'state' => 'invalid-state',
            'code' => 'authorization-code',
        ]));

    $response->assertRedirectToRoute('login');
    $response->assertSessionHasErrors(['microsoft']);
    $this->assertGuest();
    Http::assertNothingSent();
});

test('Microsoft callback handles a rejected tenant token exchange safely', function () {
    Http::preventStrayRequests();
    Http::fake([
        entraTokenUrl() => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    $response = $this
        ->withSession(entraCallbackSession())
        ->get(entraCallbackUrl());

    $response->assertRedirectToRoute('login');
    $response->assertSessionHasErrors(['microsoft']);
    $this->assertGuest();
    Http::assertSentCount(1);
});

test('Microsoft callback denies users who were not provisioned locally', function () {
    fakeMicrosoftIdentity(
        objectId: '33333333-3333-4333-8333-333333333333',
        email: 'unknown@example.com',
        displayName: 'Unknown User',
    );

    $response = $this
        ->withSession(entraCallbackSession())
        ->get(entraCallbackUrl());

    $response->assertRedirectToRoute('login');
    $response->assertSessionHasErrors(['microsoft']);
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'unknown@example.com']);
});

test('Microsoft callback links a provisioned user and preserves local authorization', function () {
    $department = Department::factory()->create();
    $customRole = Role::factory()->withAccessLevel(UserRole::Executive)->create([
        'name' => 'Planning Officer',
        'slug' => 'planning-officer',
    ]);
    $user = User::factory()->forOperationalRole($customRole)->forDepartment($department)->create([
        'name' => 'Local Name',
        'email' => 'reviewer@example.com',
    ]);
    $password = $user->password;
    fakeMicrosoftIdentity(
        objectId: '33333333-3333-4333-8333-333333333333',
        email: 'Reviewer@Example.com',
        displayName: 'Microsoft Name',
    );

    $this->withSession(entraCallbackSession());
    $previousSessionId = session()->getId();
    $response = $this->get(entraCallbackUrl());

    $response->assertRedirectToRoute('dashboard', ['current_team' => $user->currentTeam]);
    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($previousSessionId);
    $user->refresh();
    expect($user->entra_tenant_id)->toBe('11111111-1111-4111-8111-111111111111')
        ->and($user->entra_object_id)->toBe('33333333-3333-4333-8333-333333333333')
        ->and($user->name)->toBe('Microsoft Name')
        ->and($user->email)->toBe('reviewer@example.com')
        ->and($user->role_id)->toBe($customRole->id)
        ->and($user->role)->toBe(UserRole::Executive)
        ->and($user->roleLabel())->toBe('Executive')
        ->and($user->department_id)->toBe($department->id)
        ->and($user->password)->toBe($password)
        ->and($user->email_verified_at)->not->toBeNull();
});

test('Microsoft callback matches a returning user by stable object id after an email change', function () {
    $user = User::factory()->superAdmin()->create([
        'email' => 'old-address@example.com',
        'entra_tenant_id' => '11111111-1111-4111-8111-111111111111',
        'entra_object_id' => '33333333-3333-4333-8333-333333333333',
    ]);
    fakeMicrosoftIdentity(
        objectId: '33333333-3333-4333-8333-333333333333',
        email: 'new-address@example.com',
        displayName: 'Renamed Administrator',
    );

    $response = $this
        ->withSession(entraCallbackSession())
        ->get(entraCallbackUrl());

    $response->assertRedirectToRoute('dashboard', ['current_team' => $user->currentTeam]);
    $this->assertAuthenticatedAs($user);
    expect(User::query()->count())->toBe(1);
    $user->refresh();
    expect($user->email)->toBe('new-address@example.com')
        ->and($user->role)->toBe(UserRole::SystemAdmin);
});

test('Microsoft callback rejects an email collision without relinking either account', function () {
    $linkedUser = User::factory()->superAdmin()->create([
        'email' => 'linked@example.com',
        'entra_tenant_id' => '11111111-1111-4111-8111-111111111111',
        'entra_object_id' => '33333333-3333-4333-8333-333333333333',
    ]);
    User::factory()->departmentUser()->create(['email' => 'collision@example.com']);
    fakeMicrosoftIdentity(
        objectId: '33333333-3333-4333-8333-333333333333',
        email: 'collision@example.com',
        displayName: 'Linked User',
    );

    $response = $this
        ->withSession(entraCallbackSession())
        ->get(entraCallbackUrl());

    $response->assertRedirectToRoute('login');
    $response->assertSessionHasErrors(['microsoft']);
    $this->assertGuest();
    expect($linkedUser->fresh()->email)->toBe('linked@example.com');
});

test('Microsoft authenticated users can log out of the Laravel session', function () {
    $user = User::factory()->departmentUser()->create(['email' => 'department@example.com']);
    fakeMicrosoftIdentity(
        objectId: '33333333-3333-4333-8333-333333333333',
        email: 'department@example.com',
        displayName: 'Department User',
    );

    $this->withSession(entraCallbackSession())->get(entraCallbackUrl());
    $this->assertAuthenticatedAs($user);

    $response = $this->post(route('logout'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});

test('Microsoft token and Graph calls use the PKCE verifier and exchanged bearer token', function () {
    User::factory()->departmentUser()->create(['email' => 'department@example.com']);
    fakeMicrosoftIdentity(
        objectId: '33333333-3333-4333-8333-333333333333',
        email: 'department@example.com',
        displayName: 'Department User',
    );

    $this->withSession(entraCallbackSession())->get(entraCallbackUrl());

    Http::assertSent(fn (Request $request): bool => $request->url() === entraTokenUrl()
        && $request['client_id'] === '22222222-2222-4222-8222-222222222222'
        && $request['code_verifier'] === 'valid-code-verifier');
    Http::assertSent(fn (Request $request): bool => str_starts_with(
        $request->url(),
        'https://graph.microsoft.com/v1.0/me',
    ) && $request->hasHeader('Authorization', 'Bearer testing-access-token'));
});

/** @return array<string, string> */
function entraCallbackSession(): array
{
    return [
        BeginMicrosoftEntraLogin::STATE_SESSION_KEY => 'valid-state',
        BeginMicrosoftEntraLogin::CODE_VERIFIER_SESSION_KEY => 'valid-code-verifier',
    ];
}

function entraCallbackUrl(): string
{
    return route('auth.microsoft.callback', [
        'state' => 'valid-state',
        'code' => 'authorization-code',
    ]);
}

function entraTokenUrl(): string
{
    return 'https://login.microsoftonline.com/11111111-1111-4111-8111-111111111111/oauth2/v2.0/token';
}

function fakeMicrosoftIdentity(string $objectId, string $email, string $displayName): void
{
    Http::preventStrayRequests();
    Http::fake([
        entraTokenUrl() => Http::response(['access_token' => 'testing-access-token']),
        'https://graph.microsoft.com/v1.0/me*' => Http::response([
            'id' => $objectId,
            'displayName' => $displayName,
            'mail' => $email,
            'userPrincipalName' => $email,
            'role' => 'super_admin',
            'department_id' => 999,
        ]),
    ]);

    Http::assertNothingSent();
}
