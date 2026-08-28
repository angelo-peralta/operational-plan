export type UserRole = 'super_admin' | 'reviewer' | 'department_user';

export type AcademicYearStatus = 'draft' | 'open' | 'closed' | 'archived';

export type ReportingPeriodStatus = 'draft' | 'open' | 'closed';

export type SelectOption<TValue extends string = string> = {
    value: TValue;
    label: string;
};

export type DepartmentSummary = {
    id: number;
    name: string;
    code: string | null;
};

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
    department: DepartmentSummary | null;
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
    departmentId: number;
};

export type DepartmentAccountableUser = {
    id: number;
    name: string;
    department_id: number;
};

export type CoAccountableDepartment = DepartmentSummary & {
    isActive: boolean;
};

export type OperationalPlanSummary = {
    id: number;
    academicYear: AcademicYearOption;
    department: DepartmentSummary;
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
    coAccountableDepartments: DepartmentSummary[];
    sortOrder: number;
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
    remarks: string | null;
    actor: Pick<AdministrationUser, 'id' | 'name'>;
    createdAt: string;
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
    reorderKeyResultAreas: boolean;
    viewOfficial: boolean;
};
