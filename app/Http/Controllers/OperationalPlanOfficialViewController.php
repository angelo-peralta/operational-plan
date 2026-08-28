<?php

namespace App\Http\Controllers;

use App\Data\OperationalPlanData;
use App\Http\Requests\ViewOperationalPlanRequest;
use Inertia\Inertia;
use Inertia\Response;

class OperationalPlanOfficialViewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ViewOperationalPlanRequest $request, string $currentTeam): Response
    {
        $operationalPlan = $request->operationalPlan()
            ->load([
                'academicYear:id,name,status,is_current,start_year,end_year',
                'department:id,name,code',
                'accountableUser:id,name',
                'keyResultAreas.planItems.coAccountableDepartments:id,name,code',
                'statusHistories.actor:id,name',
            ])
            ->loadCount(['keyResultAreas', 'planItems']);

        return Inertia::render('operational-plans/official', [
            'institutionName' => config('app.name'),
            'operationalPlan' => OperationalPlanData::detail($operationalPlan),
        ]);
    }
}
