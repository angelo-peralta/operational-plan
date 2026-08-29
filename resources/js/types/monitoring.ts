import type {
    AcademicYearOption,
    DepartmentSummary,
    OperationalPlanStatus,
    ReportingPeriod,
    TargetOperator,
} from './operational-plan';

export type AccomplishmentStatus =
    | 'draft'
    | 'submitted'
    | 'queued_for_analysis'
    | 'analyzing'
    | 'ready_for_review'
    | 'accepted'
    | 'returned'
    | 'rejected';

export type MonitoringReportingPeriod = ReportingPeriod & {
    acceptsSubmissions: boolean;
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
    evidence: Evidence[];
    permissions: {
        uploadEvidence: boolean;
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
    department: DepartmentSummary;
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
