import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ClipboardCheck,
    Download,
    ExternalLink,
    FileText,
    PencilLine,
    Plus,
    Upload,
} from 'lucide-react';
import Heading from '@/components/heading';
import { AccomplishmentDialog } from '@/components/monitoring/accomplishment-dialog';
import { EvidenceUploadDialog } from '@/components/monitoring/evidence-upload-dialog';
import { StatusBadge } from '@/components/status-badge';
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
import AppLayout from '@/layouts/app-layout';
import type {
    MonitoringPageProps,
    MonitoringPlanItem,
    MonitoringReportingPeriod,
} from '@/types';
import { dashboard } from '@/routes';
import { index } from '@/routes/monitoring';
import {
    download as downloadEvidence,
    show as showEvidence,
} from '@/routes/monitoring/accomplishments/evidence';

function AccomplishmentSummary({
    teamSlug,
    reportingPeriod,
    planItem,
}: {
    teamSlug: string;
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
        <div className="grid gap-3 rounded-xl border bg-muted/20 p-4">
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
            <div className="grid gap-2 border-t pt-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-sm font-medium">
                        Uploaded evidence ({accomplishment.evidence.length})
                    </p>
                    {accomplishment.permissions.uploadEvidence && (
                        <EvidenceUploadDialog
                            teamSlug={teamSlug}
                            reportingPeriod={reportingPeriod}
                            planItem={planItem}
                            accomplishment={accomplishment}
                            trigger={
                                <Button size="sm" variant="outline">
                                    <Upload /> Upload evidence
                                </Button>
                            }
                        />
                    )}
                </div>
                {accomplishment.evidence.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No documentary evidence uploaded yet.
                    </p>
                ) : (
                    <ul className="grid gap-2">
                        {accomplishment.evidence.map((evidence) => {
                            const routeParameters = [
                                teamSlug,
                                reportingPeriod.id,
                                planItem.id,
                                accomplishment.id,
                                evidence.id,
                            ] as const;

                            return (
                                <li
                                    key={evidence.id}
                                    className="flex flex-col justify-between gap-2 rounded-lg border bg-background p-3 sm:flex-row sm:items-center"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {evidence.title ||
                                                evidence.originalFilename}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {evidence.originalFilename} ·{' '}
                                            {Math.max(
                                                1,
                                                Math.ceil(
                                                    evidence.fileSize / 1024,
                                                ),
                                            ).toLocaleString()}{' '}
                                            KB · {evidence.uploadedBy.name}
                                        </p>
                                    </div>
                                    <div className="flex gap-1">
                                        <Button
                                            asChild
                                            size="icon"
                                            variant="ghost"
                                        >
                                            <a
                                                href={showEvidence.url(
                                                    routeParameters,
                                                )}
                                                target="_blank"
                                                rel="noreferrer"
                                                aria-label={`View ${evidence.originalFilename}`}
                                            >
                                                <ExternalLink />
                                            </a>
                                        </Button>
                                        <Button
                                            asChild
                                            size="icon"
                                            variant="ghost"
                                        >
                                            <a
                                                href={downloadEvidence.url(
                                                    routeParameters,
                                                )}
                                                aria-label={`Download ${evidence.originalFilename}`}
                                            >
                                                <Download />
                                            </a>
                                        </Button>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </div>
    );
}

function PlanItemCard({
    teamSlug,
    reportingPeriod,
    planItem,
}: {
    teamSlug: string;
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
                                        ? 'Edit draft'
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
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: dashboard(teamSlug) },
                { title: 'Monitoring', href: index(teamSlug) },
            ]}
        >
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
                                            {operationalPlan.department.name}
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
        </AppLayout>
    );
}
