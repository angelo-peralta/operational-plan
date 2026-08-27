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
