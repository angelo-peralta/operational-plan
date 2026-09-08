<?php

namespace App\Http\Controllers;

use App\Data\OfficialOperationalPlanData;
use App\Data\OperationalPlanData;
use App\Http\Requests\ViewOperationalPlanRequest;
use Inertia\Inertia;
use Inertia\Response;

class OperationalPlanOfficialViewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        ViewOperationalPlanRequest $request,
        string $currentTeam,
        OfficialOperationalPlanData $officialData,
    ): Response {
        $operationalPlan = $request->operationalPlan()
            ->load([
                'academicYear:id,name,status,is_current,start_year,end_year',
                'organizationalUnit:id,name,code',
                'accountableUser:id,name',
                'keyResultAreas.planItems.coAccountableOrganizationalUnits:id,name,code',
                'keyResultAreas.planItems.accomplishments.reportingPeriod:id,academic_year_id,name,code,sequence',
                'keyResultAreas.planItems.accomplishments.submissions.evaluations.reviewer:id,name',
                'keyResultAreas.planItems.accomplishments.submissions.evaluations.organizationalUnit:id,name,code,type',
                'statusHistories.actor:id,name',
            ])
            ->loadCount(['keyResultAreas', 'planItems']);

        return Inertia::render('operational-plans/official', [
            'institutionName' => config('app.name'),
            'operationalPlan' => OperationalPlanData::detail($operationalPlan),
            'report' => $officialData->forPlan($operationalPlan),
        ]);
    }
}
