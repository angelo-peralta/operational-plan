import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    ClipboardList,
    FileText,
    Plus,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
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
import { Textarea } from '@/components/ui/textarea';
import { home } from '@/routes';
import { index, official, show, store } from '@/routes/operational-plans';
import type {
    AccountableUserOption,
    DepartmentSummary,
    OperationalPlanSummary,
} from '@/types';

type Props = {
    operationalPlans: OperationalPlanSummary[];
    targetDepartments: DepartmentSummary[];
    accountableUsers: AccountableUserOption[];
};

function CreateOperationalPlanDialog({
    teamSlug,
    targetDepartments,
    accountableUsers,
}: {
    teamSlug: string;
    targetDepartments: DepartmentSummary[];
    accountableUsers: AccountableUserOption[];
}) {
    const { auth } = usePage().props;
    const [open, setOpen] = useState(false);
    const [selectedDepartmentId, setSelectedDepartmentId] = useState(
        targetDepartments[0]?.id.toString() ?? '',
    );
    const [selectedAccountableUserId, setSelectedAccountableUserId] =
        useState('none');
    const isSuperAdmin = auth.user.role === 'super_admin';
    const availableAccountableUsers = accountableUsers.filter(
        (user) => user.departmentId.toString() === selectedDepartmentId,
    );

    const resetSelection = (): void => {
        setSelectedDepartmentId(targetDepartments[0]?.id.toString() ?? '');
        setSelectedAccountableUserId('none');
    };

    const updateOpen = (nextOpen: boolean): void => {
        if (!nextOpen) {
            resetSelection();
        }

        setOpen(nextOpen);
    };

    return (
        <Dialog open={open} onOpenChange={updateOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus />
                    Create Operational Plan
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Create Operational Plan</DialogTitle>
                    <DialogDescription>
                        The selected Academic Year is applied by the server.
                        Each department can have one plan for that year.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...store.form(teamSlug)}
                    errorBag="createOperationalPlan"
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => {
                        resetSelection();
                        setOpen(false);
                    }}
                    className="grid gap-5"
                >
                    {({ errors, processing }) => (
                        <>
                            {isSuperAdmin && (
                                <div className="grid gap-2">
                                    <Label htmlFor="create-plan-department">
                                        Office / Department
                                    </Label>
                                    <Select
                                        name="department_id"
                                        value={selectedDepartmentId}
                                        onValueChange={(value) => {
                                            setSelectedDepartmentId(value);
                                            setSelectedAccountableUserId(
                                                'none',
                                            );
                                        }}
                                    >
                                        <SelectTrigger
                                            id="create-plan-department"
                                            className="w-full"
                                            aria-invalid={Boolean(
                                                errors.department_id,
                                            )}
                                        >
                                            <SelectValue placeholder="Select a department" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {targetDepartments.map(
                                                (department) => (
                                                    <SelectItem
                                                        key={department.id}
                                                        value={department.id.toString()}
                                                    >
                                                        {department.name}
                                                        {department.code
                                                            ? ` (${department.code})`
                                                            : ''}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.department_id}
                                    />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="create-plan-accountable-user">
                                    Accountable user
                                </Label>
                                <input
                                    type="hidden"
                                    name="accountable_user_id"
                                    value={
                                        selectedAccountableUserId === 'none'
                                            ? ''
                                            : selectedAccountableUserId
                                    }
                                />
                                <Select
                                    value={selectedAccountableUserId}
                                    onValueChange={setSelectedAccountableUserId}
                                >
                                    <SelectTrigger
                                        id="create-plan-accountable-user"
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            errors.accountable_user_id,
                                        )}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Use a manual accountable name
                                        </SelectItem>
                                        {availableAccountableUsers.map(
                                            (user) => (
                                                <SelectItem
                                                    key={user.id}
                                                    value={user.id.toString()}
                                                >
                                                    {user.name}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.accountable_user_id}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="create-plan-accountable-name">
                                        Accountable name
                                    </Label>
                                    <Input
                                        id="create-plan-accountable-name"
                                        name="accountable_name"
                                        placeholder="e.g. Juan Dela Cruz"
                                        aria-invalid={Boolean(
                                            errors.accountable_name,
                                        )}
                                    />
                                    <InputError
                                        message={errors.accountable_name}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="create-plan-accountable-position">
                                        Accountable position
                                    </Label>
                                    <Input
                                        id="create-plan-accountable-position"
                                        name="accountable_position"
                                        placeholder="e.g. Department Director"
                                        aria-invalid={Boolean(
                                            errors.accountable_position,
                                        )}
                                    />
                                    <InputError
                                        message={errors.accountable_position}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="create-plan-goal">Goal</Label>
                                <Textarea
                                    id="create-plan-goal"
                                    name="goal"
                                    className="min-h-32"
                                    required
                                    aria-invalid={Boolean(errors.goal)}
                                />
                                <InputError message={errors.goal} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => updateOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {processing ? 'Creating...' : 'Create plan'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function accountableLabel(plan: OperationalPlanSummary): string {
    return (
        plan.accountableUser?.name ??
        plan.accountableName ??
        plan.accountablePosition ??
        'Not assigned'
    );
}

export default function OperationalPlansIndex({
    operationalPlans,
    targetDepartments,
    accountableUsers,
}: Props) {
    const { currentTeam, selectedAcademicYear, auth } = usePage().props;

    if (!currentTeam) {
        return (
            <div className="p-6 text-sm text-muted-foreground">
                Select a team before viewing Operational Plans.
            </div>
        );
    }

    const canCreate =
        selectedAcademicYear?.status === 'open' &&
        targetDepartments.length > 0 &&
        auth.user.role !== 'reviewer';

    return (
        <>
            <Head title="Operational Plans" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Operational Plans"
                        description={
                            selectedAcademicYear
                                ? `Plans are isolated to ${selectedAcademicYear.name}.`
                                : 'Select an Academic Year to view or create plans.'
                        }
                    />
                    {canCreate && (
                        <CreateOperationalPlanDialog
                            teamSlug={currentTeam.slug}
                            targetDepartments={targetDepartments}
                            accountableUsers={accountableUsers}
                        />
                    )}
                </div>

                {!selectedAcademicYear ? (
                    <Card className="border-dashed">
                        <CardContent className="flex flex-col items-center gap-3 py-10 text-center">
                            <ClipboardList className="size-10 text-muted-foreground" />
                            <div className="space-y-1">
                                <p className="font-medium">
                                    No Academic Year selected
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Choose an Academic Year from the header to
                                    load its Operational Plans.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : operationalPlans.length === 0 ? (
                    <Card className="border-dashed">
                        <CardContent className="flex flex-col items-center gap-3 py-10 text-center">
                            <FileText className="size-10 text-muted-foreground" />
                            <div className="space-y-1">
                                <p className="font-medium">
                                    No Operational Plans for{' '}
                                    {selectedAcademicYear.name}
                                </p>
                                <p className="max-w-xl text-sm leading-6 text-muted-foreground">
                                    {selectedAcademicYear.status !== 'open'
                                        ? 'This Academic Year is not open, so new plans cannot be created.'
                                        : canCreate
                                          ? 'Create the first plan for an eligible department.'
                                          : 'There are no plans available for your current role and department.'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {operationalPlans.map((plan) => (
                            <Card key={plan.id} className="overflow-hidden">
                                <CardHeader className="flex-row items-start justify-between gap-4">
                                    <div className="min-w-0 space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <CardTitle className="leading-6">
                                                {plan.department.name}
                                            </CardTitle>
                                            {plan.department.code && (
                                                <span className="rounded-md bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground">
                                                    {plan.department.code}
                                                </span>
                                            )}
                                        </div>
                                        <CardDescription>
                                            {plan.academicYear.name}
                                        </CardDescription>
                                    </div>
                                    <StatusBadge
                                        status={plan.status}
                                        label={plan.statusLabel}
                                    />
                                </CardHeader>

                                <CardContent className="grid gap-4">
                                    <div className="grid gap-3 rounded-xl border bg-muted/20 p-4 text-sm sm:grid-cols-2">
                                        <div className="space-y-1">
                                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                Accountable
                                            </p>
                                            <p className="font-medium">
                                                {accountableLabel(plan)}
                                            </p>
                                            {plan.accountablePosition && (
                                                <p className="text-xs text-muted-foreground">
                                                    {plan.accountablePosition}
                                                </p>
                                            )}
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                Structure
                                            </p>
                                            <p className="font-medium">
                                                {plan.keyResultAreaCount}{' '}
                                                {plan.keyResultAreaCount === 1
                                                    ? 'KRA'
                                                    : 'KRAs'}{' '}
                                                · {plan.planItemCount}{' '}
                                                {plan.planItemCount === 1
                                                    ? 'Plan Item'
                                                    : 'Plan Items'}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="space-y-1.5">
                                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                            Goal
                                        </p>
                                        <p className="line-clamp-3 text-sm leading-6">
                                            {plan.goal}
                                        </p>
                                    </div>

                                    {plan.latestReturnRemarks && (
                                        <div className="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
                                            <p className="font-medium">
                                                Latest return remarks
                                            </p>
                                            <p className="mt-1 line-clamp-2 leading-5">
                                                {plan.latestReturnRemarks}
                                            </p>
                                        </div>
                                    )}
                                </CardContent>

                                <CardFooter className="flex flex-wrap justify-end gap-2 border-t pt-4">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={official([
                                                currentTeam.slug,
                                                plan.id,
                                            ])}
                                        >
                                            <FileText />
                                            Official View
                                        </Link>
                                    </Button>
                                    <Button size="sm" asChild>
                                        <Link
                                            href={show([
                                                currentTeam.slug,
                                                plan.id,
                                            ])}
                                        >
                                            Open plan
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}

                {selectedAcademicYear && (
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <Building2 className="size-4" />
                        Department access and Academic Year isolation are
                        enforced by the server.
                    </div>
                )}
            </div>
        </>
    );
}

OperationalPlansIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Operational Plans',
            href: props.currentTeam ? index(props.currentTeam.slug) : home(),
        },
    ],
});
