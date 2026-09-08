import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    ClipboardCheck,
    FileText,
    MessageSquareWarning,
    PencilLine,
    RefreshCw,
    Scale,
    XCircle,
} from 'lucide-react';
import { EvaluationDialog } from '@/components/evaluations/evaluation-dialog';
import { EvaluationHistory } from '@/components/evaluations/evaluation-history';
import { EvaluationRemarks } from '@/components/evaluations/evaluation-remarks';
import Heading from '@/components/heading';
import { EvidenceList } from '@/components/monitoring/evidence-list';
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
import { index } from '@/routes/evaluations';
import type {
    EvaluationPageProps,
    EvaluationQueueItem,
    EvaluationRatingOption,
} from '@/types';

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function formatNumber(value: string): string {
    return Number(value).toLocaleString(undefined, {
        maximumFractionDigits: 4,
    });
}

function ContextFact({ label, value }: { label: string; value: string }) {
    return (
        <div className="min-w-0 space-y-1">
            <dt className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="text-sm font-medium break-words">{value}</dd>
        </div>
    );
}

function ReviewQueueCard({
    teamSlug,
    item,
    ratingOptions,
}: {
    teamSlug: string;
    item: EvaluationQueueItem;
    ratingOptions: EvaluationRatingOption[];
}) {
    const accountable = [
        item.operationalPlan.accountableName,
        item.operationalPlan.accountablePosition,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <Card className="overflow-hidden">
            <CardHeader className="gap-3 border-b bg-muted/15">
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div className="min-w-0 space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <CardTitle className="text-lg leading-6">
                                {item.organizationalUnit.name}
                            </CardTitle>
                            {item.organizationalUnit.code && (
                                <Badge variant="secondary">
                                    {item.organizationalUnit.code}
                                </Badge>
                            )}
                        </div>
                        <CardDescription>
                            Submitted by {item.submission.submitter.name} ·{' '}
                            <time dateTime={item.submission.submittedAt}>
                                {formatDateTime(item.submission.submittedAt)}
                            </time>
                        </CardDescription>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline">
                            Revision {item.submission.revision}
                        </Badge>
                        <StatusBadge
                            status={item.accomplishment.status}
                            label={item.accomplishment.statusLabel}
                        />
                    </div>
                </div>
            </CardHeader>

            <CardContent className="grid gap-6 pt-0">
                <dl className="grid gap-4 rounded-xl border bg-muted/20 p-4 sm:grid-cols-2 xl:grid-cols-4">
                    <ContextFact
                        label="Academic Year"
                        value={item.academicYear.name}
                    />
                    <ContextFact
                        label="Reporting Period"
                        value={item.reportingPeriod.name}
                    />
                    <ContextFact
                        label="Organizational Unit"
                        value={item.organizationalUnit.name}
                    />
                    <ContextFact
                        label="Key Result Area"
                        value={
                            item.keyResultArea.code
                                ? `${item.keyResultArea.code} — ${item.keyResultArea.name}`
                                : item.keyResultArea.name
                        }
                    />
                </dl>

                <div className="grid gap-4 lg:grid-cols-2">
                    <section className="grid content-start gap-4 rounded-xl border p-4">
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Operational Plan Goal
                            </p>
                            <p className="mt-1 text-sm leading-6 whitespace-pre-wrap">
                                {item.operationalPlan.goal}
                            </p>
                        </div>
                        {accountable && (
                            <div>
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Accountable
                                </p>
                                <p className="mt-1 text-sm">{accountable}</p>
                            </div>
                        )}
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Objective
                            </p>
                            <p className="mt-1 text-sm leading-6 whitespace-pre-wrap">
                                {item.planItem.objective}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Strategy
                            </p>
                            <p className="mt-1 text-sm leading-6 whitespace-pre-wrap">
                                {item.planItem.strategy ?? 'Not specified'}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                KPI / Target
                            </p>
                            <p className="mt-1 text-sm leading-6 whitespace-pre-wrap">
                                {item.planItem.kpiTargetText}
                            </p>
                            {(item.planItem.targetValue ||
                                item.planItem.targetFrequency) && (
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {item.planItem.targetValue
                                        ? `${formatNumber(item.planItem.targetValue)}${item.planItem.targetUnit ? ` ${item.planItem.targetUnit}` : ''}`
                                        : 'Qualitative target'}
                                    {item.planItem.targetFrequency
                                        ? ` · ${item.planItem.targetFrequency}`
                                        : ''}
                                </p>
                            )}
                        </div>
                        {item.planItem.resourcesNeeded && (
                            <div>
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Resources Needed
                                </p>
                                <p className="mt-1 text-sm leading-6 whitespace-pre-wrap">
                                    {item.planItem.resourcesNeeded}
                                </p>
                            </div>
                        )}
                    </section>

                    <section className="grid content-start gap-4 rounded-xl border p-4">
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Reported Accomplishment
                            </p>
                            <p className="mt-1 text-sm leading-6 whitespace-pre-wrap">
                                {item.accomplishment.accomplishmentText ??
                                    'No narrative was provided.'}
                            </p>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Reported Value
                                </p>
                                <p className="mt-1 text-lg font-semibold">
                                    {item.accomplishment.reportedValue
                                        ? `${formatNumber(item.accomplishment.reportedValue)}${item.planItem.targetUnit ? ` ${item.planItem.targetUnit}` : ''}`
                                        : 'Not applicable'}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Accomplishment
                                </p>
                                <p className="mt-1 text-lg font-semibold">
                                    {item.accomplishment.percentageAccomplished
                                        ? `${formatNumber(item.accomplishment.percentageAccomplished)}%`
                                        : 'Qualitative'}
                                </p>
                            </div>
                        </div>
                        <div>
                            <p className="flex items-center gap-2 text-sm font-medium">
                                <FileText className="size-4" /> Expected
                                documentary evidence
                            </p>
                            {item.planItem.documentaryEvidenceRequirements
                                .length > 0 ? (
                                <ul className="mt-2 grid list-disc gap-1 pl-5 text-sm leading-6 text-muted-foreground">
                                    {item.planItem.documentaryEvidenceRequirements.map(
                                        (requirement, requirementIndex) => (
                                            <li
                                                key={`${requirement}-${requirementIndex}`}
                                            >
                                                {requirement}
                                            </li>
                                        ),
                                    )}
                                </ul>
                            ) : (
                                <p className="mt-2 text-sm text-muted-foreground">
                                    No specific evidence requirement was listed.
                                </p>
                            )}
                        </div>
                    </section>
                </div>

                <section className="grid gap-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h3 className="font-medium">
                            Uploaded evidence ({item.evidence.length})
                        </h3>
                        <Badge variant="secondary">
                            Submission revision {item.submission.revision}
                        </Badge>
                    </div>
                    <EvidenceList
                        teamSlug={teamSlug}
                        reportingPeriodId={item.reportingPeriod.id}
                        planItemId={item.planItem.id}
                        accomplishmentId={item.accomplishment.id}
                        evidence={item.evidence}
                    />
                </section>

                <EvaluationRemarks teamSlug={teamSlug} item={item} />

                <EvaluationHistory history={item.evaluationHistory} />

                {item.effectiveEvaluation && (
                    <section className="grid gap-2 rounded-xl border border-primary/30 bg-primary/5 p-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <Scale className="size-4" />
                            <h3 className="font-medium">Effective rating</h3>
                            <Badge variant="secondary">
                                {item.effectiveEvaluation.rating} —{' '}
                                {item.effectiveEvaluation.ratingLabel}
                            </Badge>
                            {item.effectiveEvaluation.isOverride && (
                                <Badge variant="destructive">
                                    Presidential override
                                </Badge>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {item.effectiveEvaluation.authority.label}:{' '}
                            {item.effectiveEvaluation.reviewer.name}
                            {item.effectiveEvaluation.authority.name
                                ? ` · ${item.effectiveEvaluation.authority.name}`
                                : ''}
                        </p>
                    </section>
                )}

                <section className="flex flex-col justify-between gap-4 border-t pt-5 lg:flex-row lg:items-center">
                    <div>
                        <h3 className="font-medium">Human evaluation</h3>
                        <p className="mt-1 text-sm leading-5 text-muted-foreground">
                            Review the submitted revision and evidence before
                            recording a final action.
                        </p>
                    </div>
                    {item.permissions.reviewAccomplishment &&
                    item.presidentialReview ? (
                        <div className="grid gap-3 lg:max-w-2xl">
                            <div className="rounded-xl border bg-muted/20 p-4">
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    VP evaluation for Presidential review
                                </p>
                                <div className="mt-2 flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">
                                        {item.presidentialReview.rating} —{' '}
                                        {item.presidentialReview.ratingLabel}
                                    </Badge>
                                    <span className="text-sm">
                                        {item.presidentialReview.reviewer.name}
                                    </span>
                                </div>
                                {item.presidentialReview.remarks && (
                                    <p className="mt-3 text-sm leading-6 whitespace-pre-wrap">
                                        {item.presidentialReview.remarks}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                <EvaluationDialog
                                    decision="accepted"
                                    presidentialAction="agreement"
                                    sourceRating={
                                        item.presidentialReview.rating
                                    }
                                    teamSlug={teamSlug}
                                    item={item}
                                    ratingOptions={ratingOptions}
                                    trigger={
                                        <Button type="button">
                                            <CheckCircle2 /> Agree with VP
                                        </Button>
                                    }
                                />
                                <EvaluationDialog
                                    decision="accepted"
                                    presidentialAction="override"
                                    sourceRating={
                                        item.presidentialReview.rating
                                    }
                                    teamSlug={teamSlug}
                                    item={item}
                                    ratingOptions={ratingOptions}
                                    trigger={
                                        <Button type="button" variant="outline">
                                            <Scale /> Override rating
                                        </Button>
                                    }
                                />
                            </div>
                        </div>
                    ) : item.permissions.reviewAccomplishment ? (
                        <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <EvaluationDialog
                                decision="accepted"
                                teamSlug={teamSlug}
                                item={item}
                                ratingOptions={ratingOptions}
                                trigger={
                                    <Button type="button">
                                        <CheckCircle2 /> Accept
                                    </Button>
                                }
                            />
                            <EvaluationDialog
                                decision="request_revision"
                                teamSlug={teamSlug}
                                item={item}
                                ratingOptions={ratingOptions}
                                trigger={
                                    <Button type="button" variant="outline">
                                        <PencilLine /> Request Revision
                                    </Button>
                                }
                            />
                            <EvaluationDialog
                                decision="request_additional_evidence"
                                teamSlug={teamSlug}
                                item={item}
                                ratingOptions={ratingOptions}
                                trigger={
                                    <Button type="button" variant="outline">
                                        <MessageSquareWarning /> Request
                                        Evidence
                                    </Button>
                                }
                            />
                            <EvaluationDialog
                                decision="rejected"
                                teamSlug={teamSlug}
                                item={item}
                                ratingOptions={ratingOptions}
                                trigger={
                                    <Button type="button" variant="destructive">
                                        <XCircle /> Reject
                                    </Button>
                                }
                            />
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            This submission can no longer be evaluated.
                        </p>
                    )}
                </section>
            </CardContent>
        </Card>
    );
}

export default function EvaluationsIndex({
    academicYear,
    reportingPeriods,
    selectedReportingPeriod,
    queue,
    ratingOptions,
}: EvaluationPageProps) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return (
            <div className="p-6 text-sm text-muted-foreground">
                Select a team before reviewing accomplishments.
            </div>
        );
    }

    const teamSlug = currentTeam.slug;

    return (
        <>
            <Head title="Evidence Review" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div className="min-w-0">
                        <Heading
                            title={`Evidence Review${academicYear ? ` — ${academicYear.name}` : ''}`}
                            description="Manually evaluate submitted accomplishments and their documentary evidence."
                        />
                        {academicYear && selectedReportingPeriod && (
                            <Badge variant="secondary">
                                {queue.length}{' '}
                                {queue.length === 1
                                    ? 'submission awaiting review'
                                    : 'submissions awaiting review'}
                            </Badge>
                        )}
                    </div>
                    {selectedReportingPeriod && (
                        <div className="grid min-w-56 gap-2">
                            <label
                                htmlFor="evaluation-reporting-period"
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
                                <SelectTrigger id="evaluation-reporting-period">
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
                        <CardContent className="py-12 text-center text-muted-foreground">
                            Select an Academic Year to open its evidence review
                            queue.
                        </CardContent>
                    </Card>
                ) : !selectedReportingPeriod ? (
                    <Card>
                        <CardContent className="py-12 text-center text-muted-foreground">
                            This Academic Year has no reporting periods.
                        </CardContent>
                    </Card>
                ) : queue.length === 0 ? (
                    <Card>
                        <CardContent className="grid justify-items-center gap-3 py-12 text-center">
                            <ClipboardCheck className="size-10 text-muted-foreground" />
                            <div>
                                <p className="font-medium">
                                    Review queue is clear
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    No {selectedReportingPeriod.name}{' '}
                                    accomplishments are awaiting review for{' '}
                                    {academicYear.name}.
                                </p>
                            </div>
                            <Button asChild variant="outline">
                                <Link
                                    href={index(teamSlug, {
                                        query: {
                                            reporting_period:
                                                selectedReportingPeriod.id,
                                        },
                                    })}
                                >
                                    <RefreshCw /> Refresh queue
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-5">
                        {queue.map((item) => (
                            <ReviewQueueCard
                                key={item.submission.id}
                                teamSlug={teamSlug}
                                item={item}
                                ratingOptions={ratingOptions}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

EvaluationsIndex.layout = (props: {
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
            title: 'Evidence Review',
            href: props.currentTeam ? index(props.currentTeam.slug) : home(),
        },
    ],
});
