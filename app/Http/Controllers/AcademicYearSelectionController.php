<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AcademicYearSelectionController extends Controller
{
    public function __invoke(
        Request $request,
        string $currentTeam,
        AcademicYear $academicYear,
    ): RedirectResponse {
        Gate::authorize('view', $academicYear);

        $request->session()->put(ResolveAcademicYearContext::SESSION_KEY, $academicYear->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Academic Year changed to :name.', ['name' => $academicYear->name]),
        ]);

        return to_route('dashboard', ['current_team' => $currentTeam]);
    }
}
