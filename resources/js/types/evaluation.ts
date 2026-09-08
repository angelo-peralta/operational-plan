import type {
    AcademicYearOption,
    OperationalPlanRemark,
    OrganizationalUnitSummary,
    ReportingPeriod,
    TargetOperator,
} from './operational-plan';

export type EvaluationDecision =
    | 'accepted'
    | 'request_revision'
    | 'request_additional_evidence'
    | 'rejected';

export type EvaluationRating = 0 | 1 | 2 | 3;

export type EvaluationResultingStatus = 'accepted' | 'returned' | 'rejected';

export type EvaluationRatingOption = {
    value: EvaluationRating;
    label: string;
};

export type EvaluationHistoryEntry = {
    id: number;
    submissionRevision: number;
    decision: EvaluationDecision;
    decisionLabel: string;
    rating: EvaluationRating | null;
    ratingLabel: string | null;
    remarks: string | null;
    resultingStatus: EvaluationResultingStatus;
    resultingStatusLabel: string;
    reviewer: {
        id: number;
        name: string;
    };
    authority: EvaluationAuthority;
    isOverride: boolean;
    overridesEvaluationId: number | null;
    isEffective: boolean;
    reviewedAt: string;
};

export type EvaluationAuthority = {
    id: number | null;
    name: string | null;
    label: string;
};

export type EffectiveEvaluation = {
    id: number;
    rating: EvaluationRating;
    ratingLabel: string;
    reviewer: {
        id: number;
        name: string;
    };
    authority: EvaluationAuthority;
    isOverride: boolean;
    overridesEvaluationId: number | null;
};

export type PresidentialReview = {
    sourceEvaluationId: number;
    rating: EvaluationRating;
    ratingLabel: string;
    remarks: string | null;
    reviewer: {
        id: number;
        name: string;
    };
    authority: EvaluationAuthority;
};

export type EvaluationEvidence = {
    id: number;
    evidenceType: string | null;
    title: string | null;
    description: string | null;
    originalFilename: string;
    mimeType: string;
    fileSize: number;
    checksum: string | null;
    uploadedBy: {
        id: number;
        name: string;
    };
    createdAt: string | null;
};

export type EvaluationReportingPeriod = ReportingPeriod & {
    acceptsSubmissions: boolean;
};

export type EvaluationQueueItem = {
    id: number;
    academicYear: Pick<AcademicYearOption, 'id' | 'name'>;
    reportingPeriod: Pick<ReportingPeriod, 'id' | 'name' | 'code'>;
    organizationalUnit: OrganizationalUnitSummary;
    operationalPlan: {
        id: number;
        goal: string;
        accountableName: string | null;
        accountablePosition: string | null;
    };
    keyResultArea: {
        id: number;
        code: string | null;
        name: string;
    };
    planItem: {
        id: number;
        objective: string;
        strategy: string | null;
        kpiTargetText: string;
        targetValue: string | null;
        targetUnit: string | null;
        targetOperator: TargetOperator | null;
        targetFrequency: string | null;
        resourcesNeeded: string | null;
        documentaryEvidenceRequirements: string[];
    };
    accomplishment: {
        id: number;
        reportedValue: string | null;
        accomplishmentText: string | null;
        percentageAccomplished: string | null;
        status: 'under_review' | 'accepted';
        statusLabel: string;
    };
    submission: {
        id: number;
        revision: number;
        submittedAt: string;
        submitter: {
            id: number;
            name: string;
        };
    };
    evidence: EvaluationEvidence[];
    remarks: OperationalPlanRemark[];
    evaluationHistory: EvaluationHistoryEntry[];
    effectiveEvaluation: EffectiveEvaluation | null;
    presidentialReview: PresidentialReview | null;
    permissions: {
        reviewAccomplishment: boolean;
    };
};

export type EvaluationPageProps = {
    academicYear: AcademicYearOption | null;
    reportingPeriods: EvaluationReportingPeriod[];
    selectedReportingPeriod: EvaluationReportingPeriod | null;
    queue: EvaluationQueueItem[];
    ratingOptions: EvaluationRatingOption[];
};
