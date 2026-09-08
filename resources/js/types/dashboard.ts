import type {
    OrganizationalUnitSummary,
    OperationalPlanStatus,
    ReportingPeriodStatus,
} from './operational-plan';
import type { DashboardInvitation } from './teams';

export type DashboardRating = 0 | 1 | 2 | 3;

export type DashboardRatingSummary = {
    ratedCount: number;
    average: number | null;
    distribution: Array<{
        rating: DashboardRating;
        label: string;
        count: number;
    }>;
};

export type DashboardReportingPeriodSummary = {
    id: number;
    name: string;
    code: string;
    status: ReportingPeriodStatus;
    submitted: number;
    awaitingReview: number;
    returned: number;
    accepted: number;
    rejected: number;
};

export type DashboardRemarkSummary = {
    total: number;
    open: number;
    addressed: number;
    resolved: number;
};

export type DashboardEvaluationSummary = {
    currentSubmissions: number;
    evaluated: number;
    awaitingReview: number;
    historyCount: number;
    completionRate: number | null;
};

export type OrganizationalUnitDashboardSummary = {
    kind: 'unit';
    organizationalUnit: OrganizationalUnitSummary | null;
    plan: {
        id: number;
        status: OperationalPlanStatus;
        statusLabel: string;
        keyResultAreaCount: number;
        planItemCount: number;
    } | null;
    reportingPeriods: DashboardReportingPeriodSummary[];
    pendingEvidence: number;
    remarks: DashboardRemarkSummary;
    accomplishments: {
        awaitingReview: number;
        returned: number;
        accepted: number;
        rejected: number;
    };
    evaluations: DashboardEvaluationSummary;
    ratings: DashboardRatingSummary;
};

export type OversightDashboardSummary = {
    kind: 'oversight';
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
    accomplishments: {
        total: number;
        awaitingReview: number;
        returned: number;
        accepted: number;
        rejected: number;
    };
    remarks: DashboardRemarkSummary;
    evaluations: DashboardEvaluationSummary;
    ratings: DashboardRatingSummary;
};

export type DashboardSummary =
    | OrganizationalUnitDashboardSummary
    | OversightDashboardSummary
    | { kind: 'units'; units: OrganizationalUnitDashboardSummary[] };

export type DashboardPageProps = {
    pendingInvitations?: DashboardInvitation[];
    summary: DashboardSummary | null;
};
