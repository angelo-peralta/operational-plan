<?php

namespace App\Http\Controllers;

use App\Data\DashboardData;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardData $dashboardData): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $academicYear = $request->attributes->get(ResolveAcademicYearContext::ATTRIBUTE_KEY);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $this->pendingInvitations($user),
            'summary' => $dashboardData->forUser(
                $academicYear instanceof AcademicYear ? $academicYear : null,
                $user,
            ),
        ]);
    }

    /** @return Collection<int, array{code: string, inviterName: string, team: array{name: string, slug: string}}> */
    private function pendingInvitations(User $user): Collection
    {
        return TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [Str::lower($user->email)])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);
    }
}
