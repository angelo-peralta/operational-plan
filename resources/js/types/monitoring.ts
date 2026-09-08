import type { EvaluationHistoryEntry } from './evaluation';
import type {
    AcademicYearOption,
    OperationalPlanRemark,
    OperationalPlanStatus,
    OrganizationalUnitSummary,
    ReportingPeriod,
    TargetOperator,
} from './operational-plan';

export type AccomplishmentStatus =
    | 'draft'
    | 'submitted'
    | 'under_review'
    | 'accepted'
    | 'returned'
    | 'rejected';

export type MonitoringReportingPeriod = ReportingPeriod & {
    acceptsSubmissions: boolean;
};

type AccomplishmentSubmissionStatus =
    | AccomplishmentStatus
    | 'queued_for_analysis'
    | 'analyzing'
    | 'ready_for_review';

export type AccomplishmentSubmissionHistory = {
    id: number;
    revision: number;
    fromStatus: AccomplishmentSubmissionStatus;
    toStatus: AccomplishmentSubmissionStatus;
    toStatusLabel: string;
    evidenceCount: number;
    submitter: {
        id: number;
        name: string;
    };
    submittedAt: string;
};

export type Accomplishment = {
    id: number;
    reportedValue: string | null;
    accomplishmentText: string | null;
    percentageAccomplished: string | null;
    status: AccomplishmentStatus;
    statusLabel: string;
    submittedAt: string | null;
    resubmittedAt: string | null;
    updatedAt: string | null;
    createdBy: { id: number; name: string } | null;
    updatedBy: { id: number; name: string } | null;
    evidence: Evidence[];
    remarks: OperationalPlanRemark[];
    submissionBlocker: string | null;
    submissionHistory: AccomplishmentSubmissionHistory[];
    evaluationHistory: EvaluationHistoryEntry[];
    permissions: {
        submitAccomplishment: boolean;
        uploadEvidence: boolean;
        createRemark: boolean;
    };
};

export type Evidence = {
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

export type MonitoringPlanItemPermissions = {
    createAccomplishment: boolean;
    updateAccomplishment: boolean;
};

export type MonitoringPlanItem = {
    id: number;
    objective: string;
    strategy: string | null;
    kpiTargetText: string;
    targetValue: string | null;
    targetUnit: string | null;
    targetOperator: TargetOperator | null;
    targetFrequency: string | null;
    documentaryEvidenceRequirements: string[];
    sortOrder: number;
    accomplishment: Accomplishment | null;
    permissions: MonitoringPlanItemPermissions;
};

export type MonitoringKeyResultArea = {
    id: number;
    code: string | null;
    name: string;
    description: string | null;
    sortOrder: number;
    planItems: MonitoringPlanItem[];
};

export type MonitoringOperationalPlan = {
    id: number;
    organizationalUnit: OrganizationalUnitSummary;
    goal: string;
    status: OperationalPlanStatus;
    statusLabel: string;
    keyResultAreas: MonitoringKeyResultArea[];
};

export type MonitoringPageProps = {
    academicYear: AcademicYearOption | null;
    reportingPeriods: MonitoringReportingPeriod[];
    selectedReportingPeriod: MonitoringReportingPeriod | null;
    operationalPlans: MonitoringOperationalPlan[];
};
