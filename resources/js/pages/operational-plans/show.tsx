import { Form, Head, Link, router, usePage } from '@inertiajs/react';
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
    Upload,
    Undo2,
} from 'lucide-react';
import { useState } from 'react';
import { AccomplishmentDialog } from '@/components/monitoring/accomplishment-dialog';
import { EvidenceUploadDialog } from '@/components/monitoring/evidence-upload-dialog';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { home } from '@/routes';
import { index, official, show } from '@/routes/operational-plans';
import {
    destroy as destroyKeyResultArea,
    reorder as reorderKeyResultAreas,
} from '@/routes/operational-plans/key-result-areas';
import {
    destroy as destroyPlanItem,
    reorder as reorderPlanItems,
} from '@/routes/operational-plans/key-result-areas/plan-items';
import {
    store as storeRemark,
    update as updateRemark,
} from '@/routes/operational-plans/remarks';
import type {
    CoAccountableOrganizationalUnit,
    AccountableUserOption,
    KeyResultArea,
    OperationalPlan,
    OperationalPlanRemark,
    OperationalPlanPermissions,
    OperationalPlanReview,
    OperationalPlanReportingPeriod,
    OperationalPlanStatus,
    PlanItem,
    SelectOption,
    TargetOperator,
} from '@/types';

type Props = {
    operationalPlan: OperationalPlan;
    permissions: OperationalPlanPermissions;
    accountableUsers: AccountableUserOption[];
    coAccountableOrganizationalUnits: CoAccountableOrganizationalUnit[];
    targetOperators: SelectOption<TargetOperator>[];
    reportingPeriods: OperationalPlanReportingPeriod[];
    selectedReportingPeriod: OperationalPlanReportingPeriod | null;
    review: OperationalPlanReview;
    remarkOptions: { categories: string[]; fields: string[] };
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

function DeleteKeyResultAreaDialog({
    teamSlug,
    operationalPlanId,
    keyResultArea,
}: {
    teamSlug: string;
    operationalPlanId: number;
    keyResultArea: KeyResultArea;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm">
                    <Trash2 />
                    Remove KRA
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Remove Key Result Area?</DialogTitle>
                    <DialogDescription>
                        This removes “{keyResultArea.name}”. A KRA can only be
                        removed when it has no Plan Items or review remarks.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...destroyKeyResultArea.form([
                        teamSlug,
                        operationalPlanId,
                        keyResultArea.id,
                    ])}
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
                                {processing ? 'Removing...' : 'Remove KRA'}
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
    canReview,
    coAccountableOrganizationalUnits,
    targetOperators,
    reviewRemarks,
    remarkOptions,
    selectedReportingPeriod,
}: {
    item: PlanItem;
    itemIndex: number;
    keyResultArea: KeyResultArea;
    operationalPlan: OperationalPlan;
    teamSlug: string;
    canEdit: boolean;
    canReview: boolean;
    coAccountableOrganizationalUnits: CoAccountableOrganizationalUnit[];
    targetOperators: SelectOption<TargetOperator>[];
    selectedReportingPeriod: OperationalPlanReportingPeriod | null;
    reviewRemarks: OperationalPlanRemark[];
    remarkOptions: { categories: string[]; fields: string[] };
}) {
    const linkedUnits = item.coAccountableOrganizationalUnits.map(
        (organizationalUnit) =>
            organizationalUnit.code ?? organizationalUnit.name,
    );
    const coAccountableUnits = [
        ...linkedUnits,
        ...item.manualCoAccountableUnits,
    ];
    const itemIds = keyResultArea.planItems.map((planItem) => planItem.id);

    return (
        <tr id={`plan-item-${item.id}`} className="scroll-mt-6 align-top">
            <td className="w-12 px-4 py-4 text-sm font-semibold text-muted-foreground">
                {itemIndex + 1}
            </td>
            <td className="min-w-64 px-4 py-4">
                <p className="leading-6 font-semibold">{item.kpiTargetText}</p>
                <div className="mt-3 flex flex-wrap gap-2">
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
            </td>
            <td className="min-w-64 px-4 py-4 text-sm leading-6 whitespace-pre-line">
                {item.objective}
            </td>
            <td className="min-w-56 px-4 py-4 text-sm leading-6 whitespace-pre-line">
                {item.strategy ?? '—'}
            </td>
            <td className="min-w-64 px-4 py-4">
                <div className="space-y-3 text-sm">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Co-accountable units
                        </p>
                        {coAccountableUnits.length > 0 ? (
                            <div className="mt-1 flex flex-wrap gap-1.5">
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
                            <p className="mt-1 text-muted-foreground">—</p>
                        )}
                    </div>
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Resources
                        </p>
                        <p className="mt-1 leading-6 whitespace-pre-line">
                            {item.resourcesNeeded ?? '—'}
                        </p>
                    </div>
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Evidence requirements
                        </p>
                        {item.documentaryEvidenceRequirements.length > 0 ? (
                            <ul className="mt-1 grid list-disc gap-1 pl-4 leading-5">
                                {item.documentaryEvidenceRequirements.map(
                                    (requirement, index) => (
                                        <li key={`${requirement}-${index}`}>
                                            {requirement}
                                        </li>
                                    ),
                                )}
                            </ul>
                        ) : (
                            <p className="mt-1 text-muted-foreground">—</p>
                        )}
                    </div>
                </div>
            </td>
            <td className="min-w-80 px-4 py-4">
                <div className="grid gap-3">
                    {reviewRemarks.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No evaluation notes yet.
                        </p>
                    ) : (
                        reviewRemarks.map((remark) => (
                            <div
                                key={remark.id}
                                className="grid gap-2 rounded-lg border p-3"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <StatusBadge
                                        status={remark.status}
                                        label={remark.statusLabel}
                                    />
                                    {remark.fieldKey && (
                                        <Badge variant="outline">
                                            {remark.fieldKey.replaceAll(
                                                '_',
                                                ' ',
                                            )}
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-sm leading-5 whitespace-pre-line">
                                    {remark.content}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {remark.author.name} ·{' '}
                                    {formatDateTime(remark.createdAt)}
                                </p>
                                {(remark.canAddress || remark.canResolve) && (
                                    <Form
                                        {...updateRemark.form([
                                            teamSlug,
                                            operationalPlan.id,
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
                                                : 'Resolve'}
                                        </Button>
                                    </Form>
                                )}
                            </div>
                        ))
                    )}
                    {canReview && (
                        <Form
                            {...storeRemark.form([
                                teamSlug,
                                operationalPlan.id,
                            ])}
                            options={{ preserveScroll: true }}
                            resetOnSuccess
                            className="grid gap-2 rounded-lg border border-dashed p-3"
                        >
                            <input
                                type="hidden"
                                name="target_type"
                                value="plan_item"
                            />
                            <input
                                type="hidden"
                                name="target_id"
                                value={item.id}
                            />
                            <select
                                name="field_key"
                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                defaultValue=""
                            >
                                <option value="">General evaluation</option>
                                {remarkOptions.fields.map((field) => (
                                    <option key={field} value={field}>
                                        {field.replaceAll('_', ' ')}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="category"
                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                defaultValue="clarification"
                            >
                                {remarkOptions.categories.map((category) => (
                                    <option key={category} value={category}>
                                        {category.replaceAll('_', ' ')}
                                    </option>
                                ))}
                            </select>
                            <Textarea
                                name="content"
                                required
                                placeholder="Add an evaluation note for this item..."
                            />
                            <Button
                                type="submit"
                                size="sm"
                                className="justify-self-end"
                            >
                                Add evaluation
                            </Button>
                        </Form>
                    )}
                </div>
            </td>
            <td className="min-w-48 px-4 py-4">
                <div className="flex flex-wrap items-center gap-1">
                    {selectedReportingPeriod &&
                        item.permissions.createAccomplishment && (
                            <AccomplishmentDialog
                                teamSlug={teamSlug}
                                reportingPeriod={selectedReportingPeriod}
                                planItem={item}
                                trigger={
                                    <Button variant="outline" size="sm">
                                        <FileText />
                                        Report accomplishment
                                    </Button>
                                }
                            />
                        )}
                    {item.accomplishment?.permissions.uploadEvidence &&
                        selectedReportingPeriod && (
                            <EvidenceUploadDialog
                                teamSlug={teamSlug}
                                reportingPeriod={selectedReportingPeriod}
                                planItem={item}
                                accomplishment={item.accomplishment}
                                trigger={
                                    <Button variant="outline" size="sm">
                                        <Upload />
                                        Upload evidence
                                    </Button>
                                }
                            />
                        )}
                    {canEdit && (
                        <>
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
                                organizationalUnits={
                                    coAccountableOrganizationalUnits
                                }
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
                        </>
                    )}
                </div>
            </td>
        </tr>
    );
}

function KeyResultAreaCard({
    keyResultArea,
    kraIndex,
    operationalPlan,
    permissions,
    teamSlug,
    coAccountableOrganizationalUnits,
    targetOperators,
    review,
    remarkOptions,
    selectedReportingPeriod,
}: {
    keyResultArea: KeyResultArea;
    kraIndex: number;
    operationalPlan: OperationalPlan;
    permissions: OperationalPlanPermissions;
    teamSlug: string;
    coAccountableOrganizationalUnits: CoAccountableOrganizationalUnit[];
    targetOperators: SelectOption<TargetOperator>[];
    selectedReportingPeriod: OperationalPlanReportingPeriod | null;
    review: OperationalPlanReview;
    remarkOptions: { categories: string[]; fields: string[] };
}) {
    const kraIds = operationalPlan.keyResultAreas.map((kra) => kra.id);

    return (
        <Card id={`kra-${keyResultArea.id}`} className="scroll-mt-6 gap-5">
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
                            {permissions.updateKeyResultArea && (
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
                            )}
                            {permissions.deleteKeyResultArea && (
                                <DeleteKeyResultAreaDialog
                                    teamSlug={teamSlug}
                                    operationalPlanId={operationalPlan.id}
                                    keyResultArea={keyResultArea}
                                />
                            )}
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
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full min-w-[1500px] border-collapse text-left">
                            <thead className="bg-muted/40 text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="w-12 px-4 py-3 font-medium">
                                        #
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        KPI / Target
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Objective
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Strategy
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Resources and evidence
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Evaluation
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {keyResultArea.planItems.map(
                                    (item, itemIndex) => (
                                        <PlanItemCard
                                            key={item.id}
                                            item={item}
                                            itemIndex={itemIndex}
                                            keyResultArea={keyResultArea}
                                            operationalPlan={operationalPlan}
                                            teamSlug={teamSlug}
                                            canEdit={permissions.updatePlan}
                                            canReview={permissions.createRemark}
                                            coAccountableOrganizationalUnits={
                                                coAccountableOrganizationalUnits
                                            }
                                            targetOperators={targetOperators}
                                            selectedReportingPeriod={
                                                selectedReportingPeriod
                                            }
                                            reviewRemarks={review.remarks.filter(
                                                (remark) =>
                                                    remark.targetType ===
                                                        'plan_item' &&
                                                    remark.targetId === item.id,
                                            )}
                                            remarkOptions={remarkOptions}
                                        />
                                    ),
                                )}
                            </tbody>
                        </table>
                    </div>
                )}

                {permissions.updatePlan && (
                    <PlanItemDialog
                        mode="create"
                        teamSlug={teamSlug}
                        operationalPlanId={operationalPlan.id}
                        keyResultAreaId={keyResultArea.id}
                        organizationalUnits={coAccountableOrganizationalUnits}
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
    coAccountableOrganizationalUnits,
    targetOperators,
    review,
    remarkOptions,
    reportingPeriods,
    selectedReportingPeriod,
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
                title={`${operationalPlan.organizationalUnit.code ?? operationalPlan.organizationalUnit.name} Operational Plan`}
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
                                {operationalPlan.organizationalUnit.name}
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

                <Card id="plan-overview" className="scroll-mt-6">
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
                                {operationalPlan.organizationalUnit.name}
                            </p>
                            {operationalPlan.organizationalUnit.code && (
                                <p className="font-mono text-xs text-muted-foreground">
                                    {operationalPlan.organizationalUnit.code}
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

                {reportingPeriods.length > 0 && (
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="font-semibold">Reporting period</h2>
                            <p className="text-sm text-muted-foreground">
                                Choose the period for accomplishment evidence
                                uploads.
                            </p>
                        </div>
                        <select
                            className="h-9 rounded-md border bg-background px-3 text-sm"
                            value={selectedReportingPeriod?.id ?? ''}
                            onChange={(event) =>
                                router.get(
                                    show(
                                        [currentTeam.slug, operationalPlan.id],
                                        {
                                            query: {
                                                reporting_period:
                                                    event.target.value,
                                            },
                                        },
                                    ),
                                    {},
                                    {
                                        preserveScroll: true,
                                        preserveState: true,
                                    },
                                )
                            }
                        >
                            {reportingPeriods.map((period) => (
                                <option key={period.id} value={period.id}>
                                    {period.name} ({period.status})
                                </option>
                            ))}
                        </select>
                    </div>
                )}

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
                                    coAccountableOrganizationalUnits={
                                        coAccountableOrganizationalUnits
                                    }
                                    targetOperators={targetOperators}
                                    review={review}
                                    remarkOptions={remarkOptions}
                                    selectedReportingPeriod={
                                        selectedReportingPeriod
                                    }
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
                props.operationalPlan.organizationalUnit.code ??
                props.operationalPlan.organizationalUnit.name,
            href: props.currentTeam
                ? show([props.currentTeam.slug, props.operationalPlan.id])
                : home(),
        },
    ],
});
