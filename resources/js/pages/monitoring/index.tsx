import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ClipboardCheck,
    FileText,
    History,
    PencilLine,
    Plus,
    Send,
} from 'lucide-react';
import { useState } from 'react';
import {
    EvaluationHistory,
    LatestReviewFeedback,
} from '@/components/evaluations/evaluation-history';
import Heading from '@/components/heading';
import { AccomplishmentDialog } from '@/components/monitoring/accomplishment-dialog';
import { AccomplishmentSubmissionDialog } from '@/components/monitoring/accomplishment-submission-dialog';
import { EvidenceList } from '@/components/monitoring/evidence-list';
import { MonitoringRemarks } from '@/components/monitoring/monitoring-remarks';
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
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard, home } from '@/routes';
import { index } from '@/routes/monitoring';
import type {
    Accomplishment,
    AccomplishmentSubmissionHistory,
    MonitoringPageProps,
    MonitoringPlanItem,
    MonitoringReportingPeriod,
} from '@/types';

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function formatStatus(
    status: AccomplishmentSubmissionHistory['fromStatus'],
): string {
    return status
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function AccomplishmentSubmissionWorkflow({
    teamSlug,
    reportingPeriod,
    planItem,
    accomplishment,
}: {
    teamSlug: string;
    reportingPeriod: MonitoringReportingPeriod;
    planItem: MonitoringPlanItem;
    accomplishment: Accomplishment;
}) {
    const [historyOpen, setHistoryOpen] = useState(false);
    const isResubmission = accomplishment.status === 'returned';
    const submitLabel = isResubmission
        ? 'Resubmit for review'
        : 'Submit for review';

    return (
        <div className="grid gap-3 border-t pt-3">
            {(accomplishment.submittedAt || accomplishment.resubmittedAt) && (
                <dl className="flex flex-wrap gap-x-6 gap-y-2 text-xs text-muted-foreground">
                    {accomplishment.submittedAt && (
                        <div className="grid gap-0.5">
                            <dt className="font-medium text-foreground">
                                First submitted
                            </dt>
                            <dd>
                                <time dateTime={accomplishment.submittedAt}>
                                    {formatDateTime(accomplishment.submittedAt)}
                                </time>
                            </dd>
                        </div>
                    )}
                    {accomplishment.resubmittedAt && (
                        <div className="grid gap-0.5">
                            <dt className="font-medium text-foreground">
                                Last resubmitted
                            </dt>
                            <dd>
                                <time dateTime={accomplishment.resubmittedAt}>
                                    {formatDateTime(
                                        accomplishment.resubmittedAt,
                                    )}
                                </time>
                            </dd>
                        </div>
                    )}
                </dl>
            )}

            {accomplishment.permissions.submitAccomplishment && (
                <div className="flex flex-col justify-between gap-3 rounded-lg border bg-background p-3 sm:flex-row sm:items-center">
                    <div className="grid gap-1">
                        <p className="text-sm font-medium">
                            {isResubmission
                                ? 'Ready to resubmit?'
                                : 'Ready for review?'}
                        </p>
                        <p
                            className={
                                accomplishment.submissionBlocker
                                    ? 'text-xs leading-5 text-amber-700 dark:text-amber-300'
                                    : 'text-xs leading-5 text-muted-foreground'
                            }
                        >
                            {accomplishment.submissionBlocker ??
                                `Submitting stores an immutable snapshot with ${accomplishment.evidence.length} evidence ${accomplishment.evidence.length === 1 ? 'file' : 'files'}.`}
                        </p>
                    </div>
                    {accomplishment.submissionBlocker ? (
                        <Button
                            type="button"
                            size="sm"
                            disabled
                            title={accomplishment.submissionBlocker}
                        >
                            <Send /> {submitLabel}
                        </Button>
                    ) : (
                        <AccomplishmentSubmissionDialog
                            teamSlug={teamSlug}
                            reportingPeriod={reportingPeriod}
                            planItem={planItem}
                            accomplishment={accomplishment}
                            trigger={
                                <Button type="button" size="sm">
                                    <Send /> {submitLabel}
                                </Button>
                            }
                        />
                    )}
                </div>
            )}

            {accomplishment.submissionHistory.length > 0 && (
                <Collapsible open={historyOpen} onOpenChange={setHistoryOpen}>
                    <CollapsibleTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            className="h-auto w-full justify-between px-0 py-1 hover:bg-transparent"
                        >
                            <span className="flex items-center gap-2">
                                <History />
                                Submission history (
                                {accomplishment.submissionHistory.length})
                            </span>
                            <ChevronDown
                                className={`transition-transform ${historyOpen ? 'rotate-180' : ''}`}
                            />
                        </Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent className="pt-3">
                        <p className="mb-4 text-xs leading-5 text-muted-foreground">
                            Each entry preserves the report and evidence that
                            were submitted in that revision.
                        </p>
                        <ol className="grid gap-4">
                            {accomplishment.submissionHistory.map(
                                (submission, index) => (
                                    <li
                                        key={submission.id}
                                        className="relative grid gap-1 border-l-2 pl-5"
                                    >
                                        <span className="absolute top-1.5 -left-[5px] size-2 rounded-full bg-primary" />
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-medium">
                                                Revision {submission.revision}
                                            </p>
                                            <StatusBadge
                                                status={submission.toStatus}
                                                label={submission.toStatusLabel}
                                            />
                                            {index === 0 && (
                                                <Badge variant="secondary">
                                                    Latest
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {formatStatus(
                                                submission.fromStatus,
                                            )}{' '}
                                            → {submission.toStatusLabel}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {submission.submitter.name} ·{' '}
                                            <time
                                                dateTime={
                                                    submission.submittedAt
                                                }
                                            >
                                                {formatDateTime(
                                                    submission.submittedAt,
                                                )}
                                            </time>{' '}
                                            · {submission.evidenceCount}{' '}
                                            evidence{' '}
                                            {submission.evidenceCount === 1
                                                ? 'file'
                                                : 'files'}
                                        </p>
                                    </li>
                                ),
                            )}
                        </ol>
                    </CollapsibleContent>
                </Collapsible>
            )}
        </div>
    );
}

function AccomplishmentSummary({
    teamSlug,
    operationalPlanId,
    reportingPeriod,
    planItem,
}: {
    teamSlug: string;
    operationalPlanId: number;
    reportingPeriod: MonitoringReportingPeriod;
    planItem: MonitoringPlanItem;
}) {
    const accomplishment = planItem.accomplishment;

    if (!accomplishment) {
        return (
            <p className="text-sm text-muted-foreground">
                No accomplishment has been reported for this period.
            </p>
        );
    }

    return (
        <div
            id={`accomplishment-${accomplishment.id}`}
            className="grid scroll-mt-6 gap-3 rounded-xl border bg-muted/20 p-4"
        >
            <div className="flex flex-wrap items-center gap-2">
                <StatusBadge
                    status={accomplishment.status}
                    label={accomplishment.statusLabel}
                />
                {accomplishment.percentageAccomplished !== null && (
                    <span className="text-sm font-medium">
                        {Number(
                            accomplishment.percentageAccomplished,
                        ).toLocaleString(undefined, {
                            maximumFractionDigits: 4,
                        })}
                        % accomplished
                    </span>
                )}
            </div>
            <LatestReviewFeedback
                accomplishmentStatus={accomplishment.status}
                history={accomplishment.evaluationHistory}
            />
            {accomplishment.reportedValue !== null && (
                <p className="text-sm">
                    <span className="font-medium">Reported value:</span>{' '}
                    {Number(accomplishment.reportedValue).toLocaleString()}
                    {planItem.targetUnit ? ` ${planItem.targetUnit}` : ''}
                </p>
            )}
            {accomplishment.accomplishmentText && (
                <p className="text-sm leading-6 whitespace-pre-wrap text-muted-foreground">
                    {accomplishment.accomplishmentText}
                </p>
            )}
            {(accomplishment.createdBy || accomplishment.updatedBy) && (
                <p className="text-xs text-muted-foreground">
                    {accomplishment.createdBy
                        ? `Reported by ${accomplishment.createdBy.name}`
                        : 'Original reporter unavailable'}
                    {accomplishment.updatedBy &&
                    accomplishment.updatedBy.id !== accomplishment.createdBy?.id
                        ? ` · Last updated by ${accomplishment.updatedBy.name}`
                        : ''}
                </p>
            )}
            <div className="grid gap-2 border-t pt-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-sm font-medium">
                        Uploaded evidence ({accomplishment.evidence.length})
                    </p>
                </div>
                <EvidenceList
                    teamSlug={teamSlug}
                    reportingPeriodId={reportingPeriod.id}
                    planItemId={planItem.id}
                    accomplishmentId={accomplishment.id}
                    evidence={accomplishment.evidence}
                />
            </div>
            <MonitoringRemarks
                key={accomplishment.id}
                teamSlug={teamSlug}
                operationalPlanId={operationalPlanId}
                reportingPeriodId={reportingPeriod.id}
                accomplishment={accomplishment}
            />
            <AccomplishmentSubmissionWorkflow
                teamSlug={teamSlug}
                reportingPeriod={reportingPeriod}
                planItem={planItem}
                accomplishment={accomplishment}
            />
            <EvaluationHistory history={accomplishment.evaluationHistory} />
        </div>
    );
}

function PlanItemCard({
    teamSlug,
    operationalPlanId,
    reportingPeriod,
    planItem,
}: {
    teamSlug: string;
    operationalPlanId: number;
    reportingPeriod: MonitoringReportingPeriod;
    planItem: MonitoringPlanItem;
}) {
    const canEdit =
        planItem.permissions.createAccomplishment ||
        planItem.permissions.updateAccomplishment;

    return (
        <Card>
            <CardHeader className="gap-3">
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div className="grid gap-1">
                        <CardTitle className="text-base">
                            {planItem.kpiTargetText}
                        </CardTitle>
                        <CardDescription>{planItem.objective}</CardDescription>
                    </div>
                    {canEdit && (
                        <AccomplishmentDialog
                            teamSlug={teamSlug}
                            reportingPeriod={reportingPeriod}
                            planItem={planItem}
                            trigger={
                                <Button size="sm" variant="outline">
                                    {planItem.accomplishment ? (
                                        <PencilLine />
                                    ) : (
                                        <Plus />
                                    )}
                                    {planItem.accomplishment
                                        ? planItem.accomplishment.status ===
                                          'returned'
                                            ? 'Edit returned report'
                                            : 'Edit draft'
                                        : 'Report accomplishment'}
                                </Button>
                            }
                        />
                    )}
                </div>
            </CardHeader>
            <CardContent className="grid gap-4">
                {planItem.strategy && (
                    <div className="text-sm">
                        <p className="font-medium">Strategy</p>
                        <p className="mt-1 text-muted-foreground">
                            {planItem.strategy}
                        </p>
                    </div>
                )}
                <AccomplishmentSummary
                    teamSlug={teamSlug}
                    operationalPlanId={operationalPlanId}
                    reportingPeriod={reportingPeriod}
                    planItem={planItem}
                />
                {planItem.documentaryEvidenceRequirements.length > 0 && (
                    <div className="text-sm">
                        <p className="flex items-center gap-2 font-medium">
                            <FileText className="size-4" />
                            Expected documentary evidence
                        </p>
                        <ul className="mt-2 grid list-disc gap-1 pl-5 text-muted-foreground">
                            {planItem.documentaryEvidenceRequirements.map(
                                (requirement, index) => (
                                    <li key={`${requirement}-${index}`}>
                                        {requirement}
                                    </li>
                                ),
                            )}
                        </ul>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function MonitoringIndex({
    academicYear,
    reportingPeriods,
    selectedReportingPeriod,
    operationalPlans,
}: MonitoringPageProps) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam!.slug;

    return (
        <>
            <Head title="Monitoring" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <Heading
                        title={`Semester Monitoring${academicYear ? ` — ${academicYear.name}` : ''}`}
                        description="Report accomplishments against approved Plan Items for the selected Academic Year and semester."
                    />
                    {selectedReportingPeriod && (
                        <div className="grid min-w-56 gap-2">
                            <label
                                htmlFor="reporting-period"
                                className="text-sm font-medium"
                            >
                                Reporting period
                            </label>
                            <Select
                                value={selectedReportingPeriod.id.toString()}
                                onValueChange={(value) =>
                                    router.get(
                                        index.url(teamSlug),
                                        { reporting_period: value },
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        },
                                    )
                                }
                            >
                                <SelectTrigger id="reporting-period">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {reportingPeriods.map((reportingPeriod) => (
                                        <SelectItem
                                            key={reportingPeriod.id}
                                            value={reportingPeriod.id.toString()}
                                        >
                                            {reportingPeriod.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>

                {!academicYear ? (
                    <Card>
                        <CardContent className="py-10 text-center text-muted-foreground">
                            Select an Academic Year to view semester monitoring.
                        </CardContent>
                    </Card>
                ) : !selectedReportingPeriod ? (
                    <Card>
                        <CardContent className="py-10 text-center text-muted-foreground">
                            This Academic Year has no reporting periods.
                        </CardContent>
                    </Card>
                ) : operationalPlans.length === 0 ? (
                    <Card>
                        <CardContent className="grid justify-items-center gap-3 py-12 text-center">
                            <ClipboardCheck className="size-10 text-muted-foreground" />
                            <div>
                                <p className="font-medium">
                                    No approved plans to monitor
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Approved operational plans for{' '}
                                    {academicYear.name} will appear here.
                                </p>
                            </div>
                            <Button asChild variant="outline">
                                <Link href={index(teamSlug)}>
                                    Refresh monitoring
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-8">
                        {operationalPlans.map((operationalPlan) => (
                            <section
                                key={operationalPlan.id}
                                className="grid gap-5"
                            >
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="text-xl font-semibold">
                                            {
                                                operationalPlan
                                                    .organizationalUnit.name
                                            }
                                        </h2>
                                        <StatusBadge
                                            status={operationalPlan.status}
                                            label={operationalPlan.statusLabel}
                                        />
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {selectedReportingPeriod.name}
                                    </p>
                                </div>

                                {operationalPlan.keyResultAreas.map(
                                    (keyResultArea) => (
                                        <div
                                            key={keyResultArea.id}
                                            className="grid gap-4"
                                        >
                                            <div className="border-l-4 border-primary pl-3">
                                                <h3 className="font-semibold">
                                                    {keyResultArea.code
                                                        ? `${keyResultArea.code} — `
                                                        : ''}
                                                    {keyResultArea.name}
                                                </h3>
                                                {keyResultArea.description && (
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {
                                                            keyResultArea.description
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                            <div className="grid gap-4 xl:grid-cols-2">
                                                {keyResultArea.planItems.map(
                                                    (planItem) => (
                                                        <PlanItemCard
                                                            key={planItem.id}
                                                            teamSlug={teamSlug}
                                                            operationalPlanId={
                                                                operationalPlan.id
                                                            }
                                                            reportingPeriod={
                                                                selectedReportingPeriod
                                                            }
                                                            planItem={planItem}
                                                        />
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    ),
                                )}
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

MonitoringIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam
                ? dashboard(props.currentTeam.slug)
                : home(),
        },
        {
            title: 'Monitoring',
            href: props.currentTeam ? index(props.currentTeam.slug) : home(),
        },
    ],
});
