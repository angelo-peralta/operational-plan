import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    CheckCircle2,
    FileText,
    LockKeyhole,
    Pencil,
    Plus,
    RotateCcw,
    Send,
    Trash2,
    Undo2,
} from 'lucide-react';
import { useState } from 'react';
import { KeyResultAreaDialog } from '@/components/operational-plans/key-result-area-dialog';
import { PlanHeaderDialog } from '@/components/operational-plans/plan-header-dialog';
import { PlanItemDialog } from '@/components/operational-plans/plan-item-dialog';
import { WorkflowActionDialog } from '@/components/operational-plans/workflow-action-dialog';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { home } from '@/routes';
import { index, official, show } from '@/routes/operational-plans';
import { reorder as reorderKeyResultAreas } from '@/routes/operational-plans/key-result-areas';
import {
    destroy as destroyPlanItem,
    reorder as reorderPlanItems,
} from '@/routes/operational-plans/key-result-areas/plan-items';
import type {
    CoAccountableDepartment,
    DepartmentAccountableUser,
    KeyResultArea,
    OperationalPlan,
    OperationalPlanPermissions,
    OperationalPlanStatus,
    PlanItem,
    SelectOption,
    TargetOperator,
} from '@/types';

type Props = {
    operationalPlan: OperationalPlan;
    permissions: OperationalPlanPermissions;
    accountableUsers: DepartmentAccountableUser[];
    coAccountableDepartments: CoAccountableDepartment[];
    targetOperators: SelectOption<TargetOperator>[];
};

const operatorLabels: Record<TargetOperator, string> = {
    equals: 'Equals',
    at_least: 'At least',
    at_most: 'At most',
    percentage_at_least: 'Percentage at least',
    percentage_at_most: 'Percentage at most',
    zero_tolerance: 'Zero tolerance',
    qualitative: 'Qualitative',
};

function reorderedIds(
    ids: number[],
    currentIndex: number,
    direction: -1 | 1,
): number[] {
    const destinationIndex = currentIndex + direction;

    if (destinationIndex < 0 || destinationIndex >= ids.length) {
        return ids;
    }

    const nextIds = [...ids];
    [nextIds[currentIndex], nextIds[destinationIndex]] = [
        nextIds[destinationIndex],
        nextIds[currentIndex],
    ];

    return nextIds;
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function accountabilityLabel(operationalPlan: OperationalPlan): string {
    return (
        operationalPlan.accountableUser?.name ??
        operationalPlan.accountableName ??
        operationalPlan.accountablePosition ??
        'Not assigned'
    );
}

function DeletePlanItemDialog({
    teamSlug,
    operationalPlanId,
    keyResultAreaId,
    item,
}: {
    teamSlug: string;
    operationalPlanId: number;
    keyResultAreaId: number;
    item: PlanItem;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm">
                    <Trash2 />
                    Remove
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Remove Plan Item?</DialogTitle>
                    <DialogDescription>
                        This removes the draft item beginning “
                        {item.kpiTargetText.slice(0, 90)}
                        {item.kpiTargetText.length > 90 ? '…' : ''}”. This
                        action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...destroyPlanItem.form([
                        teamSlug,
                        operationalPlanId,
                        keyResultAreaId,
                        item.id,
                    ])}
                    errorBag={`deletePlanItem${item.id}`}
                    options={{ preserveScroll: true }}
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                >
                    {({ processing }) => (
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                {processing ? 'Removing...' : 'Remove item'}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function PlanItemCard({
    item,
    itemIndex,
    keyResultArea,
    operationalPlan,
    teamSlug,
    canEdit,
    coAccountableDepartments,
    targetOperators,
}: {
    item: PlanItem;
    itemIndex: number;
    keyResultArea: KeyResultArea;
    operationalPlan: OperationalPlan;
    teamSlug: string;
    canEdit: boolean;
    coAccountableDepartments: CoAccountableDepartment[];
    targetOperators: SelectOption<TargetOperator>[];
}) {
    const linkedUnits = item.coAccountableDepartments.map(
        (department) => department.code ?? department.name,
    );
    const coAccountableUnits = [
        ...linkedUnits,
        ...item.manualCoAccountableUnits,
    ];
    const itemIds = keyResultArea.planItems.map((planItem) => planItem.id);

    return (
        <article className="grid gap-5 rounded-xl border bg-background p-4 shadow-xs md:p-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0 space-y-1">
                    <p className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                        Plan Item {itemIndex + 1}
                    </p>
                    <h4 className="leading-6 font-semibold">
                        {item.kpiTargetText}
                    </h4>
                </div>

                {canEdit && (
                    <div className="flex shrink-0 flex-wrap items-center gap-1">
                        {keyResultArea.planItems.length > 1 && (
                            <>
                                <Form
                                    {...reorderPlanItems.form([
                                        teamSlug,
                                        operationalPlan.id,
                                        keyResultArea.id,
                                    ])}
                                    errorBag={`reorderPlanItems${keyResultArea.id}`}
                                    options={{ preserveScroll: true }}
                                    disableWhileProcessing
                                >
                                    {({ processing }) => (
                                        <>
                                            {reorderedIds(
                                                itemIds,
                                                itemIndex,
                                                -1,
                                            ).map((id) => (
                                                <input
                                                    key={id}
                                                    type="hidden"
                                                    name="ordered_ids[]"
                                                    value={id}
                                                />
                                            ))}
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="icon"
                                                disabled={
                                                    processing ||
                                                    itemIndex === 0
                                                }
                                                aria-label={`Move Plan Item ${itemIndex + 1} up`}
                                            >
                                                <ArrowUp />
                                            </Button>
                                        </>
                                    )}
                                </Form>
                                <Form
                                    {...reorderPlanItems.form([
                                        teamSlug,
                                        operationalPlan.id,
                                        keyResultArea.id,
                                    ])}
                                    errorBag={`reorderPlanItems${keyResultArea.id}`}
                                    options={{ preserveScroll: true }}
                                    disableWhileProcessing
                                >
                                    {({ processing }) => (
                                        <>
                                            {reorderedIds(
                                                itemIds,
                                                itemIndex,
                                                1,
                                            ).map((id) => (
                                                <input
                                                    key={id}
                                                    type="hidden"
                                                    name="ordered_ids[]"
                                                    value={id}
                                                />
                                            ))}
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="icon"
                                                disabled={
                                                    processing ||
                                                    itemIndex ===
                                                        itemIds.length - 1
                                                }
                                                aria-label={`Move Plan Item ${itemIndex + 1} down`}
                                            >
                                                <ArrowDown />
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </>
                        )}
                        <PlanItemDialog
                            mode="edit"
                            teamSlug={teamSlug}
                            operationalPlanId={operationalPlan.id}
                            keyResultAreaId={keyResultArea.id}
                            item={item}
                            departments={coAccountableDepartments}
                            targetOperators={targetOperators}
                            trigger={
                                <Button variant="ghost" size="sm">
                                    <Pencil />
                                    Edit
                                </Button>
                            }
                        />
                        <DeletePlanItemDialog
                            teamSlug={teamSlug}
                            operationalPlanId={operationalPlan.id}
                            keyResultAreaId={keyResultArea.id}
                            item={item}
                        />
                    </div>
                )}
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-1.5">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Objective
                    </p>
                    <p className="text-sm leading-6 whitespace-pre-line">
                        {item.objective}
                    </p>
                </div>
                <div className="space-y-1.5">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Strategy
                    </p>
                    <p className="text-sm leading-6 whitespace-pre-line">
                        {item.strategy ?? '—'}
                    </p>
                </div>
            </div>

            {(item.targetOperator ||
                item.targetValue ||
                item.targetUnit ||
                item.targetFrequency) && (
                <div className="flex flex-wrap gap-2">
                    {item.targetOperator && (
                        <Badge variant="outline">
                            {operatorLabels[item.targetOperator]}
                        </Badge>
                    )}
                    {item.targetValue && (
                        <Badge variant="outline">
                            Value: {item.targetValue}
                        </Badge>
                    )}
                    {item.targetUnit && (
                        <Badge variant="outline">Unit: {item.targetUnit}</Badge>
                    )}
                    {item.targetFrequency && (
                        <Badge variant="outline">
                            Frequency: {item.targetFrequency}
                        </Badge>
                    )}
                </div>
            )}

            <Separator />

            <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Unit Co-Accountable
                    </p>
                    {coAccountableUnits.length > 0 ? (
                        <div className="flex flex-wrap gap-1.5">
                            {coAccountableUnits.map((unit, index) => (
                                <Badge
                                    key={`${unit}-${index}`}
                                    variant="secondary"
                                >
                                    {unit}
                                </Badge>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">—</p>
                    )}
                </div>
                <div className="space-y-2">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Resources Needed
                    </p>
                    <p className="text-sm leading-6 whitespace-pre-line">
                        {item.resourcesNeeded ?? '—'}
                    </p>
                </div>
                <div className="space-y-2">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Documentary Evidence Requirements
                    </p>
                    {item.documentaryEvidenceRequirements.length > 0 ? (
                        <ul className="grid list-disc gap-1 pl-4 text-sm leading-5">
                            {item.documentaryEvidenceRequirements.map(
                                (requirement, index) => (
                                    <li key={`${requirement}-${index}`}>
                                        {requirement}
                                    </li>
                                ),
                            )}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">—</p>
                    )}
                </div>
            </div>
        </article>
    );
}

function KeyResultAreaCard({
    keyResultArea,
    kraIndex,
    operationalPlan,
    permissions,
    teamSlug,
    coAccountableDepartments,
    targetOperators,
}: {
    keyResultArea: KeyResultArea;
    kraIndex: number;
    operationalPlan: OperationalPlan;
    permissions: OperationalPlanPermissions;
    teamSlug: string;
    coAccountableDepartments: CoAccountableDepartment[];
    targetOperators: SelectOption<TargetOperator>[];
}) {
    const kraIds = operationalPlan.keyResultAreas.map((kra) => kra.id);

    return (
        <Card className="gap-5">
            <CardHeader className="border-b pb-5">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0 space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {keyResultArea.code ?? `KRA ${kraIndex + 1}`}
                            </Badge>
                            <CardDescription>
                                {keyResultArea.planItems.length}{' '}
                                {keyResultArea.planItems.length === 1
                                    ? 'Plan Item'
                                    : 'Plan Items'}
                            </CardDescription>
                        </div>
                        <CardTitle className="text-xl leading-7">
                            {keyResultArea.name}
                        </CardTitle>
                        {keyResultArea.description && (
                            <p className="max-w-3xl text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                {keyResultArea.description}
                            </p>
                        )}
                    </div>

                    {permissions.updatePlan && (
                        <div className="flex shrink-0 flex-wrap items-center gap-1">
                            {permissions.reorderKeyResultAreas &&
                                kraIds.length > 1 && (
                                    <>
                                        <Form
                                            {...reorderKeyResultAreas.form([
                                                teamSlug,
                                                operationalPlan.id,
                                            ])}
                                            errorBag="reorderKeyResultAreas"
                                            options={{ preserveScroll: true }}
                                            disableWhileProcessing
                                        >
                                            {({ processing }) => (
                                                <>
                                                    {reorderedIds(
                                                        kraIds,
                                                        kraIndex,
                                                        -1,
                                                    ).map((id) => (
                                                        <input
                                                            key={id}
                                                            type="hidden"
                                                            name="ordered_ids[]"
                                                            value={id}
                                                        />
                                                    ))}
                                                    <Button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="icon"
                                                        disabled={
                                                            processing ||
                                                            kraIndex === 0
                                                        }
                                                        aria-label={`Move ${keyResultArea.name} up`}
                                                    >
                                                        <ArrowUp />
                                                    </Button>
                                                </>
                                            )}
                                        </Form>
                                        <Form
                                            {...reorderKeyResultAreas.form([
                                                teamSlug,
                                                operationalPlan.id,
                                            ])}
                                            errorBag="reorderKeyResultAreas"
                                            options={{ preserveScroll: true }}
                                            disableWhileProcessing
                                        >
                                            {({ processing }) => (
                                                <>
                                                    {reorderedIds(
                                                        kraIds,
                                                        kraIndex,
                                                        1,
                                                    ).map((id) => (
                                                        <input
                                                            key={id}
                                                            type="hidden"
                                                            name="ordered_ids[]"
                                                            value={id}
                                                        />
                                                    ))}
                                                    <Button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="icon"
                                                        disabled={
                                                            processing ||
                                                            kraIndex ===
                                                                kraIds.length -
                                                                    1
                                                        }
                                                        aria-label={`Move ${keyResultArea.name} down`}
                                                    >
                                                        <ArrowDown />
                                                    </Button>
                                                </>
                                            )}
                                        </Form>
                                    </>
                                )}
                            <KeyResultAreaDialog
                                mode="edit"
                                teamSlug={teamSlug}
                                operationalPlanId={operationalPlan.id}
                                keyResultArea={keyResultArea}
                                trigger={
                                    <Button variant="ghost" size="sm">
                                        <Pencil />
                                        Edit KRA
                                    </Button>
                                }
                            />
                        </div>
                    )}
                </div>
            </CardHeader>

            <CardContent className="grid gap-4">
                {keyResultArea.planItems.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-8 text-center">
                        <p className="font-medium">No Plan Items yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            A KRA needs at least one Plan Item before the plan
                            can be submitted.
                        </p>
                    </div>
                ) : (
                    keyResultArea.planItems.map((item, itemIndex) => (
                        <PlanItemCard
                            key={item.id}
                            item={item}
                            itemIndex={itemIndex}
                            keyResultArea={keyResultArea}
                            operationalPlan={operationalPlan}
                            teamSlug={teamSlug}
                            canEdit={permissions.updatePlan}
                            coAccountableDepartments={coAccountableDepartments}
                            targetOperators={targetOperators}
                        />
                    ))
                )}

                {permissions.updatePlan && (
                    <PlanItemDialog
                        mode="create"
                        teamSlug={teamSlug}
                        operationalPlanId={operationalPlan.id}
                        keyResultAreaId={keyResultArea.id}
                        departments={coAccountableDepartments}
                        targetOperators={targetOperators}
                        trigger={
                            <Button variant="outline" className="w-full">
                                <Plus />
                                Add Plan Item to{' '}
                                {keyResultArea.code ?? `KRA ${kraIndex + 1}`}
                            </Button>
                        }
                    />
                )}
            </CardContent>
        </Card>
    );
}

function ReadOnlyNotice({
    academicYearStatus,
    planStatus,
}: {
    academicYearStatus: string;
    planStatus: OperationalPlanStatus;
}) {
    const message =
        academicYearStatus === 'closed' || academicYearStatus === 'archived'
            ? 'The selected Academic Year is closed. This plan remains available for review but cannot be changed.'
            : planStatus === 'submitted'
              ? 'This plan is awaiting reviewer action. Editing remains locked until it is returned for revision.'
              : planStatus === 'approved'
                ? 'This plan is approved and read-only. An administrator can reopen it for revision when required.'
                : planStatus === 'closed'
                  ? 'This plan is closed and read-only. An administrator can reopen it with a recorded reason.'
                  : 'Your current role can view this plan but cannot change it.';

    return (
        <Alert>
            <LockKeyhole />
            <AlertTitle>Read-only plan</AlertTitle>
            <AlertDescription>{message}</AlertDescription>
        </Alert>
    );
}

export default function OperationalPlanShow({
    operationalPlan,
    permissions,
    accountableUsers,
    coAccountableDepartments,
    targetOperators,
}: Props) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return (
            <div className="p-6 text-sm text-muted-foreground">
                Select a team before viewing this Operational Plan.
            </div>
        );
    }

    return (
        <>
            <Head
                title={`${operationalPlan.department.code ?? operationalPlan.department.name} Operational Plan`}
            />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div className="min-w-0 space-y-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusBadge
                                status={operationalPlan.status}
                                label={operationalPlan.statusLabel}
                            />
                            <Badge variant="outline">
                                {operationalPlan.academicYear.name}
                            </Badge>
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
                                {operationalPlan.department.name}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Operational Plan structured editor
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={index(currentTeam.slug)}>
                                <ArrowLeft />
                                All plans
                            </Link>
                        </Button>
                        {permissions.viewOfficial && (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={official([
                                        currentTeam.slug,
                                        operationalPlan.id,
                                    ])}
                                >
                                    <FileText />
                                    Print / Official View
                                </Link>
                            </Button>
                        )}
                        {permissions.updatePlan && (
                            <PlanHeaderDialog
                                teamSlug={currentTeam.slug}
                                operationalPlan={operationalPlan}
                                accountableUsers={accountableUsers}
                            />
                        )}
                        {permissions.submitPlan && (
                            <WorkflowActionDialog
                                action="submit"
                                teamSlug={currentTeam.slug}
                                operationalPlanId={operationalPlan.id}
                                trigger={
                                    <Button size="sm">
                                        <Send />
                                        Submit plan
                                    </Button>
                                }
                            />
                        )}
                        {permissions.approvePlan && (
                            <WorkflowActionDialog
                                action="approve"
                                teamSlug={currentTeam.slug}
                                operationalPlanId={operationalPlan.id}
                                trigger={
                                    <Button size="sm">
                                        <CheckCircle2 />
                                        Approve
                                    </Button>
                                }
                            />
                        )}
                        {permissions.returnPlan && (
                            <WorkflowActionDialog
                                action="return"
                                teamSlug={currentTeam.slug}
                                operationalPlanId={operationalPlan.id}
                                trigger={
                                    <Button variant="destructive" size="sm">
                                        <Undo2 />
                                        Return for revision
                                    </Button>
                                }
                            />
                        )}
                        {permissions.closePlan && (
                            <WorkflowActionDialog
                                action="close"
                                teamSlug={currentTeam.slug}
                                operationalPlanId={operationalPlan.id}
                                trigger={
                                    <Button variant="outline" size="sm">
                                        <LockKeyhole />
                                        Close plan
                                    </Button>
                                }
                            />
                        )}
                        {permissions.reopenPlan && (
                            <WorkflowActionDialog
                                action="reopen"
                                teamSlug={currentTeam.slug}
                                operationalPlanId={operationalPlan.id}
                                trigger={
                                    <Button variant="outline" size="sm">
                                        <RotateCcw />
                                        Reopen for revision
                                    </Button>
                                }
                            />
                        )}
                    </div>
                </div>

                {!permissions.updatePlan && (
                    <ReadOnlyNotice
                        academicYearStatus={operationalPlan.academicYear.status}
                        planStatus={operationalPlan.status}
                    />
                )}

                {operationalPlan.latestReturnRemarks &&
                    operationalPlan.status === 'returned' && (
                        <Alert className="border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100">
                            <AlertTriangle />
                            <AlertTitle>Reviewer return remarks</AlertTitle>
                            <AlertDescription className="text-rose-800 dark:text-rose-200">
                                {operationalPlan.latestReturnRemarks}
                            </AlertDescription>
                        </Alert>
                    )}

                <Card>
                    <CardHeader>
                        <CardTitle>Plan details</CardTitle>
                        <CardDescription>
                            Header information used in the official plan.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5 md:grid-cols-3">
                        <div className="space-y-1.5">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Academic Year
                            </p>
                            <p className="font-medium">
                                {operationalPlan.academicYear.name}
                            </p>
                            <StatusBadge
                                status={operationalPlan.academicYear.status}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Office
                            </p>
                            <p className="font-medium">
                                {operationalPlan.department.name}
                            </p>
                            {operationalPlan.department.code && (
                                <p className="font-mono text-xs text-muted-foreground">
                                    {operationalPlan.department.code}
                                </p>
                            )}
                        </div>
                        <div className="space-y-1.5">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Accountable
                            </p>
                            <p className="font-medium">
                                {accountabilityLabel(operationalPlan)}
                            </p>
                            {operationalPlan.accountablePosition && (
                                <p className="text-xs text-muted-foreground">
                                    {operationalPlan.accountablePosition}
                                </p>
                            )}
                        </div>
                        <div className="space-y-1.5 md:col-span-3">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Goal
                            </p>
                            <p className="text-sm leading-6 whitespace-pre-line">
                                {operationalPlan.goal}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <section className="grid gap-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 className="text-xl font-semibold">
                                Key Result Areas
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {operationalPlan.keyResultAreaCount}{' '}
                                {operationalPlan.keyResultAreaCount === 1
                                    ? 'KRA'
                                    : 'KRAs'}{' '}
                                with {operationalPlan.planItemCount} Plan Items
                            </p>
                        </div>
                        {permissions.createKeyResultArea && (
                            <KeyResultAreaDialog
                                mode="create"
                                teamSlug={currentTeam.slug}
                                operationalPlanId={operationalPlan.id}
                                trigger={
                                    <Button>
                                        <Plus />
                                        Add KRA
                                    </Button>
                                }
                            />
                        )}
                    </div>

                    {operationalPlan.keyResultAreas.length === 0 ? (
                        <Card className="border-dashed">
                            <CardContent className="py-10 text-center">
                                <p className="font-medium">
                                    No Key Result Areas yet
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Add the first KRA, then create its Plan
                                    Items.
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        operationalPlan.keyResultAreas.map(
                            (keyResultArea, kraIndex) => (
                                <KeyResultAreaCard
                                    key={keyResultArea.id}
                                    keyResultArea={keyResultArea}
                                    kraIndex={kraIndex}
                                    operationalPlan={operationalPlan}
                                    permissions={permissions}
                                    teamSlug={currentTeam.slug}
                                    coAccountableDepartments={
                                        coAccountableDepartments
                                    }
                                    targetOperators={targetOperators}
                                />
                            ),
                        )
                    )}
                </section>

                <Card>
                    <CardHeader>
                        <CardTitle>Workflow history</CardTitle>
                        <CardDescription>
                            Append-only record of submissions, approvals,
                            returns, closures, and reopenings.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4">
                            {operationalPlan.statusHistory.map(
                                (history, index) => (
                                    <div
                                        key={history.id}
                                        className="relative grid gap-1 border-l-2 pl-5"
                                    >
                                        <span className="absolute top-1.5 -left-[5px] size-2 rounded-full bg-primary" />
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">
                                                {history.fromStatus
                                                    ? `${history.fromStatus.replaceAll('_', ' ')} → ${history.toStatusLabel}`
                                                    : `Created as ${history.toStatusLabel}`}
                                            </p>
                                            {index === 0 && (
                                                <Badge variant="secondary">
                                                    Latest
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {history.actor.name} ·{' '}
                                            {formatDateTime(history.createdAt)}
                                        </p>
                                        {history.remarks && (
                                            <p className="mt-1 rounded-lg bg-muted/50 p-3 text-sm leading-5 whitespace-pre-line">
                                                {history.remarks}
                                            </p>
                                        )}
                                    </div>
                                ),
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

OperationalPlanShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    operationalPlan: OperationalPlan;
}) => ({
    breadcrumbs: [
        {
            title: 'Operational Plans',
            href: props.currentTeam ? index(props.currentTeam.slug) : home(),
        },
        {
            title:
                props.operationalPlan.department.code ??
                props.operationalPlan.department.name,
            href: props.currentTeam
                ? show([props.currentTeam.slug, props.operationalPlan.id])
                : home(),
        },
    ],
});
