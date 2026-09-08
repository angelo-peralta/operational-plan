import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CalendarRange,
    CircleAlert,
    ClipboardCheck,
    ClipboardList,
    Clock3,
    FileText,
    History,
    ListChecks,
    MessageSquareText,
    ShieldCheck,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, home } from '@/routes';
import { index as evaluationsIndex } from '@/routes/evaluations';
import { index as monitoringIndex } from '@/routes/monitoring';
import {
    index as operationalPlansIndex,
    show as operationalPlanShow,
} from '@/routes/operational-plans';
import type {
    DashboardPageProps,
    DashboardRating,
    DashboardRatingSummary,
    DashboardReportingPeriodSummary,
    OrganizationalUnitDashboardSummary,
    OversightDashboardSummary,
} from '@/types';

type MetricTone = 'default' | 'positive' | 'warning';

const metricIconStyles: Record<MetricTone, string> = {
    default: 'bg-primary/10 text-primary',
    positive:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
    warning:
        'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
};

const ratingBarStyles: Record<DashboardRating, string> = {
    3: 'bg-emerald-500',
    2: 'bg-blue-500',
    1: 'bg-amber-500',
    0: 'bg-rose-500',
};

function MetricCard({
    title,
    value,
    description,
    icon: Icon,
    tone = 'default',
}: {
    title: string;
    value: number | string;
    description: string;
    icon: LucideIcon;
    tone?: MetricTone;
}) {
    return (
        <Card className="min-w-0">
            <CardHeader className="flex-row items-start justify-between gap-3 pb-2">
                <div className="min-w-0 space-y-1">
                    <CardDescription>{title}</CardDescription>
                    <CardTitle className="text-2xl tracking-tight">
                        {typeof value === 'number'
                            ? value.toLocaleString()
                            : value}
                    </CardTitle>
                </div>
                <span
                    className={`flex size-10 shrink-0 items-center justify-center rounded-xl ${metricIconStyles[tone]}`}
                >
                    <Icon className="size-5" aria-hidden="true" />
                </span>
            </CardHeader>
            <CardContent>
                <p className="text-xs leading-5 text-muted-foreground">
                    {description}
                </p>
            </CardContent>
        </Card>
    );
}

function CountTile({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-lg border bg-muted/20 p-3">
            <p className="text-xl font-semibold tracking-tight tabular-nums">
                {value.toLocaleString()}
            </p>
            <p className="mt-1 text-xs leading-5 text-muted-foreground">
                {label}
            </p>
        </div>
    );
}

function PipelineCount({
    label,
    status,
    value,
}: {
    label: string;
    status: string;
    value: number;
}) {
    return (
        <div className="flex items-center justify-between gap-3 rounded-lg border bg-muted/20 p-3">
            <StatusBadge status={status} label={label} />
            <span className="text-lg font-semibold tabular-nums">
                {value.toLocaleString()}
            </span>
        </div>
    );
}

function RatingSummaryCard({
    ratings,
    title = 'Official evaluation summary',
}: {
    ratings: DashboardRatingSummary;
    title?: string;
}) {
    const average =
        ratings.average === null
            ? 'No ratings yet'
            : `${ratings.average.toLocaleString(undefined, {
                  maximumFractionDigits: 2,
              })} / 3`;

    return (
        <Card className="min-w-0">
            <CardHeader>
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-1.5">
                        <CardTitle>{title}</CardTitle>
                        <CardDescription>
                            Latest rated submission per accomplishment
                        </CardDescription>
                    </div>
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <ShieldCheck className="size-5" aria-hidden="true" />
                    </span>
                </div>
            </CardHeader>
            <CardContent className="grid gap-5">
                <div className="flex flex-wrap items-end justify-between gap-3 rounded-xl border bg-muted/20 p-4">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Average rating
                        </p>
                        <p className="mt-1 text-2xl font-semibold tracking-tight">
                            {average}
                        </p>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {ratings.ratedCount.toLocaleString()}{' '}
                        {ratings.ratedCount === 1
                            ? 'rated accomplishment'
                            : 'rated accomplishments'}
                    </p>
                </div>

                <div className="grid gap-3">
                    {ratings.distribution.map((item) => {
                        const percentage =
                            ratings.ratedCount === 0
                                ? 0
                                : (item.count / ratings.ratedCount) * 100;

                        return (
                            <div key={item.rating} className="grid gap-2">
                                <div className="flex items-center justify-between gap-3 text-sm">
                                    <span className="min-w-0 truncate">
                                        <span className="font-semibold">
                                            {item.rating}
                                        </span>{' '}
                                        <span className="text-muted-foreground">
                                            {item.label}
                                        </span>
                                    </span>
                                    <span className="shrink-0 font-medium tabular-nums">
                                        {item.count.toLocaleString()}
                                    </span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                    <div
                                        className={`h-full rounded-full ${ratingBarStyles[item.rating]}`}
                                        style={{ width: `${percentage}%` }}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="flex gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200">
                    <ShieldCheck
                        className="mt-0.5 size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <p>
                        Official decisions and ratings are entered manually by
                        authorized reviewers. No automated score or decision is
                        used.
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}

function RemarkSummaryCard({
    remarks,
}: {
    remarks: OrganizationalUnitDashboardSummary['remarks'];
}) {
    return (
        <Card>
            <CardHeader>
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-1.5">
                        <CardTitle>Executive remarks</CardTitle>
                        <CardDescription>
                            Field-level review threads in this Academic Year
                        </CardDescription>
                    </div>
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <MessageSquareText
                            className="size-5"
                            aria-hidden="true"
                        />
                    </span>
                </div>
            </CardHeader>
            <CardContent className="grid grid-cols-3 gap-3">
                <CountTile label="Open" value={remarks.open} />
                <CountTile label="Addressed" value={remarks.addressed} />
                <CountTile label="Resolved" value={remarks.resolved} />
            </CardContent>
        </Card>
    );
}

function EvaluationProgressCard({
    evaluations,
}: {
    evaluations: OrganizationalUnitDashboardSummary['evaluations'];
}) {
    const completion =
        evaluations.completionRate === null
            ? 'No submissions'
            : `${evaluations.completionRate.toLocaleString()}%`;

    return (
        <Card>
            <CardHeader>
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-1.5">
                        <CardTitle>Evaluation completion</CardTitle>
                        <CardDescription>
                            Current submissions and preserved review history
                        </CardDescription>
                    </div>
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <History className="size-5" aria-hidden="true" />
                    </span>
                </div>
            </CardHeader>
            <CardContent className="grid gap-4">
                <div className="flex items-end justify-between gap-3 rounded-lg border bg-muted/20 p-3">
                    <div>
                        <p className="text-2xl font-semibold tracking-tight">
                            {completion}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {evaluations.evaluated.toLocaleString()} of{' '}
                            {evaluations.currentSubmissions.toLocaleString()}{' '}
                            current submissions evaluated
                        </p>
                    </div>
                    <p className="text-sm font-medium tabular-nums">
                        {evaluations.historyCount.toLocaleString()} history
                    </p>
                </div>
                <CountTile
                    label="Awaiting an executive review"
                    value={evaluations.awaitingReview}
                />
            </CardContent>
        </Card>
    );
}

function ReportingPeriodCard({
    period,
    planItemCount,
    teamSlug,
}: {
    period: DashboardReportingPeriodSummary;
    planItemCount: number;
    teamSlug: string | null;
}) {
    return (
        <Card className="min-w-0 overflow-hidden">
            <CardHeader className="flex-row items-start justify-between gap-4 border-b bg-muted/15">
                <div className="min-w-0 space-y-1.5">
                    <CardTitle className="leading-6">{period.name}</CardTitle>
                    <CardDescription>
                        Semester accomplishment progress
                    </CardDescription>
                </div>
                <StatusBadge status={period.status} />
            </CardHeader>
            <CardContent className="grid gap-4">
                <div>
                    <p className="text-3xl font-semibold tracking-tight tabular-nums">
                        {period.submitted.toLocaleString()}
                        <span className="ml-1 text-base font-normal text-muted-foreground">
                            / {planItemCount.toLocaleString()}
                        </span>
                    </p>
                    <p className="mt-1 text-xs leading-5 text-muted-foreground">
                        Plan Items with at least one preserved submission
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <CountTile
                        label="Awaiting review"
                        value={period.awaitingReview}
                    />
                    <CountTile label="Returned" value={period.returned} />
                    <CountTile label="Accepted" value={period.accepted} />
                    <CountTile label="Rejected" value={period.rejected} />
                </div>
            </CardContent>
            {teamSlug && (
                <CardFooter className="border-t pt-4">
                    <Button asChild variant="outline" className="w-full">
                        <Link
                            href={monitoringIndex(teamSlug, {
                                query: { reporting_period: period.id },
                            })}
                            prefetch
                        >
                            Open {period.name} monitoring
                            <ArrowRight />
                        </Link>
                    </Button>
                </CardFooter>
            )}
        </Card>
    );
}

function OrganizationalUnitDashboard({
    summary,
    teamSlug,
}: {
    summary: OrganizationalUnitDashboardSummary;
    teamSlug: string | null;
}) {
    if (!summary.organizationalUnit) {
        return (
            <Card className="border-dashed">
                <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                    <Building2
                        className="size-10 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <div className="space-y-1">
                        <p className="font-medium">
                            Organizational unit assignment required
                        </p>
                        <p className="max-w-lg text-sm leading-6 text-muted-foreground">
                            Ask an administrator to assign your account to a
                            organizational unit before working with Operational
                            Plans.
                        </p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (!summary.plan) {
        return (
            <Card className="border-dashed">
                <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
                    <ClipboardList
                        className="size-10 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <div className="space-y-1">
                        <p className="font-medium">
                            No Operational Plan for{' '}
                            {summary.organizationalUnit.name}
                        </p>
                        <p className="max-w-lg text-sm leading-6 text-muted-foreground">
                            Create or review your organizational unit&apos;s
                            plan before semester monitoring begins.
                        </p>
                    </div>
                    {teamSlug && (
                        <Button asChild>
                            <Link
                                href={operationalPlansIndex(teamSlug)}
                                prefetch
                            >
                                View Operational Plans
                                <ArrowRight />
                            </Link>
                        </Button>
                    )}
                </CardContent>
            </Card>
        );
    }

    const plan = summary.plan;

    return (
        <div className="grid gap-6">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div className="min-w-0 space-y-1">
                    <h2 className="text-xl font-semibold tracking-tight">
                        {summary.organizationalUnit.name}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Organizational unit planning and semester monitoring
                        overview
                    </p>
                </div>
                {teamSlug && (
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={monitoringIndex(teamSlug)} prefetch>
                                Semester monitoring
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={operationalPlanShow([teamSlug, plan.id])}
                                prefetch
                            >
                                Open plan
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                )}
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <MetricCard
                    title="Plan status"
                    value={plan.statusLabel}
                    description="Current official planning workflow state"
                    icon={FileText}
                    tone={plan.status === 'approved' ? 'positive' : 'default'}
                />
                <MetricCard
                    title="Key Result Areas"
                    value={plan.keyResultAreaCount}
                    description="KRAs defined in this Academic Year"
                    icon={ListChecks}
                />
                <MetricCard
                    title="Plan Items"
                    value={plan.planItemCount}
                    description="Operational Plan entries to be monitored"
                    icon={ClipboardList}
                />
                <MetricCard
                    title="Pending evidence"
                    value={summary.pendingEvidence}
                    description="Monitored Plan Item-periods with no uploaded evidence"
                    icon={CircleAlert}
                    tone={summary.pendingEvidence > 0 ? 'warning' : 'positive'}
                />
            </div>

            <section className="grid gap-4" aria-labelledby="semester-progress">
                <div>
                    <h2
                        id="semester-progress"
                        className="text-lg font-semibold tracking-tight"
                    >
                        Semester progress
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Submission and review status for each reporting period
                    </p>
                </div>

                {summary.reportingPeriods.length > 0 ? (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {summary.reportingPeriods.map((period) => (
                            <ReportingPeriodCard
                                key={period.id}
                                period={period}
                                planItemCount={plan.planItemCount}
                                teamSlug={teamSlug}
                            />
                        ))}
                    </div>
                ) : (
                    <Card className="border-dashed">
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            This Academic Year has no reporting periods.
                        </CardContent>
                    </Card>
                )}
            </section>

            <div className="grid items-start gap-6 xl:grid-cols-2">
                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-4">
                            <div className="space-y-1.5">
                                <CardTitle>Review outcomes</CardTitle>
                                <CardDescription>
                                    Current status across all reporting periods
                                </CardDescription>
                            </div>
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <ClipboardCheck
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-3">
                        <CountTile
                            label="Awaiting review"
                            value={summary.accomplishments.awaitingReview}
                        />
                        <CountTile
                            label="Returned"
                            value={summary.accomplishments.returned}
                        />
                        <CountTile
                            label="Accepted"
                            value={summary.accomplishments.accepted}
                        />
                        <CountTile
                            label="Rejected"
                            value={summary.accomplishments.rejected}
                        />
                    </CardContent>
                </Card>

                <RatingSummaryCard ratings={summary.ratings} />
                <RemarkSummaryCard remarks={summary.remarks} />
                <EvaluationProgressCard evaluations={summary.evaluations} />
            </div>
        </div>
    );
}

function OversightDashboard({
    summary,
    teamSlug,
}: {
    summary: OversightDashboardSummary;
    teamSlug: string | null;
}) {
    return (
        <div className="grid gap-6">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div className="space-y-1">
                    <h2 className="text-xl font-semibold tracking-tight">
                        Institution overview
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Organizational unit plan coverage and selected-year
                        review outcomes
                    </p>
                </div>
                {teamSlug && (
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link
                                href={operationalPlansIndex(teamSlug)}
                                prefetch
                            >
                                View plans
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={evaluationsIndex(teamSlug)} prefetch>
                                Open Evidence Review
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                )}
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <MetricCard
                    title="Active organizational units"
                    value={summary.organizationalUnits.active}
                    description="Units included in your organizational scope"
                    icon={Users}
                />
                <MetricCard
                    title="Units with plans"
                    value={summary.organizationalUnits.withPlan}
                    description="Active units with a selected-year plan"
                    icon={ClipboardList}
                    tone="positive"
                />
                <MetricCard
                    title="Units without plans"
                    value={summary.organizationalUnits.withoutPlan}
                    description="Active units still missing a plan"
                    icon={CircleAlert}
                    tone={
                        summary.organizationalUnits.withoutPlan > 0
                            ? 'warning'
                            : 'positive'
                    }
                />
                <MetricCard
                    title="Awaiting review"
                    value={summary.accomplishments.awaitingReview}
                    description="Accomplishments ready for a human decision"
                    icon={Clock3}
                    tone={
                        summary.accomplishments.awaitingReview > 0
                            ? 'warning'
                            : 'positive'
                    }
                />
            </div>

            <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(22rem,0.9fr)]">
                <div className="grid gap-6">
                    <Card>
                        <CardHeader>
                            <div className="flex items-end justify-between gap-4">
                                <div className="space-y-1.5">
                                    <CardTitle>Plan pipeline</CardTitle>
                                    <CardDescription>
                                        All selected-year plans, including
                                        inactive-unit history
                                    </CardDescription>
                                </div>
                                <p className="text-3xl font-semibold tracking-tight tabular-nums">
                                    {summary.plans.total.toLocaleString()}
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <PipelineCount
                                label="Draft"
                                status="draft"
                                value={summary.plans.draft}
                            />
                            <PipelineCount
                                label="Submitted"
                                status="submitted"
                                value={summary.plans.submitted}
                            />
                            <PipelineCount
                                label="Approved"
                                status="approved"
                                value={summary.plans.approved}
                            />
                            <PipelineCount
                                label="Returned"
                                status="returned"
                                value={summary.plans.returned}
                            />
                            <PipelineCount
                                label="Closed"
                                status="closed"
                                value={summary.plans.closed}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-end justify-between gap-4">
                                <div className="space-y-1.5">
                                    <CardTitle>
                                        Accomplishment review pipeline
                                    </CardTitle>
                                    <CardDescription>
                                        Current review outcomes across all
                                        selected-year organizational units
                                    </CardDescription>
                                </div>
                                <p className="text-3xl font-semibold tracking-tight tabular-nums">
                                    {summary.accomplishments.total.toLocaleString()}
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2">
                            <PipelineCount
                                label="Awaiting review"
                                status="under_review"
                                value={summary.accomplishments.awaitingReview}
                            />
                            <PipelineCount
                                label="Returned"
                                status="returned"
                                value={summary.accomplishments.returned}
                            />
                            <PipelineCount
                                label="Accepted"
                                status="accepted"
                                value={summary.accomplishments.accepted}
                            />
                            <PipelineCount
                                label="Rejected"
                                status="rejected"
                                value={summary.accomplishments.rejected}
                            />
                        </CardContent>
                    </Card>
                </div>

                <RatingSummaryCard
                    ratings={summary.ratings}
                    title="Institutional rating summary"
                />
                <RemarkSummaryCard remarks={summary.remarks} />
                <EvaluationProgressCard evaluations={summary.evaluations} />
            </div>
        </div>
    );
}

export default function Dashboard({
    pendingInvitations = [],
    summary,
}: DashboardPageProps) {
    const { auth, academicYears, currentTeam, selectedAcademicYear } =
        usePage().props;
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );
    const teamSlug = currentTeam?.slug ?? null;
    const isUnitDashboard =
        summary?.kind === 'unit' || summary?.kind === 'units';

    return (
        <>
            <Head
                title={
                    selectedAcademicYear
                        ? `Dashboard — ${selectedAcademicYear.name}`
                        : 'Dashboard'
                }
            />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="relative overflow-hidden rounded-2xl border bg-card p-6 shadow-sm md:p-8">
                    <div className="absolute top-0 right-0 size-48 translate-x-12 -translate-y-16 rounded-full bg-primary/5" />
                    <div className="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div className="min-w-0 space-y-3">
                            <p className="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                                Active planning context
                            </p>
                            <div className="flex flex-wrap items-center gap-3">
                                <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">
                                    {selectedAcademicYear
                                        ? `Dashboard — ${selectedAcademicYear.name}`
                                        : 'Dashboard'}
                                </h1>
                                {selectedAcademicYear && (
                                    <StatusBadge
                                        status={selectedAcademicYear.status}
                                    />
                                )}
                            </div>
                            <p className="max-w-2xl text-sm leading-6 text-muted-foreground md:text-base">
                                {selectedAcademicYear
                                    ? isUnitDashboard
                                        ? `Your assigned units' planning, submissions, and official evaluations are scoped to ${selectedAcademicYear.name}.`
                                        : `Planning and manual review metrics within your organizational scope are scoped to ${selectedAcademicYear.name}.`
                                    : academicYears.length > 0
                                      ? 'Choose an Academic Year from the header to load its dashboard metrics.'
                                      : 'An administrator must create an Academic Year before planning can begin.'}
                            </p>
                            <div className="flex flex-wrap gap-2">
                                <Badge
                                    variant="outline"
                                    className="gap-1.5 bg-background/80"
                                >
                                    <ShieldCheck
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                    {auth.user.roleLabel ?? 'Unassigned role'}
                                </Badge>
                                {auth.user.scopeLabel && (
                                    <Badge
                                        variant="outline"
                                        className="gap-1.5 bg-background/80"
                                    >
                                        <Building2
                                            className="size-3.5"
                                            aria-hidden="true"
                                        />
                                        {auth.user.scopeLabel}
                                    </Badge>
                                )}
                            </div>
                        </div>
                        <div className="flex size-16 shrink-0 items-center justify-center rounded-2xl border bg-background shadow-sm">
                            <CalendarRange
                                className="size-8 text-primary"
                                aria-hidden="true"
                            />
                        </div>
                    </div>
                </section>

                {!selectedAcademicYear ? (
                    <Card className="border-dashed">
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <CalendarRange
                                className="size-10 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <div className="space-y-1">
                                <p className="font-medium">
                                    No Academic Year selected
                                </p>
                                <p className="max-w-lg text-sm leading-6 text-muted-foreground">
                                    Choose an Academic Year from the header to
                                    view role-appropriate operational metrics.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : !summary ? (
                    <Card className="border-dashed">
                        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                            <CircleAlert
                                className="size-10 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <div className="space-y-1">
                                <p className="font-medium">
                                    Dashboard metrics unavailable
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Refresh the page or choose the Academic Year
                                    again.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : summary.kind === 'units' ? (
                    <div className="grid gap-6">
                        {summary.units.map((unit) => (
                            <OrganizationalUnitDashboard
                                key={unit.organizationalUnit?.id}
                                summary={unit}
                                teamSlug={teamSlug}
                            />
                        ))}
                    </div>
                ) : summary.kind === 'unit' ? (
                    <OrganizationalUnitDashboard
                        summary={summary}
                        teamSlug={teamSlug}
                    />
                ) : (
                    <OversightDashboard summary={summary} teamSlug={teamSlug} />
                )}
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam
                ? dashboard(props.currentTeam.slug)
                : home(),
        },
    ],
});
