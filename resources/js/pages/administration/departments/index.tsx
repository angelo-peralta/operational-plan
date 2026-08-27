import { Form, Head, usePage } from '@inertiajs/react';
import { Building2, CircleCheck, Pencil, Plus } from 'lucide-react';
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
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { index, store, update } from '@/routes/administration/departments';
import type { Department } from '@/types';

type Props = {
    departments: Department[];
};

type DepartmentFieldsProps = {
    errors: Partial<Record<string, string>>;
    idPrefix: string;
    defaults?: Department;
    isActive: boolean;
    onActiveChange: (isActive: boolean) => void;
};

function DepartmentFields({
    errors,
    idPrefix,
    defaults,
    isActive,
    onActiveChange,
}: DepartmentFieldsProps) {
    return (
        <div className="grid gap-5">
            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-name`}>Name</Label>
                <Input
                    id={`${idPrefix}-name`}
                    name="name"
                    defaultValue={defaults?.name}
                    placeholder="College of Information Technology"
                    required
                    aria-invalid={Boolean(errors.name)}
                    aria-describedby={
                        errors.name ? `${idPrefix}-name-error` : undefined
                    }
                />
                <InputError
                    id={`${idPrefix}-name-error`}
                    message={errors.name}
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-code`}>Code</Label>
                <Input
                    id={`${idPrefix}-code`}
                    name="code"
                    defaultValue={defaults?.code ?? ''}
                    placeholder="CIT"
                    aria-invalid={Boolean(errors.code)}
                    aria-describedby={
                        errors.code ? `${idPrefix}-code-error` : undefined
                    }
                />
                <InputError
                    id={`${idPrefix}-code-error`}
                    message={errors.code}
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-description`}>Description</Label>
                <textarea
                    id={`${idPrefix}-description`}
                    name="description"
                    defaultValue={defaults?.description ?? ''}
                    rows={4}
                    placeholder="Optional description of this department"
                    aria-invalid={Boolean(errors.description)}
                    aria-describedby={
                        errors.description
                            ? `${idPrefix}-description-error`
                            : undefined
                    }
                    className={cn(
                        'min-h-24 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30',
                        errors.description &&
                            'border-destructive ring-destructive/20 dark:ring-destructive/40',
                    )}
                />
                <InputError
                    id={`${idPrefix}-description-error`}
                    message={errors.description}
                />
            </div>

            <div className="grid gap-2">
                <input
                    type="hidden"
                    name="is_active"
                    value={isActive ? '1' : '0'}
                />
                <div className="flex items-start gap-3 rounded-lg border bg-muted/30 p-3">
                    <Checkbox
                        id={`${idPrefix}-active`}
                        checked={isActive}
                        onCheckedChange={(checked) =>
                            onActiveChange(checked === true)
                        }
                        aria-invalid={Boolean(errors.is_active)}
                    />
                    <div className="grid gap-1">
                        <Label htmlFor={`${idPrefix}-active`}>
                            Active department
                        </Label>
                        <p className="text-xs leading-5 text-muted-foreground">
                            Active departments can be assigned to users.
                        </p>
                    </div>
                </div>
                <InputError message={errors.is_active} />
            </div>
        </div>
    );
}

function CreateDepartmentDialog({ teamSlug }: { teamSlug: string }) {
    const [open, setOpen] = useState(false);
    const [isActive, setIsActive] = useState(true);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus />
                    New Department
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Create department</DialogTitle>
                    <DialogDescription>
                        Add an organizational unit for operational planning and
                        user assignments.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...store.form(teamSlug)}
                    errorBag="createDepartment"
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => {
                        setOpen(false);
                        setIsActive(true);
                    }}
                    className="grid gap-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <DepartmentFields
                                errors={errors}
                                idPrefix="create-department"
                                isActive={isActive}
                                onActiveChange={setIsActive}
                            />
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {processing ? 'Creating...' : 'Create'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function EditDepartmentDialog({
    department,
    teamSlug,
}: {
    department: Department;
    teamSlug: string;
}) {
    const [open, setOpen] = useState(false);
    const [isActive, setIsActive] = useState(department.isActive);

    const updateOpen = (nextOpen: boolean): void => {
        setOpen(nextOpen);

        if (!nextOpen) {
            setIsActive(department.isActive);
        }
    };

    return (
        <Dialog open={open} onOpenChange={updateOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <Pencil />
                    Edit
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit department</DialogTitle>
                    <DialogDescription>
                        Update {department.name}. Existing planning records are
                        retained if the department is made inactive.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...update.form([teamSlug, department.id])}
                    errorBag={`updateDepartment${department.id}`}
                    options={{ preserveScroll: true }}
                    setDefaultsOnSuccess
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-6"
                >
                    {({ errors, processing, recentlySuccessful }) => (
                        <>
                            <DepartmentFields
                                errors={errors}
                                idPrefix={`edit-department-${department.id}`}
                                defaults={department}
                                isActive={isActive}
                                onActiveChange={setIsActive}
                            />
                            <DialogFooter className="items-center">
                                {recentlySuccessful && (
                                    <span className="mr-auto flex items-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-300">
                                        <CircleCheck className="size-4" />
                                        Saved
                                    </span>
                                )}
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => updateOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {processing ? 'Saving...' : 'Save changes'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function DepartmentsIndex({ departments }: Props) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return (
            <div className="p-6 text-sm text-muted-foreground">
                Select a team before managing departments.
            </div>
        );
    }

    return (
        <>
            <Head title="Departments" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Departments"
                        description="Manage the organizational units used for user access and planning ownership."
                    />
                    <CreateDepartmentDialog teamSlug={currentTeam.slug} />
                </div>

                <Card>
                    <CardHeader className="flex-row items-start justify-between gap-4">
                        <div className="space-y-1.5">
                            <CardTitle>Department directory</CardTitle>
                            <CardDescription>
                                {departments.length}{' '}
                                {departments.length === 1
                                    ? 'department'
                                    : 'departments'}
                            </CardDescription>
                        </div>
                        <Building2 className="size-5 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        {departments.length === 0 ? (
                            <div className="rounded-xl border border-dashed p-8 text-center">
                                <p className="font-medium">
                                    No departments yet
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Create the first department to begin
                                    assigning department users.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y rounded-xl border">
                                {departments.map((department) => (
                                    <div
                                        key={department.id}
                                        className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="min-w-0 space-y-1.5">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">
                                                    {department.name}
                                                </p>
                                                {department.code && (
                                                    <span className="rounded-md bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground">
                                                        {department.code}
                                                    </span>
                                                )}
                                                <StatusBadge
                                                    status={
                                                        department.isActive
                                                            ? 'active'
                                                            : 'inactive'
                                                    }
                                                />
                                            </div>
                                            <p className="line-clamp-2 text-sm text-muted-foreground">
                                                {department.description ??
                                                    'No description provided.'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {department.userCount}{' '}
                                                {department.userCount === 1
                                                    ? 'assigned user'
                                                    : 'assigned users'}
                                            </p>
                                        </div>
                                        <EditDepartmentDialog
                                            key={`${department.id}-${department.name}-${department.code}-${department.isActive}`}
                                            department={department}
                                            teamSlug={currentTeam.slug}
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

DepartmentsIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Departments',
            href: props.currentTeam ? index(props.currentTeam.slug) : home(),
        },
    ],
});
