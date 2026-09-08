import { Form } from '@inertiajs/react';
import { Send } from 'lucide-react';
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
import { Spinner } from '@/components/ui/spinner';
import { submit as submitAccomplishment } from '@/routes/monitoring/accomplishments';
import type {
    Accomplishment,
    MonitoringPlanItem,
    MonitoringReportingPeriod,
} from '@/types';

export function AccomplishmentSubmissionDialog({
    teamSlug,
    reportingPeriod,
    planItem,
    accomplishment,
    trigger,
}: {
    teamSlug: string;
    reportingPeriod: MonitoringReportingPeriod;
    planItem: MonitoringPlanItem;
    accomplishment: Accomplishment;
    trigger: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const isResubmission = accomplishment.status === 'returned';

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isResubmission
                            ? 'Resubmit accomplishment'
                            : 'Submit accomplishment'}
                    </DialogTitle>
                    <DialogDescription>
                        {isResubmission
                            ? 'Create a new immutable revision of the updated report and evidence, then return it to reviewers.'
                            : 'Create an immutable snapshot of this report and its evidence, then send it to reviewers.'}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...submitAccomplishment.form([
                        teamSlug,
                        reportingPeriod.id,
                        planItem.id,
                        accomplishment.id,
                    ])}
                    errorBag={`submitAccomplishment${accomplishment.id}`}
                    options={{ preserveScroll: true }}
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="rounded-xl border bg-muted/30 p-4 text-sm leading-6">
                                <p className="font-medium">
                                    {reportingPeriod.name} ·{' '}
                                    {planItem.kpiTargetText}
                                </p>
                                <p className="mt-1 text-muted-foreground">
                                    {isResubmission
                                        ? 'After resubmission, this report cannot be edited while it is ready for review.'
                                        : 'After submission, this draft cannot be edited while it is ready for review.'}
                                </p>
                            </div>

                            <div className="grid gap-1">
                                <InputError message={errors.accomplishment} />
                                <InputError message={errors.evidence} />
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
                                    {processing ? <Spinner /> : <Send />}
                                    {processing
                                        ? isResubmission
                                            ? 'Resubmitting…'
                                            : 'Submitting…'
                                        : isResubmission
                                          ? 'Resubmit for review'
                                          : 'Submit for review'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
