import { Form } from '@inertiajs/react';
import { MessageSquareText } from 'lucide-react';
import { useState } from 'react';
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
import type { Accomplishment } from '@/types';

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export function MonitoringRemarks({
    teamSlug,
    operationalPlanId,
    reportingPeriodId,
    accomplishment,
}: {
    teamSlug: string;
    operationalPlanId: number;
    reportingPeriodId: number;
    accomplishment: Accomplishment;
}) {
    const [target, setTarget] = useState(`accomplishment:${accomplishment.id}`);
    const [targetType, targetId] = target.split(':');
    const fieldKey =
        targetType === 'evidence' ? 'evidence' : 'accomplishment_text';

    return (
        <div className="grid gap-3 border-t pt-3">
            <div className="flex items-center gap-2">
                <MessageSquareText className="size-4" />
                <p className="text-sm font-medium">
                    Review remarks ({accomplishment.remarks.length})
                </p>
            </div>

            {accomplishment.permissions.createRemark && (
                <Form
                    {...storeRemark.form([teamSlug, operationalPlanId])}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['content']}
                    className="grid gap-3 rounded-lg border bg-background p-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <input
                                type="hidden"
                                name="target_type"
                                value={targetType}
                            />
                            <input
                                type="hidden"
                                name="target_id"
                                value={targetId}
                            />
                            <input
                                type="hidden"
                                name="field_key"
                                value={fieldKey}
                            />
                            <input
                                type="hidden"
                                name="reporting_period_id"
                                value={reportingPeriodId}
                            />
                            <div className="grid gap-3 sm:grid-cols-2">
                                <label className="grid gap-1 text-xs font-medium">
                                    Remark target
                                    <select
                                        className="h-9 rounded-md border bg-background px-3 text-sm"
                                        value={target}
                                        onChange={(event) =>
                                            setTarget(event.target.value)
                                        }
                                    >
                                        <option
                                            value={`accomplishment:${accomplishment.id}`}
                                        >
                                            Reported accomplishment
                                        </option>
                                        {accomplishment.evidence.map(
                                            (evidence) => (
                                                <option
                                                    key={evidence.id}
                                                    value={`evidence:${evidence.id}`}
                                                >
                                                    Evidence:{' '}
                                                    {evidence.title ||
                                                        evidence.originalFilename}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </label>
                                <label className="grid gap-1 text-xs font-medium">
                                    Category
                                    <select
                                        name="category"
                                        className="h-9 rounded-md border bg-background px-3 text-sm"
                                        defaultValue="clarification"
                                    >
                                        <option value="clarification">
                                            Clarification
                                        </option>
                                        <option value="correction">
                                            Correction
                                        </option>
                                        <option value="recommendation">
                                            Recommendation
                                        </option>
                                        <option value="approval_condition">
                                            Approval condition
                                        </option>
                                        <option value="general">General</option>
                                    </select>
                                </label>
                            </div>
                            <Textarea
                                name="content"
                                required
                                placeholder="Add a specific accomplishment or evidence remark…"
                            />
                            <InputError
                                message={
                                    errors.target_id ??
                                    errors.field_key ??
                                    errors.reporting_period_id ??
                                    errors.content ??
                                    errors.category
                                }
                            />
                            <Button
                                type="submit"
                                size="sm"
                                className="justify-self-end"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Add remark
                            </Button>
                        </>
                    )}
                </Form>
            )}

            {accomplishment.remarks.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No accomplishment or evidence remarks recorded.
                </p>
            ) : (
                <div className="grid gap-2">
                    {accomplishment.remarks.map((remark) => (
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
                                        operationalPlanId,
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
        </div>
    );
}
