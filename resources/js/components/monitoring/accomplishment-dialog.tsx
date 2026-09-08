import { Form } from '@inertiajs/react';
import { ClipboardPenLine } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store, update } from '@/routes/monitoring/accomplishments';
import type {
    MonitoringPlanItem,
    MonitoringReportingPeriod,
    TargetOperator,
} from '@/types';

const percentageOperators: Array<TargetOperator | null> = [
    null,
    'equals',
    'at_least',
    'percentage_at_least',
];

function formatNumber(value: string): string {
    return new Intl.NumberFormat(undefined, {
        maximumFractionDigits: 4,
    }).format(Number(value));
}

export function AccomplishmentDialog({
    teamSlug,
    reportingPeriod,
    planItem,
    trigger,
}: {
    teamSlug: string;
    reportingPeriod: MonitoringReportingPeriod;
    planItem: MonitoringPlanItem;
    trigger: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const accomplishment = planItem.accomplishment;
    const isReturned = accomplishment?.status === 'returned';
    const formRoute = accomplishment
        ? update.form([
              teamSlug,
              reportingPeriod.id,
              planItem.id,
              accomplishment.id,
          ])
        : store.form([teamSlug, reportingPeriod.id, planItem.id]);
    const canCalculatePercentage =
        planItem.targetValue !== null &&
        Number(planItem.targetValue) > 0 &&
        percentageOperators.includes(planItem.targetOperator);
    const idPrefix = `accomplishment-${planItem.id}`;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {accomplishment
                            ? isReturned
                                ? 'Edit returned accomplishment'
                                : 'Edit accomplishment draft'
                            : 'Report accomplishment'}
                    </DialogTitle>
                    <DialogDescription>
                        {reportingPeriod.name} · {planItem.kpiTargetText}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...formRoute}
                    errorBag={`${accomplishment ? 'update' : 'create'}Accomplishment${planItem.id}`}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={!accomplishment}
                    setDefaultsOnSuccess={Boolean(accomplishment)}
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <InputError message={errors.accomplishment} />

                            <div className="grid gap-2">
                                <Label htmlFor={`${idPrefix}-reported-value`}>
                                    Reported numeric value
                                </Label>
                                <Input
                                    id={`${idPrefix}-reported-value`}
                                    name="reported_value"
                                    type="number"
                                    min="0"
                                    step="any"
                                    defaultValue={
                                        accomplishment?.reportedValue ?? ''
                                    }
                                    placeholder="Leave blank for qualitative accomplishments"
                                    aria-invalid={Boolean(
                                        errors.reported_value,
                                    )}
                                />
                                <p className="text-xs leading-5 text-muted-foreground">
                                    Enter a value only when the KPI can be
                                    measured numerically
                                    {planItem.targetUnit
                                        ? ` in ${planItem.targetUnit}`
                                        : ''}
                                    .
                                </p>
                                <InputError message={errors.reported_value} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`${idPrefix}-accomplishment-text`}
                                >
                                    Reported accomplishment
                                </Label>
                                <Textarea
                                    id={`${idPrefix}-accomplishment-text`}
                                    name="accomplishment_text"
                                    className="min-h-36"
                                    defaultValue={
                                        accomplishment?.accomplishmentText ?? ''
                                    }
                                    placeholder="Describe what was completed during this reporting period."
                                    aria-invalid={Boolean(
                                        errors.accomplishment_text,
                                    )}
                                />
                                <p className="text-xs leading-5 text-muted-foreground">
                                    A numeric value or an accomplishment
                                    description is required. Qualitative targets
                                    should be explained here.
                                </p>
                                <InputError
                                    message={errors.accomplishment_text}
                                />
                            </div>

                            <div className="rounded-xl border bg-muted/30 p-4 text-sm">
                                <p className="font-medium">
                                    Accomplishment percentage
                                </p>
                                {accomplishment?.percentageAccomplished ? (
                                    <p className="mt-1 text-lg font-semibold">
                                        {formatNumber(
                                            accomplishment.percentageAccomplished,
                                        )}
                                        %
                                    </p>
                                ) : null}
                                <p className="mt-1 leading-5 text-muted-foreground">
                                    {canCalculatePercentage
                                        ? `The server calculates this from the reported value and target of ${formatNumber(planItem.targetValue ?? '0')}${planItem.targetUnit ? ` ${planItem.targetUnit}` : ''}. It cannot be entered manually.`
                                        : 'This KPI is qualitative or does not have a compatible numeric target, so a percentage is not forced.'}
                                </p>
                            </div>

                            {planItem.documentaryEvidenceRequirements.length >
                                0 && (
                                <div className="rounded-xl border bg-muted/20 p-4">
                                    <p className="text-sm font-medium">
                                        Expected documentary evidence
                                    </p>
                                    <ul className="mt-2 grid list-disc gap-1 pl-5 text-sm leading-5 text-muted-foreground">
                                        {planItem.documentaryEvidenceRequirements.map(
                                            (requirement, index) => (
                                                <li
                                                    key={`${requirement}-${index}`}
                                                >
                                                    {requirement}
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}

                            <div className="grid gap-3 rounded-xl border p-4">
                                <div>
                                    <p className="text-sm font-medium">
                                        Upload documentary evidence
                                    </p>
                                    <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                        Upload the documents listed in the
                                        evidence requirements. You can select
                                        multiple files.
                                    </p>
                                </div>
                                <Input
                                    id={`${idPrefix}-evidence-files`}
                                    name="evidence_files[]"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                    multiple
                                    aria-label="Documentary evidence files"
                                />
                                <InputError message={errors.evidence_files} />
                                <p className="text-xs text-muted-foreground">
                                    Accepted formats: PDF, JPG, JPEG, and PNG.
                                    Maximum 10 MB per file.
                                </p>
                            </div>

                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200">
                                {isReturned
                                    ? 'This updates the returned report. Save your changes, then upload at least one additional evidence file before resubmitting it for review.'
                                    : 'This saves a draft. Upload at least one evidence file before submitting it for review.'}
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <Spinner />
                                    ) : (
                                        <ClipboardPenLine />
                                    )}
                                    {processing
                                        ? 'Saving…'
                                        : accomplishment
                                          ? isReturned
                                              ? 'Update returned report'
                                              : 'Update draft'
                                          : 'Save draft'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
