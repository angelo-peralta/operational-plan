export type UserRole = 'system_admin' | 'executive' | 'office_head' | 'staff';

export type AcademicYearStatus = 'draft' | 'open' | 'closed' | 'archived';

export type ReportingPeriodStatus = 'draft' | 'open' | 'closed';

export type SelectOption<TValue extends string = string> = {
    value: TValue;
    label: string;
};

export type OperationalRoleOption = {
    id: number;
    label: string;
    accessLevel: UserRole;
    requiresDepartment: boolean;
    isSystem: boolean;
};

export type OperationalRole = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    accessLevel: UserRole;
    accessLevelLabel: string;
    requiresDepartment: boolean;
    isSystem: boolean;
    userCount: number;
};

export type OrganizationalUnitSummary = {
    id: number;
    name: string;
    code: string | null;
};

export type DepartmentSummary = OrganizationalUnitSummary;

export type AcademicYearOption = {
    id: number;
    name: string;
    status: AcademicYearStatus;
    isCurrent: boolean;
    startYear: number;
    endYear: number;
};

export type ReportingPeriod = {
    id: number;
    name: string;
    code: string;
    sequence: number;
    startsOn: string | null;
    endsOn: string | null;
    status: ReportingPeriodStatus;
};

export type OperationalPlanReportingPeriod = ReportingPeriod & {
    acceptsSubmissions: boolean;
};

export type AcademicYear = AcademicYearOption & {
    startsOn: string | null;
    endsOn: string | null;
    reportingPeriods: ReportingPeriod[];
};

export type Department = DepartmentSummary & {
    description: string | null;
    isActive: boolean;
    userCount: number;
};

export type AdministrationUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole | null;
    roleLabel: string | null;
    emailVerifiedAt: string | null;
};

export type OperationalPlanStatus =
    'draft' | 'submitted' | 'approved' | 'returned' | 'closed';

export type TargetOperator =
    | 'equals'
    | 'at_least'
    | 'at_most'
    | 'percentage_at_least'
    | 'percentage_at_most'
    | 'zero_tolerance'
    | 'qualitative';

export type AccountableUserOption = {
    id: number;
    name: string;
    organizationalUnitId: number;
};

export type CoAccountableOrganizationalUnit = OrganizationalUnitSummary & {
    isActive: boolean;
};

export type OperationalPlanSummary = {
    id: number;
    academicYear: AcademicYearOption;
    organizationalUnit: OrganizationalUnitSummary;
    accountableUser: Pick<AdministrationUser, 'id' | 'name'> | null;
    accountableName: string | null;
    accountablePosition: string | null;
    goal: string;
    status: OperationalPlanStatus;
    statusLabel: string;
    submittedAt: string | null;
    approvedAt: string | null;
    returnedAt: string | null;
    closedAt: string | null;
    keyResultAreaCount: number;
    planItemCount: number;
    latestReturnRemarks: string | null;
};

export type PlanItem = {
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
    manualCoAccountableUnits: string[];
    coAccountableOrganizationalUnits: OrganizationalUnitSummary[];
    sortOrder: number;
    accomplishment: {
        id: number;
        reportedValue: string | null;
        accomplishmentText: string | null;
        percentageAccomplished: string | null;
        status:
            | 'draft'
            | 'submitted'
            | 'under_review'
            | 'accepted'
            | 'returned'
            | 'rejected';
        statusLabel: string;
        submittedAt: string | null;
        resubmittedAt: string | null;
        updatedAt: string | null;
        createdBy: { id: number; name: string } | null;
        updatedBy: { id: number; name: string } | null;
        evidence: never[];
        remarks: OperationalPlanRemark[];
        submissionBlocker: string | null;
        submissionHistory: never[];
        evaluationHistory: never[];
        permissions: {
            uploadEvidence: boolean;
            submitAccomplishment: boolean;
            createRemark: boolean;
        };
    } | null;
    permissions: {
        createAccomplishment: boolean;
        updateAccomplishment: boolean;
    };
};

export type KeyResultArea = {
    id: number;
    code: string | null;
    name: string;
    description: string | null;
    sortOrder: number;
    planItems: PlanItem[];
};

export type OperationalPlanStatusHistory = {
    id: number;
    fromStatus: OperationalPlanStatus | null;
    toStatus: OperationalPlanStatus;
    toStatusLabel: string;
    action: string;
    actorRole: UserRole | null;
    actorOrganizationalUnitIds: number[];
    remarks: string | null;
    actor: Pick<AdministrationUser, 'id' | 'name'>;
    createdAt: string;
};

export type OperationalPlanRemark = {
    id: number;
    targetType:
        | 'operational_plan'
        | 'key_result_area'
        | 'plan_item'
        | 'accomplishment'
        | 'evidence'
        | 'evaluation';
    targetId: number;
    targetLabel: string;
    anchor: string;
    fieldKey: string | null;
    period: { id: number; name: string } | null;
    category: string;
    content: string;
    status: 'open' | 'addressed' | 'resolved';
    statusLabel: string;
    author: { id: number; name: string; authority: string };
    createdAt: string;
    addressedAt: string | null;
    resolvedAt: string | null;
    canAddress: boolean;
    canResolve: boolean;
};

export type OperationalPlanReview = {
    counts: {
        total: number;
        open: number;
        addressed: number;
        resolved: number;
        president: number;
        vicePresident: number;
    };
    groups: { label: string; anchor: string; count: number }[];
    remarks: OperationalPlanRemark[];
};

export type OperationalPlan = OperationalPlanSummary & {
    keyResultAreas: KeyResultArea[];
    statusHistory: OperationalPlanStatusHistory[];
};

export type OperationalPlanPermissions = {
    updatePlan: boolean;
    submitPlan: boolean;
    approvePlan: boolean;
    returnPlan: boolean;
    closePlan: boolean;
    reopenPlan: boolean;
    createKeyResultArea: boolean;
    updateKeyResultArea: boolean;
    deleteKeyResultArea: boolean;
    reorderKeyResultAreas: boolean;
    viewOfficial: boolean;
    createRemark: boolean;
};
