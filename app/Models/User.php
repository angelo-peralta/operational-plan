<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $entra_tenant_id
 * @property string|null $entra_object_id
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property int|null $department_id
 * @property int|null $role_id
 * @property UserRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, OperationalPlan> $accountableOperationalPlans
 * @property-read Collection<int, OperationalPlan> $approvedOperationalPlans
 * @property-read Collection<int, OperationalPlan> $closedOperationalPlans
 * @property-read Collection<int, OperationalPlan> $createdOperationalPlans
 * @property-read Collection<int, OrganizationalUnitUserAssignment> $organizationalUnitAssignments
 * @property-read Department|null $department
 * @property-read Role|null $operationalRole
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, OperationalPlan> $returnedOperationalPlans
 * @property-read Collection<int, Evaluation> $reviewedEvaluations
 * @property-read Collection<int, OperationalPlan> $submittedOperationalPlans
 * @property-read Collection<int, Accomplishment> $submittedAccomplishments
 * @property-read Collection<int, Evidence> $uploadedEvidence
 * @property-read Collection<int, Team> $teams
 * @property-read Collection<int, OperationalPlan> $updatedOperationalPlans
 */
#[Fillable(['name', 'email', 'password', 'current_team_id', 'department_id', 'role_id', 'role'])]
#[Hidden([
    'password',
    'legacy_role',
    'legacy_role_id',
    'entra_tenant_id',
    'entra_object_id',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'remember_token',
])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'staff',
    ];

    /**
     * Get the department assigned to the user.
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<Role, $this> */
    public function operationalRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function accountableOperationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class, 'accountable_user_id');
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function createdOperationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class, 'created_by');
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function updatedOperationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class, 'updated_by');
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function submittedOperationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class, 'submitted_by');
    }

    /** @return HasMany<Accomplishment, $this> */
    public function submittedAccomplishments(): HasMany
    {
        return $this->hasMany(Accomplishment::class, 'submitted_by');
    }

    /** @return HasMany<Evidence, $this> */
    public function uploadedEvidence(): HasMany
    {
        return $this->hasMany(Evidence::class, 'uploaded_by');
    }

    /** @return HasMany<Evaluation, $this> */
    public function reviewedEvaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'reviewer_id');
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function approvedOperationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class, 'approved_by');
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function returnedOperationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class, 'returned_by');
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function closedOperationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class, 'closed_by');
    }

    /**
     * Determine whether the user has the given system role.
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->accessLevel() === $role;
    }

    public function accessLevel(): UserRole
    {
        return $this->role;
    }

    public function roleLabel(): string
    {
        return $this->role->label();
    }

    public function isOfficeHead(): bool
    {
        return $this->hasRole(UserRole::OfficeHead);
    }

    /** @return HasMany<OrganizationalUnitUserAssignment, $this> */
    public function organizationalUnitAssignments(): HasMany
    {
        return $this->hasMany(OrganizationalUnitUserAssignment::class);
    }

    /**
     * Determine whether the user is a super administrator.
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasRole(UserRole::SystemAdmin);
    }

    /**
     * Determine whether the user is a reviewer.
     */
    public function isExecutive(): bool
    {
        return $this->hasRole(UserRole::Executive);
    }

    /**
     * Determine whether the user is a department user.
     */
    public function isStaff(): bool
    {
        return $this->hasRole(UserRole::Staff);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
