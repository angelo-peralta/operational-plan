<?php

namespace App\Enums;

enum RemarkField: string
{
    case Accountable = 'accountable';
    case Goal = 'goal';
    case KraName = 'kra_name';
    case Objective = 'objective';
    case Strategy = 'strategy';
    case KpiTargetText = 'kpi_target_text';
    case CoAccountables = 'co_accountables';
    case ResourcesNeeded = 'resources_needed';
    case DocumentaryEvidenceRequirements = 'documentary_evidence_requirements';
    case AccomplishmentText = 'accomplishment_text';
    case Evidence = 'evidence';
    case Evaluation = 'evaluation';
}
