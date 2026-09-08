import { Form } from '@inertiajs/react';
import {
    CheckCircle2,
    MessageSquareWarning,
    PencilLine,
    XCircle,
} from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/evaluations';
import type {
    EvaluationDecision,
    EvaluationQueueItem,
    EvaluationRating,
    EvaluationRatingOption,
} from '@/types';

const actionContent: Record<
    EvaluationDecision,
    {
        title: string;
        description: string;
        submitLabel: string;
        processingLabel: string;
        variant: 'default' | 'outline' | 'destructive';
    }
> = {
    accepted: {
        title: 'Accept accomplishment',
        description:
            'Confirm the official rating and accept this submitted revision.',
        submitLabel: 'Accept accomplishment',
        processingLabel: 'Accepting…',
        variant: 'default',
    },
    request_additional_evidence: {
        title: 'Request additional evidence',
        description:
            'Return this accomplishment to the organizational unit with clear instructions for resubmission.',
        submitLabel: 'Request evidence',
        processingLabel: 'Returning…',
        variant: 'default',
    },
    request_revision: {
        title: 'Request revision',
        description:
            'Return this accomplishment with corrections that must be completed before resubmission.',
        submitLabel: 'Request revision',
        processingLabel: 'Returning…',
        variant: 'outline',
    },
    rejected: {
        title: 'Reject accomplishment',
        description:
            'Reject this submitted revision. This final decision remains in the review history.',
        submitLabel: 'Reject accomplishment',
        processingLabel: 'Rejecting…',
        variant: 'destructive',
    },
};

export function EvaluationDialog({
    decision,
    teamSlug,
    item,
    ratingOptions,
    trigger,
    presidentialAction,
    sourceRating,
}: {
    decision: EvaluationDecision;
    teamSlug: string;
    item: EvaluationQueueItem;
    ratingOptions: EvaluationRatingOption[];
    trigger: ReactNode;
    presidentialAction?: 'agreement' | 'override';
    sourceRating?: EvaluationRating;
}) {
    const initialRating =
        presidentialAction === 'agreement' && sourceRating !== undefined
            ? String(sourceRating)
            : decision === 'rejected'
              ? 'none'
              : '';
    const [open, setOpen] = useState(false);
    const [rating, setRating] = useState(initialRating);
    const content = presidentialAction
        ? {
              title:
                  presidentialAction === 'agreement'
                      ? 'Agree with VP evaluation'
                      : 'Override VP rating',
              description:
                  presidentialAction === 'agreement'
                      ? 'Confirm the VP rating as the effective Presidential evaluation. You may add remarks.'
                      : 'Record a different Presidential rating while preserving the VP evaluation in history.',
              submitLabel:
                  presidentialAction === 'agreement'
                      ? 'Confirm agreement'
                      : 'Record override',
              processingLabel: 'Recording…',
              variant: 'default' as const,
          }
        : actionContent[decision];
    const permitsRating = ![
        'request_revision',
        'request_additional_evidence',
    ].includes(decision);
    const requiresRating = decision === 'accepted';
    const requiresRemarks =
        decision !== 'accepted' || rating === '0' || rating === '1';
    const idPrefix = `${decision}-${presidentialAction ?? 'initial'}-${item.accomplishment.id}`;
    const formRouteParameters: [string, number, number, number] = [
        teamSlug,
        item.reportingPeriod.id,
        item.planItem.id,
        item.accomplishment.id,
    ];
    const SubmitIcon =
        decision === 'accepted'
            ? CheckCircle2
            : decision === 'request_additional_evidence'
              ? MessageSquareWarning
              : decision === 'request_revision'
                ? PencilLine
                : XCircle;

    const updateOpen = (nextOpen: boolean): void => {
        setOpen(nextOpen);

        if (!nextOpen) {
            setRating(initialRating);
        }
    };

    return (
        <Dialog open={open} onOpenChange={updateOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{content.title}</DialogTitle>
                    <DialogDescription>{content.description}</DialogDescription>
                </DialogHeader>

                <Form
                    {...store.form(formRouteParameters)}
                    errorBag={`evaluateAccomplishment${item.accomplishment.id}-${decision}-${presidentialAction ?? 'initial'}`}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => updateOpen(false)}
                    className="grid gap-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <input
                                type="hidden"
                                name="decision"
                                value={decision}
                            />
                            {presidentialAction === 'override' && (
                                <input
                                    type="hidden"
                                    name="is_override"
                                    value="1"
                                />
                            )}
                            {permitsRating && (
                                <input
                                    type="hidden"
                                    name="rating"
                                    value={rating === 'none' ? '' : rating}
                                />
                            )}

                            <div className="rounded-xl border bg-muted/30 p-4 text-sm leading-6">
                                <p className="font-medium">
                                    Revision {item.submission.revision} ·{' '}
                                    {item.organizationalUnit.name}
                                </p>
                                <p className="mt-1 text-muted-foreground">
                                    {item.reportingPeriod.name} ·{' '}
                                    {item.planItem.kpiTargetText}
                                </p>
                            </div>

                            <div className="grid gap-1">
                                <InputError message={errors.accomplishment} />
                                <InputError message={errors.decision} />
                            </div>

                            {permitsRating &&
                                presidentialAction === 'agreement' && (
                                    <div className="grid gap-2">
                                        <Label>Official rating</Label>
                                        <div className="rounded-lg border bg-muted/30 px-3 py-2 text-sm font-medium">
                                            {sourceRating} —{' '}
                                            {
                                                ratingOptions.find(
                                                    (option) =>
                                                        option.value ===
                                                        sourceRating,
                                                )?.label
                                            }
                                        </div>
                                        <InputError message={errors.rating} />
                                    </div>
                                )}

                            {permitsRating &&
                                presidentialAction !== 'agreement' && (
                                    <div className="grid gap-2">
                                        <Label htmlFor={`${idPrefix}-rating`}>
                                            Official rating
                                            {!requiresRating && (
                                                <span className="font-normal text-muted-foreground">
                                                    {' '}
                                                    (optional)
                                                </span>
                                            )}
                                        </Label>
                                        <Select
                                            value={rating || undefined}
                                            onValueChange={setRating}
                                        >
                                            <SelectTrigger
                                                id={`${idPrefix}-rating`}
                                                className="w-full"
                                                aria-invalid={Boolean(
                                                    errors.rating,
                                                )}
                                            >
                                                <SelectValue placeholder="Select an official rating" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {!requiresRating && (
                                                    <SelectItem value="none">
                                                        No official rating
                                                    </SelectItem>
                                                )}
                                                {ratingOptions
                                                    .filter(
                                                        (option) =>
                                                            presidentialAction !==
                                                                'override' ||
                                                            option.value !==
                                                                sourceRating,
                                                    )
                                                    .map((option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={String(
                                                                option.value,
                                                            )}
                                                        >
                                                            {option.value} —{' '}
                                                            {option.label}
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs leading-5 text-muted-foreground">
                                            {requiresRating
                                                ? 'Select one rating from 0 to 3. Remarks are required for ratings 0 and 1.'
                                                : 'A rating is optional when rejecting a submission. Reviewer remarks are required.'}
                                        </p>
                                        <InputError message={errors.rating} />
                                    </div>
                                )}

                            <div className="grid gap-2">
                                <Label htmlFor={`${idPrefix}-remarks`}>
                                    Reviewer remarks
                                    {!requiresRemarks && (
                                        <span className="font-normal text-muted-foreground">
                                            {' '}
                                            (optional)
                                        </span>
                                    )}
                                </Label>
                                <Textarea
                                    id={`${idPrefix}-remarks`}
                                    name="remarks"
                                    className="min-h-32"
                                    maxLength={5000}
                                    required={requiresRemarks}
                                    placeholder={
                                        decision ===
                                        'request_additional_evidence'
                                            ? 'Describe the additional evidence the organizational unit must provide.'
                                            : decision === 'request_revision'
                                              ? 'Describe the corrections required before resubmission.'
                                              : 'Record the basis for this evaluation.'
                                    }
                                    aria-invalid={Boolean(errors.remarks)}
                                />
                                <InputError message={errors.remarks} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => updateOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    variant={content.variant}
                                    disabled={processing}
                                >
                                    {processing ? <Spinner /> : <SubmitIcon />}
                                    {processing
                                        ? content.processingLabel
                                        : content.submitLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
