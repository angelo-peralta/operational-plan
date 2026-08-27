import { Form, Head, usePage } from '@inertiajs/react';
import { CalendarDays, CircleCheck, Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { index, store, update } from '@/routes/academic-years';
import { update as updateReportingPeriod } from '@/routes/academic-years/reporting-periods';
import type {
    AcademicYear,
    AcademicYearStatus,
    ReportingPeriod,
    ReportingPeriodStatus,
    SelectOption,
} from '@/types';

type Props = {
    academicYears: AcademicYear[];
    academicYearStatuses: SelectOption<AcademicYearStatus>[];
    reportingPeriodStatuses: SelectOption<ReportingPeriodStatus>[];
};

function dateValue(value: string | null): string {
    return value?.slice(0, 10) ?? '';
}

function CreateAcademicYearForm({
    teamSlug,
    statuses,
}: {
    teamSlug: string;
    statuses: SelectOption<AcademicYearStatus>[];
}) {
    const [status, setStatus] = useState<AcademicYearStatus>('draft');
    const [isCurrent, setIsCurrent] = useState(false);

    return (
        <Card className="h-fit lg:sticky lg:top-6">
            <CardHeader>
                <div className="flex items-center gap-3">
                    <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Plus className="size-5" />
                    </div>
                    <div className="space-y-1">
                        <CardTitle>Create Academic Year</CardTitle>
                        <CardDescription>
                            First and Second Semester periods are created with
                            the year.
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <Form
                    {...store.form(teamSlug)}
                    errorBag="createAcademicYear"
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => {
                        setStatus('draft');
                        setIsCurrent(false);
                    }}
                    className="grid gap-5"
                >
                    {({ errors, processing, recentlySuccessful }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="create-ay-name">Name</Label>
                                <Input
                                    id="create-ay-name"
                                    name="name"
                                    placeholder="AY 2025-2026"
                                    required
                                    aria-invalid={Boolean(errors.name)}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="create-ay-start-year">
                                        Start year
                                    </Label>
                                    <Input
                                        id="create-ay-start-year"
                                        name="start_year"
                                        type="number"
                                        min="2000"
                                        max="9998"
                                        placeholder="2025"
                                        required
                                        aria-invalid={Boolean(
                                            errors.start_year,
                                        )}
                                    />
                                    <InputError message={errors.start_year} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="create-ay-end-year">
                                        End year
                                    </Label>
                                    <Input
                                        id="create-ay-end-year"
                                        name="end_year"
                                        type="number"
                                        min="2001"
                                        max="9999"
                                        placeholder="2026"
                                        required
                                        aria-invalid={Boolean(errors.end_year)}
                                    />
                                    <InputError message={errors.end_year} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="create-ay-starts-on">
                                        Starts on
                                    </Label>
                                    <Input
                                        id="create-ay-starts-on"
                                        name="starts_on"
                                        type="date"
                                        aria-invalid={Boolean(errors.starts_on)}
                                    />
                                    <InputError message={errors.starts_on} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="create-ay-ends-on">
                                        Ends on
                                    </Label>
                                    <Input
                                        id="create-ay-ends-on"
                                        name="ends_on"
                                        type="date"
                                        aria-invalid={Boolean(errors.ends_on)}
                                    />
                                    <InputError message={errors.ends_on} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="create-ay-status">Status</Label>
                                <Select
                                    name="status"
                                    value={status}
                                    onValueChange={(value) =>
                                        setStatus(value as AcademicYearStatus)
                                    }
                                >
                                    <SelectTrigger
                                        id="create-ay-status"
                                        className="w-full"
                                        aria-invalid={Boolean(errors.status)}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="space-y-2">
                                <input
                                    type="hidden"
                                    name="is_current"
                                    value={isCurrent ? '1' : '0'}
                                />
                                <div className="flex items-start gap-3 rounded-lg border bg-muted/30 p-3">
                                    <Checkbox
                                        id="create-ay-current"
                                        checked={isCurrent}
                                        onCheckedChange={(checked) =>
                                            setIsCurrent(checked === true)
                                        }
                                    />
                                    <div className="grid gap-1">
                                        <Label htmlFor="create-ay-current">
                                            Make this the current Academic Year
                                        </Label>
                                        <p className="text-xs leading-5 text-muted-foreground">
                                            Only one year can be marked current.
                                        </p>
                                    </div>
                                </div>
                                <InputError message={errors.is_current} />
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Creating...'
                                        : 'Create Academic Year'}
                                </Button>
                                {recentlySuccessful && (
                                    <span className="flex items-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-300">
                                        <CircleCheck className="size-4" />
                                        Created
                                    </span>
                                )}
                            </div>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}

function ReportingPeriodForm({
    teamSlug,
    academicYearId,
    period,
    statuses,
}: {
    teamSlug: string;
    academicYearId: number;
    period: ReportingPeriod;
    statuses: SelectOption<ReportingPeriodStatus>[];
}) {
    const [status, setStatus] = useState(period.status);

    return (
        <Form
            {...updateReportingPeriod.form([
                teamSlug,
                academicYearId,
                period.id,
            ])}
            errorBag={`updateReportingPeriod${period.id}`}
            options={{ preserveScroll: true }}
            setDefaultsOnSuccess
            disableWhileProcessing
            className="grid gap-4 rounded-xl border bg-muted/20 p-4"
        >
            {({ errors, processing, recentlySuccessful }) => (
                <>
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p className="font-medium">{period.name}</p>
                            <p className="text-xs text-muted-foreground">
                                Reporting period {period.sequence}
                            </p>
                        </div>
                        <StatusBadge status={status} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`period-${period.id}-name`}>
                                Name
                            </Label>
                            <Input
                                id={`period-${period.id}-name`}
                                name="name"
                                defaultValue={period.name}
                                required
                                aria-invalid={Boolean(errors.name)}
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={`period-${period.id}-code`}>
                                Code
                            </Label>
                            <Input
                                id={`period-${period.id}-code`}
                                name="code"
                                defaultValue={period.code}
                                required
                                aria-invalid={Boolean(errors.code)}
                            />
                            <InputError message={errors.code} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`period-${period.id}-starts-on`}>
                                Starts on
                            </Label>
                            <Input
                                id={`period-${period.id}-starts-on`}
                                name="starts_on"
                                type="date"
                                defaultValue={dateValue(period.startsOn)}
                                aria-invalid={Boolean(errors.starts_on)}
                            />
                            <InputError message={errors.starts_on} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={`period-${period.id}-ends-on`}>
                                Ends on
                            </Label>
                            <Input
                                id={`period-${period.id}-ends-on`}
                                name="ends_on"
                                type="date"
                                defaultValue={dateValue(period.endsOn)}
                                aria-invalid={Boolean(errors.ends_on)}
                            />
                            <InputError message={errors.ends_on} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_8rem]">
                        <div className="grid gap-2">
                            <Label htmlFor={`period-${period.id}-status`}>
                                Status
                            </Label>
                            <Select
                                name="status"
                                value={status}
                                onValueChange={(value) =>
                                    setStatus(value as ReportingPeriodStatus)
                                }
                            >
                                <SelectTrigger
                                    id={`period-${period.id}-status`}
                                    className="w-full"
                                    aria-invalid={Boolean(errors.status)}
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={`period-${period.id}-sequence`}>
                                Sequence
                            </Label>
                            <Input
                                id={`period-${period.id}-sequence`}
                                name="sequence"
                                type="number"
                                min="1"
                                defaultValue={period.sequence}
                                required
                                aria-invalid={Boolean(errors.sequence)}
                            />
                            <InputError message={errors.sequence} />
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button
                            type="submit"
                            size="sm"
                            variant="secondary"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            {processing ? 'Saving...' : 'Save period'}
                        </Button>
                        {recentlySuccessful && (
                            <span className="text-xs text-emerald-700 dark:text-emerald-300">
                                Saved
                            </span>
                        )}
                    </div>
                </>
            )}
        </Form>
    );
}

function AcademicYearCard({
    teamSlug,
    academicYear,
    statuses,
    reportingPeriodStatuses,
}: {
    teamSlug: string;
    academicYear: AcademicYear;
    statuses: SelectOption<AcademicYearStatus>[];
    reportingPeriodStatuses: SelectOption<ReportingPeriodStatus>[];
}) {
    const [status, setStatus] = useState(academicYear.status);
    const [isCurrent, setIsCurrent] = useState(academicYear.isCurrent);

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-start gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                            <CalendarDays className="size-5" />
                        </div>
                        <div className="space-y-1.5">
                            <CardTitle className="text-lg">
                                {academicYear.name}
                            </CardTitle>
                            <CardDescription>
                                {academicYear.startYear}–{academicYear.endYear}
                            </CardDescription>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {academicYear.isCurrent && (
                            <StatusBadge status="current" label="Current" />
                        )}
                        <StatusBadge status={academicYear.status} />
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-6">
                <Form
                    {...update.form([teamSlug, academicYear.id])}
                    errorBag={`updateAcademicYear${academicYear.id}`}
                    options={{ preserveScroll: true }}
                    setDefaultsOnSuccess
                    disableWhileProcessing
                    className="grid gap-5"
                >
                    {({ errors, processing, recentlySuccessful }) => (
                        <>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="grid gap-2 md:col-span-3">
                                    <Label
                                        htmlFor={`academic-year-${academicYear.id}-name`}
                                    >
                                        Name
                                    </Label>
                                    <Input
                                        id={`academic-year-${academicYear.id}-name`}
                                        name="name"
                                        defaultValue={academicYear.name}
                                        required
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`academic-year-${academicYear.id}-start-year`}
                                    >
                                        Start year
                                    </Label>
                                    <Input
                                        id={`academic-year-${academicYear.id}-start-year`}
                                        name="start_year"
                                        type="number"
                                        min="2000"
                                        max="9998"
                                        defaultValue={academicYear.startYear}
                                        required
                                        aria-invalid={Boolean(
                                            errors.start_year,
                                        )}
                                    />
                                    <InputError message={errors.start_year} />
                                </div>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`academic-year-${academicYear.id}-end-year`}
                                    >
                                        End year
                                    </Label>
                                    <Input
                                        id={`academic-year-${academicYear.id}-end-year`}
                                        name="end_year"
                                        type="number"
                                        min="2001"
                                        max="9999"
                                        defaultValue={academicYear.endYear}
                                        required
                                        aria-invalid={Boolean(errors.end_year)}
                                    />
                                    <InputError message={errors.end_year} />
                                </div>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`academic-year-${academicYear.id}-status`}
                                    >
                                        Status
                                    </Label>
                                    <Select
                                        name="status"
                                        value={status}
                                        onValueChange={(value) =>
                                            setStatus(
                                                value as AcademicYearStatus,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id={`academic-year-${academicYear.id}-status`}
                                            className="w-full"
                                            aria-invalid={Boolean(
                                                errors.status,
                                            )}
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statuses.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.status} />
                                </div>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`academic-year-${academicYear.id}-starts-on`}
                                    >
                                        Starts on
                                    </Label>
                                    <Input
                                        id={`academic-year-${academicYear.id}-starts-on`}
                                        name="starts_on"
                                        type="date"
                                        defaultValue={dateValue(
                                            academicYear.startsOn,
                                        )}
                                        aria-invalid={Boolean(errors.starts_on)}
                                    />
                                    <InputError message={errors.starts_on} />
                                </div>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`academic-year-${academicYear.id}-ends-on`}
                                    >
                                        Ends on
                                    </Label>
                                    <Input
                                        id={`academic-year-${academicYear.id}-ends-on`}
                                        name="ends_on"
                                        type="date"
                                        defaultValue={dateValue(
                                            academicYear.endsOn,
                                        )}
                                        aria-invalid={Boolean(errors.ends_on)}
                                    />
                                    <InputError message={errors.ends_on} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <input
                                    type="hidden"
                                    name="is_current"
                                    value={isCurrent ? '1' : '0'}
                                />
                                <div className="flex items-start gap-3 rounded-lg border bg-muted/30 p-3">
                                    <Checkbox
                                        id={`academic-year-${academicYear.id}-current`}
                                        checked={isCurrent}
                                        onCheckedChange={(checked) =>
                                            setIsCurrent(checked === true)
                                        }
                                    />
                                    <div className="grid gap-1">
                                        <Label
                                            htmlFor={`academic-year-${academicYear.id}-current`}
                                        >
                                            Current Academic Year
                                        </Label>
                                        <p className="text-xs leading-5 text-muted-foreground">
                                            Setting this year as current clears
                                            the flag from every other year.
                                        </p>
                                    </div>
                                </div>
                                <InputError message={errors.is_current} />
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Saving...'
                                        : 'Save Academic Year'}
                                </Button>
                                {recentlySuccessful && (
                                    <span className="text-sm text-emerald-700 dark:text-emerald-300">
                                        Changes saved
                                    </span>
                                )}
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-y-3 border-t pt-6">
                    <div>
                        <h3 className="font-medium">Reporting periods</h3>
                        <p className="text-sm text-muted-foreground">
                            Opening and closing a semester controls when
                            accomplishments can be submitted.
                        </p>
                    </div>
                    <div className="grid gap-4 xl:grid-cols-2">
                        {academicYear.reportingPeriods.map((period) => (
                            <ReportingPeriodForm
                                key={`${period.id}-${period.status}`}
                                teamSlug={teamSlug}
                                academicYearId={academicYear.id}
                                period={period}
                                statuses={reportingPeriodStatuses}
                            />
                        ))}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function AcademicYearsIndex({
    academicYears,
    academicYearStatuses,
    reportingPeriodStatuses,
}: Props) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return null;
    }

    return (
        <>
            <Head title="Academic Years" />
            <div className="space-y-6 p-4 md:p-6">
                <Heading
                    title="Academic Years"
                    description="Create the planning calendar, choose the current year, and control semester availability."
                />

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,2fr)]">
                    <CreateAcademicYearForm
                        teamSlug={currentTeam.slug}
                        statuses={academicYearStatuses}
                    />

                    <div className="space-y-4">
                        {academicYears.length === 0 ? (
                            <Card className="border-dashed">
                                <CardContent className="flex min-h-48 flex-col items-center justify-center gap-3 text-center">
                                    <CalendarDays className="size-8 text-muted-foreground" />
                                    <div className="space-y-1">
                                        <p className="font-medium">
                                            No Academic Years yet
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Create the first year to establish
                                            the planning context.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            academicYears.map((academicYear) => (
                                <AcademicYearCard
                                    key={`${academicYear.id}-${academicYear.status}-${academicYear.isCurrent}`}
                                    teamSlug={currentTeam.slug}
                                    academicYear={academicYear}
                                    statuses={academicYearStatuses}
                                    reportingPeriodStatuses={
                                        reportingPeriodStatuses
                                    }
                                />
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

AcademicYearsIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentTeam
        ? [
              {
                  title: 'Academic Years',
                  href: index(props.currentTeam.slug),
              },
          ]
        : [],
});
