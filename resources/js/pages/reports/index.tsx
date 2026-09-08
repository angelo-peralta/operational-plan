import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Building2,
    CheckCircle2,
    ClipboardCheck,
    FileText,
    MessageSquareText,
    Printer,
    ShieldCheck,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard, home } from '@/routes';
import { official as officialPlan } from '@/routes/operational-plans';
import { index } from '@/routes/reports';
import type {
    ConsolidatedReport,
    ExecutiveRemarkReport,
    ReportOrganizationalUnit,
} from '@/types';

type Props = {
    report: ConsolidatedReport | null;
};

function MetricCard({
    label,
    value,
    detail,
    icon: Icon,
}: {
    label: string;
    value: number | string;
    detail: string;
    icon: typeof Building2;
}) {
    return (
        <Card>
            <CardHeader className="flex-row items-start justify-between gap-3 pb-2">
                <div className="space-y-1">
                    <CardDescription>{label}</CardDescription>
                    <CardTitle className="text-2xl">
                        {typeof value === 'number'
                            ? value.toLocaleString()
                            : value}
                    </CardTitle>
                </div>
                <span className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <Icon className="size-5" aria-hidden="true" />
                </span>
            </CardHeader>
            <CardContent>
                <p className="text-xs leading-5 text-muted-foreground">
                    {detail}
                </p>
            </CardContent>
        </Card>
    );
}

function ratingLabel(unit: ReportOrganizationalUnit): string {
    if (unit.ratings.average === null) {
        return 'No rating';
    }

    return `${unit.ratings.average.toLocaleString()} / 3 (${unit.ratings.ratedCount})`;
}

function RemarkRow({ remark }: { remark: ExecutiveRemarkReport }) {
    const keyResultArea = remark.keyResultArea
        ? [remark.keyResultArea.code, remark.keyResultArea.name]
              .filter(Boolean)
              .join(' - ')
        : 'Plan overview';

    return (
        <tr>
            <td>
                <span className="font-medium">
                    {remark.organizationalUnit.code ??
                        remark.organizationalUnit.name}
                </span>
            </td>
            <td>
                <span className="font-medium">{remark.author.name}</span>
                <br />
                <span className="text-muted-foreground">
                    {remark.author.authority}
                </span>
            </td>
            <td>
                <span className="font-medium">{keyResultArea}</span>
                {remark.planItem && (
                    <>
                        <br />
                        <span className="text-muted-foreground">
                            {remark.planItem.objective}
                        </span>
                    </>
                )}
            </td>
            <td>
                {remark.fieldLabel}
                <br />
                <span className="text-muted-foreground">
                    {remark.targetLabel}
                </span>
            </td>
            <td className="max-w-md whitespace-pre-wrap">{remark.content}</td>
            <td>
                <StatusBadge
                    status={remark.status}
                    label={remark.statusLabel}
                />
            </td>
        </tr>
    );
}

export default function ReportsIndex({ report }: Props) {
    const { currentTeam, selectedAcademicYear } = usePage().props;
    const [showRemarks, setShowRemarks] = useState(false);
    const teamSlug = currentTeam?.slug ?? null;
    const reportSelection = report?.selectedReportingPeriod
        ? report.selectedReportingPeriod.id.toString()
        : 'annual';
    const reportTitle = report?.selectedReportingPeriod
        ? `${report.selectedReportingPeriod.name} Monitoring Report`
        : 'Annual Operational Plan Report';

    return (
        <>
            <Head title="Reports" />

            <div className="reports-page flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
                <div className="reports-print-actions flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <Heading
                        title={
                            selectedAcademicYear
                                ? `Reports - ${selectedAcademicYear.name}`
                                : 'Reports'
                        }
                        description="Consolidated planning, accomplishment, evidence, remark, and manual evaluation summaries within your organizational scope."
                    />
                    {report && teamSlug && (
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="grid min-w-60 gap-2">
                                <label
                                    htmlFor="report-period"
                                    className="text-sm font-medium"
                                >
                                    Report period
                                </label>
                                <Select
                                    value={reportSelection}
                                    onValueChange={(value) =>
                                        router.get(
                                            index.url(teamSlug),
                                            value === 'annual'
                                                ? {}
                                                : {
                                                      reporting_period: value,
                                                  },
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
                                        )
                                    }
                                >
                                    <SelectTrigger id="report-period">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="annual">
                                            Annual report
                                        </SelectItem>
                                        {report.reportingPeriods.map(
                                            (period) => (
                                                <SelectItem
                                                    key={period.id}
                                                    value={period.id.toString()}
                                                >
                                                    {period.name}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    setShowRemarks((shown) => !shown)
                                }
                            >
                                <MessageSquareText />
                                {showRemarks ? 'Hide' : 'Include'} remarks
                            </Button>
                            <Button
                                type="button"
                                onClick={() => window.print()}
                            >
                                <Printer />
                                Print / Save as PDF
                            </Button>
                        </div>
                    )}
                </div>

                {!report ? (
                    <Card className="border-dashed">
                        <CardContent className="py-12 text-center text-muted-foreground">
                            Select an Academic Year to generate a report.
                        </CardContent>
                    </Card>
                ) : (
                    <article className="report-sheet grid gap-6">
                        <header className="rounded-xl border bg-card p-5 text-center">
                            <p className="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                                {report.academicYear.name}
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                {reportTitle}
                            </h1>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Scope contains{' '}
                                {report.summary.organizationalUnits.active.toLocaleString()}{' '}
                                active organizational units.
                            </p>
                        </header>

                        <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <MetricCard
                                label="Plan coverage"
                                value={`${report.summary.organizationalUnits.withPlan}/${report.summary.organizationalUnits.active}`}
                                detail={`${report.summary.organizationalUnits.withoutPlan} active units have no selected-year plan.`}
                                icon={Building2}
                            />
                            <MetricCard
                                label="Awaiting review"
                                value={
                                    report.summary.accomplishments
                                        .awaitingReview
                                }
                                detail="Current accomplishment submissions awaiting an executive decision."
                                icon={ClipboardCheck}
                            />
                            <MetricCard
                                label="Pending evidence"
                                value={report.summary.pendingEvidence}
                                detail="Plan Item-period obligations without an uploaded evidence file."
                                icon={FileText}
                            />
                            <MetricCard
                                label="Effective rating"
                                value={
                                    report.summary.ratings.average === null
                                        ? 'No rating'
                                        : `${report.summary.ratings.average.toLocaleString()} / 3`
                                }
                                detail={`${report.summary.ratings.ratedCount} accepted current submissions with an effective manual rating.`}
                                icon={ShieldCheck}
                            />
                        </section>

                        <section className="grid gap-4 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Plan workflow</CardTitle>
                                    <CardDescription>
                                        Selected-year plan states within scope
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                                    {Object.entries(report.summary.plans)
                                        .filter(([key]) => key !== 'total')
                                        .map(([status, count]) => (
                                            <div
                                                key={status}
                                                className="rounded-lg border bg-muted/20 p-3"
                                            >
                                                <p className="text-xl font-semibold tabular-nums">
                                                    {count.toLocaleString()}
                                                </p>
                                                <p className="mt-1 text-muted-foreground capitalize">
                                                    {status}
                                                </p>
                                            </div>
                                        ))}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Review completion</CardTitle>
                                    <CardDescription>
                                        Current submissions and preserved
                                        evaluation history
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                                    <div className="rounded-lg border bg-muted/20 p-3">
                                        <p className="text-xl font-semibold">
                                            {report.summary.evaluations
                                                .completionRate === null
                                                ? 'N/A'
                                                : `${report.summary.evaluations.completionRate.toLocaleString()}%`}
                                        </p>
                                        <p className="mt-1 text-muted-foreground">
                                            Completion
                                        </p>
                                    </div>
                                    <div className="rounded-lg border bg-muted/20 p-3">
                                        <p className="text-xl font-semibold">
                                            {report.summary.evaluations.evaluated.toLocaleString()}
                                        </p>
                                        <p className="mt-1 text-muted-foreground">
                                            Evaluated
                                        </p>
                                    </div>
                                    <div className="rounded-lg border bg-muted/20 p-3">
                                        <p className="text-xl font-semibold">
                                            {report.summary.evaluations.historyCount.toLocaleString()}
                                        </p>
                                        <p className="mt-1 text-muted-foreground">
                                            History entries
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </section>

                        <section className="overflow-hidden rounded-xl border bg-card">
                            <div className="border-b p-5">
                                <h2 className="text-lg font-semibold">
                                    Organizational unit summary
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Each row is isolated to the selected
                                    Academic Year and report period.
                                </p>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="report-table w-full min-w-[1150px] text-left text-sm">
                                    <thead>
                                        <tr>
                                            <th>Organizational unit</th>
                                            <th>Plan</th>
                                            <th>KRA / Items</th>
                                            <th>Awaiting / Returned</th>
                                            <th>Accepted</th>
                                            <th>Pending evidence</th>
                                            <th>Open remarks</th>
                                            <th>Evaluation</th>
                                            <th>Effective rating</th>
                                            <th aria-label="Official plan" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.organizationalUnits.map(
                                            (unit) => (
                                                <tr key={unit.id}>
                                                    <td>
                                                        <span className="font-medium">
                                                            {unit.name}
                                                        </span>
                                                        {unit.code && (
                                                            <>
                                                                <br />
                                                                <span className="text-muted-foreground">
                                                                    {unit.code}
                                                                </span>
                                                            </>
                                                        )}
                                                        {!unit.isActive && (
                                                            <Badge
                                                                variant="outline"
                                                                className="mt-1"
                                                            >
                                                                Inactive
                                                            </Badge>
                                                        )}
                                                    </td>
                                                    <td>
                                                        {unit.plan ? (
                                                            <StatusBadge
                                                                status={
                                                                    unit.plan
                                                                        .status
                                                                }
                                                                label={
                                                                    unit.plan
                                                                        .statusLabel
                                                                }
                                                            />
                                                        ) : (
                                                            'No plan'
                                                        )}
                                                    </td>
                                                    <td>
                                                        {unit.plan
                                                            ? `${unit.plan.keyResultAreaCount} / ${unit.plan.planItemCount}`
                                                            : '0 / 0'}
                                                    </td>
                                                    <td>
                                                        {unit.accomplishments.awaitingReview.toLocaleString()}{' '}
                                                        /{' '}
                                                        {unit.accomplishments.returned.toLocaleString()}
                                                    </td>
                                                    <td>
                                                        {unit.accomplishments.accepted.toLocaleString()}
                                                    </td>
                                                    <td>
                                                        {unit.pendingEvidence.toLocaleString()}
                                                    </td>
                                                    <td>
                                                        {unit.remarks.open.toLocaleString()}
                                                    </td>
                                                    <td>
                                                        {unit.evaluations.evaluated.toLocaleString()}
                                                        /
                                                        {unit.evaluations.currentSubmissions.toLocaleString()}
                                                    </td>
                                                    <td>{ratingLabel(unit)}</td>
                                                    <td>
                                                        {unit.plan &&
                                                            teamSlug && (
                                                                <Button
                                                                    asChild
                                                                    size="sm"
                                                                    variant="outline"
                                                                    className="report-row-action"
                                                                >
                                                                    <Link
                                                                        href={officialPlan(
                                                                            [
                                                                                teamSlug,
                                                                                unit
                                                                                    .plan
                                                                                    .id,
                                                                            ],
                                                                        )}
                                                                    >
                                                                        Official
                                                                    </Link>
                                                                </Button>
                                                            )}
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        {showRemarks && (
                            <section className="executive-remarks-report overflow-hidden rounded-xl border bg-card">
                                <div className="border-b p-5">
                                    <div className="flex items-center gap-2">
                                        <CheckCircle2 className="size-5 text-primary" />
                                        <h2 className="text-lg font-semibold">
                                            Executive Remarks Summary
                                        </h2>
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Field-level remarks with author,
                                        organizational target, and current
                                        resolution status.
                                    </p>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="report-table w-full min-w-[1100px] text-left text-sm">
                                        <thead>
                                            <tr>
                                                <th>Unit</th>
                                                <th>Author</th>
                                                <th>KRA / Plan Item</th>
                                                <th>Field / Target</th>
                                                <th>Remark</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {report.remarks.map((remark) => (
                                                <RemarkRow
                                                    key={remark.id}
                                                    remark={remark}
                                                />
                                            ))}
                                            {report.remarks.length === 0 && (
                                                <tr>
                                                    <td
                                                        colSpan={6}
                                                        className="text-center text-muted-foreground"
                                                    >
                                                        No executive remarks in
                                                        this report scope.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        )}
                    </article>
                )}
            </div>
        </>
    );
}

ReportsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam
                ? dashboard(props.currentTeam.slug)
                : home(),
        },
        {
            title: 'Reports',
            href: props.currentTeam ? index(props.currentTeam.slug) : home(),
        },
    ],
});
