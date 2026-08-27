<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAcademicYearContext
{
    public const ATTRIBUTE_KEY = 'selected_academic_year';

    public const SESSION_KEY = 'selected_academic_year_id';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
            return $next($request);
        }

        $selectedAcademicYearId = $request->session()->get(self::SESSION_KEY);
        $selectedAcademicYear = is_numeric($selectedAcademicYearId)
            ? AcademicYear::query()->find((int) $selectedAcademicYearId)
            : null;

        if ($selectedAcademicYear === null) {
            $selectedAcademicYear = AcademicYear::query()
                ->orderByDesc('is_current')
                ->orderByDesc('start_year')
                ->first();

            if ($selectedAcademicYear !== null) {
                $request->session()->put(self::SESSION_KEY, $selectedAcademicYear->id);
            } else {
                $request->session()->forget(self::SESSION_KEY);
            }
        }

        $request->attributes->set(self::ATTRIBUTE_KEY, $selectedAcademicYear);

        return $next($request);
    }
}
