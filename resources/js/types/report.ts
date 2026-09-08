import type {
    AcademicYearOption,
    OperationalPlanStatus,
    ReportingPeriodStatus,
} from './operational-plan';

export type ReportRatingSummary = {
    ratedCount: number;
    average: number | null;
    distribution: Array<{
        rating: 0 | 1 | 2 | 3;
        label: string;
        count: number;
    }>;
};

export type ReportAccomplishmentSummary = {
    total: number;
    awaitingReview: number;
    returned: number;
    accepted: number;
    rejected: number;
};

export type ReportRemarkSummary = {
    total: number;
    open: number;
    addressed: number;
    resolved: number;
};

export type ReportEvaluationSummary = {
    currentSubmissions: number;
    evaluated: number;
    awaitingReview: number;
    historyCount: number;
    completionRate: number | null;
};

export type ReportPeriod = {
    id: number;
    name: string;
    code: string;
    sequence: number;
    status: ReportingPeriodStatus;
};

export type ReportOrganizationalUnit = {
    id: number;
    name: string;
    code: string | null;
    type: string;
    isActive: boolean;
    plan: {
        id: number;
        status: OperationalPlanStatus;
        statusLabel: string;
        keyResultAreaCount: number;
        planItemCount: number;
    } | null;
    accomplishments: ReportAccomplishmentSummary;
    pendingEvidence: number;
    remarks: ReportRemarkSummary;
    evaluations: ReportEvaluationSummary;
    ratings: ReportRatingSummary;
};

export type ExecutiveRemarkReport = {
    id: number;
    organizationalUnit: {
        id: number;
        name: string;
        code: string | null;
    };
    author: { id: number; name: string; authority: string };
    targetType: string;
    targetLabel: string;
    keyResultArea: {
        id: number;
        code: string | null;
        name: string;
    } | null;
    planItem: { id: number; objective: string } | null;
    field: string | null;
    fieldLabel: string;
    period: { id: number; name: string; code: string } | null;
    content: string;
    status: 'open' | 'addressed' | 'resolved';
    statusLabel: string;
    createdAt: string | null;
};

export type ConsolidatedReport = {
    academicYear: AcademicYearOption;
    mode: 'annual' | 'monitoring';
    reportingPeriods: ReportPeriod[];
    selectedReportingPeriod: ReportPeriod | null;
    summary: {
        organizationalUnits: {
            active: number;
            withPlan: number;
            withoutPlan: number;
        };
        plans: {
            total: number;
            draft: number;
            submitted: number;
            approved: number;
            returned: number;
            closed: number;
        };
        planItemCount: number;
        accomplishments: ReportAccomplishmentSummary;
        pendingEvidence: number;
        remarks: ReportRemarkSummary;
        evaluations: ReportEvaluationSummary;
        ratings: ReportRatingSummary;
    };
    organizationalUnits: ReportOrganizationalUnit[];
    remarks: ExecutiveRemarkReport[];
};

export type OfficialEvaluationResult = {
    reportingPeriod: { id: number; name: string; code: string };
    status: string;
    statusLabel: string;
    rating: number | null;
    ratingLabel: string | null;
    remarks: string | null;
    reviewer: {
        id: number;
        name: string;
        authority: string;
    } | null;
    isPresidential: boolean;
    isOverride: boolean;
};

export type OfficialOperationalPlanReport = {
    itemResults: Array<{
        planItemId: number;
        firstSemester: OfficialEvaluationResult | null;
        secondSemester: OfficialEvaluationResult | null;
    }>;
    executiveRemarks: ExecutiveRemarkReport[];
};
