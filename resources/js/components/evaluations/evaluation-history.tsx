import { ChevronDown, History, MessageSquareWarning } from 'lucide-react';
import { useState } from 'react';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { EvaluationHistoryEntry } from '@/types';

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export function LatestReviewFeedback({
    accomplishmentStatus,
    history,
}: {
    accomplishmentStatus: string;
    history: EvaluationHistoryEntry[];
}) {
    const latestEvaluation = history[0];

    if (
        accomplishmentStatus !== 'returned' ||
        !latestEvaluation ||
        latestEvaluation.resultingStatus !== 'returned'
    ) {
        return null;
    }

    return (
        <div className="grid gap-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
            <div className="flex items-start gap-2">
                <MessageSquareWarning className="mt-0.5 size-4 shrink-0" />
                <div className="min-w-0">
                    <p className="text-sm font-medium">
                        {latestEvaluation.decision === 'request_revision'
                            ? 'Reviewer requested revisions'
                            : 'Reviewer requested additional evidence'}
                    </p>
                    <p className="mt-1 text-sm leading-6 whitespace-pre-wrap">
                        {latestEvaluation.remarks}
                    </p>
                </div>
            </div>
            <p className="pl-6 text-xs text-amber-800 dark:text-amber-200">
                {latestEvaluation.reviewer.name} ·{' '}
                <time dateTime={latestEvaluation.reviewedAt}>
                    {formatDateTime(latestEvaluation.reviewedAt)}
                </time>
            </p>
        </div>
    );
}

export function EvaluationHistory({
    history,
}: {
    history: EvaluationHistoryEntry[];
}) {
    const [open, setOpen] = useState(false);

    if (history.length === 0) {
        return null;
    }

    return (
        <Collapsible
            open={open}
            onOpenChange={setOpen}
            className="border-t pt-3"
        >
            <CollapsibleTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    className="h-auto w-full justify-between px-0 py-1 hover:bg-transparent"
                >
                    <span className="flex items-center gap-2">
                        <History /> Review history ({history.length})
                    </span>
                    <ChevronDown
                        className={`transition-transform ${open ? 'rotate-180' : ''}`}
                    />
                </Button>
            </CollapsibleTrigger>
            <CollapsibleContent className="pt-3">
                <p className="mb-4 text-xs leading-5 text-muted-foreground">
                    Every decision remains linked to the exact submitted
                    revision that was reviewed.
                </p>
                <ol className="grid gap-4">
                    {history.map((evaluation) => (
                        <li
                            key={evaluation.id}
                            className="relative grid gap-2 border-l-2 pl-5"
                        >
                            <span className="absolute top-1.5 -left-[5px] size-2 rounded-full bg-primary" />
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="text-sm font-medium">
                                    Revision {evaluation.submissionRevision}
                                </p>
                                <StatusBadge
                                    status={evaluation.resultingStatus}
                                    label={evaluation.decisionLabel}
                                />
                                {evaluation.rating !== null ? (
                                    <Badge variant="secondary">
                                        {evaluation.rating} —{' '}
                                        {evaluation.ratingLabel}
                                    </Badge>
                                ) : (
                                    <Badge variant="outline">No rating</Badge>
                                )}
                                <Badge variant="outline">
                                    {evaluation.authority.label}
                                </Badge>
                                {evaluation.isOverride && (
                                    <Badge variant="destructive">
                                        Presidential override
                                    </Badge>
                                )}
                                {evaluation.isEffective && (
                                    <Badge variant="secondary">Effective</Badge>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {evaluation.reviewer.name}
                                {evaluation.authority.name
                                    ? ` · ${evaluation.authority.name}`
                                    : ''}{' '}
                                ·{' '}
                                <time dateTime={evaluation.reviewedAt}>
                                    {formatDateTime(evaluation.reviewedAt)}
                                </time>
                            </p>
                            {evaluation.remarks && (
                                <p className="rounded-lg bg-muted/50 p-3 text-sm leading-6 whitespace-pre-wrap">
                                    {evaluation.remarks}
                                </p>
                            )}
                        </li>
                    ))}
                </ol>
            </CollapsibleContent>
        </Collapsible>
    );
}
