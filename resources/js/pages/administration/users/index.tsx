import { Form, Head, usePage } from '@inertiajs/react';
import { CircleCheck, Pencil, Plus, ShieldCheck, Users } from 'lucide-react';
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
import { home } from '@/routes';
import { index, store, update } from '@/routes/administration/users';
import type {
    AdministrationUser,
    DepartmentSummary,
    SelectOption,
    UserRole,
} from '@/types';

type Props = {
    users: AdministrationUser[];
    departments: DepartmentSummary[];
    roles: SelectOption<UserRole>[];
};

type UserFieldsProps = {
    departments: DepartmentSummary[];
    errors: Partial<Record<string, string>>;
    idPrefix: string;
    roles: SelectOption<UserRole>[];
    role: UserRole;
    departmentId: string;
    onRoleChange: (role: UserRole) => void;
    onDepartmentChange: (departmentId: string) => void;
    defaults?: AdministrationUser;
    requirePassword?: boolean;
};

function UserFields({
    departments,
    errors,
    idPrefix,
    roles,
    role,
    departmentId,
    onRoleChange,
    onDepartmentChange,
    defaults,
    requirePassword = false,
}: UserFieldsProps) {
    const updateRole = (nextRole: string): void => {
        const typedRole = nextRole as UserRole;
        onRoleChange(typedRole);

        if (typedRole !== 'department_user') {
            onDepartmentChange('');
        }
    };

    return (
        <div className="grid gap-5">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-name`}>Name</Label>
                    <Input
                        id={`${idPrefix}-name`}
                        name="name"
                        defaultValue={defaults?.name}
                        autoComplete="name"
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
                    <Label htmlFor={`${idPrefix}-email`}>Email</Label>
                    <Input
                        id={`${idPrefix}-email`}
                        name="email"
                        type="email"
                        defaultValue={defaults?.email}
                        autoComplete="email"
                        required
                        aria-invalid={Boolean(errors.email)}
                        aria-describedby={
                            errors.email ? `${idPrefix}-email-error` : undefined
                        }
                    />
                    <InputError
                        id={`${idPrefix}-email-error`}
                        message={errors.email}
                    />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-role`}>Role</Label>
                    <input type="hidden" name="role" value={role} />
                    <Select value={role} onValueChange={updateRole}>
                        <SelectTrigger
                            id={`${idPrefix}-role`}
                            className="w-full"
                            aria-invalid={Boolean(errors.role)}
                            aria-describedby={
                                errors.role
                                    ? `${idPrefix}-role-error`
                                    : undefined
                            }
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {roles.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError
                        id={`${idPrefix}-role-error`}
                        message={errors.role}
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-department`}>Department</Label>
                    <input
                        type="hidden"
                        name="department_id"
                        value={departmentId}
                    />
                    <Select
                        value={departmentId || undefined}
                        onValueChange={onDepartmentChange}
                        disabled={role !== 'department_user'}
                    >
                        <SelectTrigger
                            id={`${idPrefix}-department`}
                            className="w-full"
                            aria-invalid={Boolean(errors.department_id)}
                            aria-describedby={
                                errors.department_id
                                    ? `${idPrefix}-department-error`
                                    : undefined
                            }
                        >
                            <SelectValue
                                placeholder={
                                    role === 'department_user'
                                        ? 'Select a department'
                                        : 'Not department-scoped'
                                }
                            />
                        </SelectTrigger>
                        <SelectContent>
                            {departments.map((department) => (
                                <SelectItem
                                    key={department.id}
                                    value={String(department.id)}
                                >
                                    {department.name}
                                    {department.code
                                        ? ` (${department.code})`
                                        : ''}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError
                        id={`${idPrefix}-department-error`}
                        message={errors.department_id}
                    />
                    {role === 'department_user' && departments.length === 0 && (
                        <p className="text-xs text-amber-700 dark:text-amber-300">
                            Create an active department before assigning this
                            role.
                        </p>
                    )}
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-password`}>
                        Password{requirePassword ? '' : ' (optional)'}
                    </Label>
                    <Input
                        id={`${idPrefix}-password`}
                        name="password"
                        type="password"
                        autoComplete="new-password"
                        required={requirePassword}
                        aria-invalid={Boolean(errors.password)}
                        aria-describedby={
                            errors.password
                                ? `${idPrefix}-password-error`
                                : `${idPrefix}-password-help`
                        }
                    />
                    <p
                        id={`${idPrefix}-password-help`}
                        className="text-xs text-muted-foreground"
                    >
                        {requirePassword
                            ? 'Use a secure password that meets the application requirements.'
                            : 'Leave blank to keep the current password.'}
                    </p>
                    <InputError
                        id={`${idPrefix}-password-error`}
                        message={errors.password}
                    />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor={`${idPrefix}-password-confirmation`}>
                        Confirm password
                    </Label>
                    <Input
                        id={`${idPrefix}-password-confirmation`}
                        name="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        required={requirePassword}
                        aria-invalid={Boolean(errors.password_confirmation)}
                        aria-describedby={
                            errors.password_confirmation
                                ? `${idPrefix}-password-confirmation-error`
                                : undefined
                        }
                    />
                    <InputError
                        id={`${idPrefix}-password-confirmation-error`}
                        message={errors.password_confirmation}
                    />
                </div>
            </div>
        </div>
    );
}

function CreateUserDialog({
    departments,
    roles,
    teamSlug,
}: {
    departments: DepartmentSummary[];
    roles: SelectOption<UserRole>[];
    teamSlug: string;
}) {
    const [open, setOpen] = useState(false);
    const [role, setRole] = useState<UserRole>('department_user');
    const [departmentId, setDepartmentId] = useState('');

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus />
                    New User
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Create user</DialogTitle>
                    <DialogDescription>
                        Create an account and assign its operational role and
                        department access.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...store.form(teamSlug)}
                    errorBag="createUser"
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => {
                        setOpen(false);
                        setRole('department_user');
                        setDepartmentId('');
                    }}
                    className="grid gap-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <UserFields
                                departments={departments}
                                errors={errors}
                                idPrefix="create-user"
                                roles={roles}
                                role={role}
                                departmentId={departmentId}
                                onRoleChange={setRole}
                                onDepartmentChange={setDepartmentId}
                                requirePassword
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
                                    {processing ? 'Creating...' : 'Create user'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function EditUserDialog({
    departments,
    roles,
    teamSlug,
    user,
}: {
    departments: DepartmentSummary[];
    roles: SelectOption<UserRole>[];
    teamSlug: string;
    user: AdministrationUser;
}) {
    const initialRole = user.role ?? 'department_user';
    const initialDepartmentId = user.department
        ? String(user.department.id)
        : '';
    const [open, setOpen] = useState(false);
    const [role, setRole] = useState<UserRole>(initialRole);
    const [departmentId, setDepartmentId] = useState(initialDepartmentId);

    const updateOpen = (nextOpen: boolean): void => {
        setOpen(nextOpen);

        if (!nextOpen) {
            setRole(initialRole);
            setDepartmentId(initialDepartmentId);
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
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit user</DialogTitle>
                    <DialogDescription>
                        Update {user.name}&apos;s account, role, department, or
                        password.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...update.form([teamSlug, user.id])}
                    errorBag={`updateUser${user.id}`}
                    options={{ preserveScroll: true }}
                    setDefaultsOnSuccess
                    resetOnSuccess={['password', 'password_confirmation']}
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-6"
                >
                    {({ errors, processing, recentlySuccessful }) => (
                        <>
                            <UserFields
                                departments={departments}
                                errors={errors}
                                idPrefix={`edit-user-${user.id}`}
                                roles={roles}
                                role={role}
                                departmentId={departmentId}
                                onRoleChange={setRole}
                                onDepartmentChange={setDepartmentId}
                                defaults={user}
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

export default function UsersIndex({ users, departments, roles }: Props) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return (
            <div className="p-6 text-sm text-muted-foreground">
                Select a team before managing users.
            </div>
        );
    }

    return (
        <>
            <Head title="Users" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Users"
                        description="Manage accounts, operational roles, and department assignments."
                    />
                    <CreateUserDialog
                        departments={departments}
                        roles={roles}
                        teamSlug={currentTeam.slug}
                    />
                </div>

                <Card>
                    <CardHeader className="flex-row items-start justify-between gap-4">
                        <div className="space-y-1.5">
                            <CardTitle>User directory</CardTitle>
                            <CardDescription>
                                {users.length}{' '}
                                {users.length === 1 ? 'account' : 'accounts'}
                            </CardDescription>
                        </div>
                        <Users className="size-5 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        {users.length === 0 ? (
                            <div className="rounded-xl border border-dashed p-8 text-center">
                                <p className="font-medium">No users yet</p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Create an account and assign its role to get
                                    started.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y rounded-xl border">
                                {users.map((user) => (
                                    <div
                                        key={user.id}
                                        className="flex flex-col gap-4 p-4 lg:flex-row lg:items-center lg:justify-between"
                                    >
                                        <div className="grid min-w-0 gap-3 sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] lg:flex-1">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {user.name}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {user.email}
                                                </p>
                                            </div>
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {user.department?.name ??
                                                        'No department'}
                                                </p>
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {user.department?.code ??
                                                        'Not department-scoped'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-2 lg:justify-end">
                                            <StatusBadge
                                                status={
                                                    user.role ?? 'unassigned'
                                                }
                                                label={
                                                    user.roleLabel ??
                                                    'Unassigned'
                                                }
                                            />
                                            <StatusBadge
                                                status={
                                                    user.emailVerifiedAt
                                                        ? 'verified'
                                                        : 'pending'
                                                }
                                                label={
                                                    user.emailVerifiedAt
                                                        ? 'Verified'
                                                        : 'Unverified'
                                                }
                                            />
                                            <EditUserDialog
                                                key={`${user.id}-${user.name}-${user.email}-${user.role}-${user.department?.id}`}
                                                departments={departments}
                                                roles={roles}
                                                teamSlug={currentTeam.slug}
                                                user={user}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="flex items-start gap-3 rounded-xl border bg-muted/20 p-4 text-sm text-muted-foreground">
                    <ShieldCheck className="mt-0.5 size-4 shrink-0" />
                    <p>
                        Navigation is a convenience only. All role and
                        department permissions are enforced by the server.
                    </p>
                </div>
            </div>
        </>
    );
}

UsersIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Users',
            href: props.currentTeam ? index(props.currentTeam.slug) : home(),
        },
    ],
});
