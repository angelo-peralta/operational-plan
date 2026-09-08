import { Form } from '@inertiajs/react';
import { MessageSquareText } from 'lucide-react';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    store as storeRemark,
    update as updateRemark,
} from '@/routes/operational-plans/remarks';
import type { EvaluationQueueItem } from '@/types';

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export function EvaluationRemarks({
    teamSlug,
    item,
}: {
    teamSlug: string;
    item: EvaluationQueueItem;
}) {
    const presidentialReview = item.presidentialReview;

    return (
        <section className="grid gap-3 border-t pt-4">
            <div className="flex items-center gap-2">
                <MessageSquareText className="size-4" />
                <h3 className="font-medium">
                    Field-level remarks ({item.remarks.length})
                </h3>
            </div>

            {presidentialReview && (
                <Form
                    {...storeRemark.form([teamSlug, item.operationalPlan.id])}
                    errorBag={`presidentialEvaluationRemark${presidentialReview.sourceEvaluationId}`}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['content']}
                    className="grid gap-3 rounded-lg border bg-muted/20 p-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <input
                                type="hidden"
                                name="target_type"
                                value="evaluation"
                            />
                            <input
                                type="hidden"
                                name="target_id"
                                value={presidentialReview.sourceEvaluationId}
                            />
                            <input
                                type="hidden"
                                name="field_key"
                                value="evaluation"
                            />
                            <input
                                type="hidden"
                                name="reporting_period_id"
                                value={item.reportingPeriod.id}
                            />
                            <input
                                type="hidden"
                                name="category"
                                value="general"
                            />
                            <Textarea
                                name="content"
                                required
                                maxLength={5000}
                                placeholder="Add a Presidential remark to the VP evaluation…"
                            />
                            <InputError
                                message={
                                    errors.target_id ??
                                    errors.field_key ??
                                    errors.reporting_period_id ??
                                    errors.content
                                }
                            />
                            <Button
                                type="submit"
                                size="sm"
                                className="justify-self-end"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Add Presidential remark
                            </Button>
                        </>
                    )}
                </Form>
            )}

            {item.remarks.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No field-level remarks have been recorded.
                </p>
            ) : (
                <div className="grid gap-2">
                    {item.remarks.map((remark) => (
                        <div
                            key={remark.id}
                            className="grid gap-2 rounded-lg border bg-background p-3"
                        >
                            <div className="flex flex-wrap items-center gap-2">
                                <a
                                    href={`#${remark.anchor}`}
                                    className="text-sm font-medium underline-offset-4 hover:underline"
                                >
                                    {remark.targetLabel}
                                </a>
                                {remark.fieldKey && (
                                    <Badge variant="outline">
                                        {remark.fieldKey.replaceAll('_', ' ')}
                                    </Badge>
                                )}
                                <StatusBadge
                                    status={remark.status}
                                    label={remark.statusLabel}
                                />
                            </div>
                            <p className="text-sm leading-6 whitespace-pre-wrap">
                                {remark.content}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {remark.author.authority}: {remark.author.name}{' '}
                                · {formatDateTime(remark.createdAt)}
                            </p>
                            {(remark.canAddress || remark.canResolve) && (
                                <Form
                                    {...updateRemark.form([
                                        teamSlug,
                                        item.operationalPlan.id,
                                        remark.id,
                                    ])}
                                    options={{ preserveScroll: true }}
                                    className="flex justify-end"
                                >
                                    <input
                                        type="hidden"
                                        name="status"
                                        value={
                                            remark.canAddress
                                                ? 'addressed'
                                                : 'resolved'
                                        }
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="outline"
                                    >
                                        {remark.canAddress
                                            ? 'Mark addressed'
                                            : 'Resolve remark'}
                                    </Button>
                                </Form>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}
