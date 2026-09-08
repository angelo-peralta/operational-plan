import { Head, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { home } from '@/routes';
import { index, store, update } from '@/routes/administration/users';
import type { UserRole } from '@/types';

type Assignment = {
    organizational_unit_id: string;
    assignment_type: string;
    position_title: string;
    is_primary: boolean;
    is_active: boolean;
    starts_on: string;
    ends_on: string;
};
type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    roleLabel: string;
    assignments: (Omit<
        Assignment,
        'organizational_unit_id' | 'starts_on' | 'ends_on' | 'position_title'
    > & {
        organizational_unit_id: number;
        unit_name: string;
        starts_on: string | null;
        ends_on: string | null;
        position_title: string | null;
    })[];
};
type Props = {
    users: ManagedUser[];
    roles: { value: UserRole; label: string }[];
    organizationalUnits: {
        id: number;
        name: string;
        code: string | null;
        is_active: boolean;
    }[];
};

function assignmentType(role: UserRole): string {
    return role === 'office_head'
        ? 'head'
        : role === 'executive'
          ? 'executive'
          : 'staff';
}

function UserDialog({
    user,
    roles,
    units,
    teamSlug,
}: {
    user?: ManagedUser;
    roles: Props['roles'];
    units: Props['organizationalUnits'];
    teamSlug: string;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        role: user?.role ?? ('staff' as UserRole),
        assignments: (user?.assignments ?? []).map(
            (assignment): Assignment => ({
                organizational_unit_id: String(
                    assignment.organizational_unit_id,
                ),
                assignment_type: assignment.assignment_type,
                position_title: assignment.position_title ?? '',
                is_primary: assignment.is_primary,
                is_active: assignment.is_active,
                starts_on: assignment.starts_on ?? '',
                ends_on: assignment.ends_on ?? '',
            }),
        ),
    });
    const errors: Partial<Record<string, string>> = form.errors;
    const prefix = `user-${user?.id ?? 'new'}`;
    const patchAssignment = (row: number, patch: Partial<Assignment>) =>
        form.setData(
            'assignments',
            form.data.assignments.map((assignment, index) =>
                index === row ? { ...assignment, ...patch } : assignment,
            ),
        );

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (next) {
                    form.reset();
                    form.clearErrors();
                }
            }}
        >
            <DialogTrigger asChild>
                <Button
                    variant={user ? 'outline' : 'default'}
                    size={user ? 'sm' : 'default'}
                >
                    {user ? <Pencil /> : <Plus />}
                    {user ? 'Edit' : 'New user'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>
                        {user ? 'Edit user' : 'Create user'}
                    </DialogTitle>
                    <DialogDescription>
                        Choose a system role and organizational assignments.
                        President and Vice Presidents share the Executive role.
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="grid gap-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const options = {
                            preserveScroll: true,
                            onSuccess: () => setOpen(false),
                        };

                        if (user) {
                            form.patch(
                                update.url([teamSlug, user.id]),
                                options,
                            );
                        } else {
                            form.post(store.url(teamSlug), options);
                        }
                    }}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        {(['name', 'email'] as const).map((field) => (
                            <div key={field} className="grid gap-2">
                                <Label htmlFor={prefix + '-' + field}>
                                    {field === 'name' ? 'Name' : 'Email'}
                                </Label>
                                <Input
                                    id={prefix + '-' + field}
                                    type={field === 'email' ? 'email' : 'text'}
                                    value={form.data[field]}
                                    onChange={(event) =>
                                        form.setData(field, event.target.value)
                                    }
                                    required
                                />
                                <InputError message={form.errors[field]} />
                            </div>
                        ))}
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {(['password', 'password_confirmation'] as const).map(
                            (field) => (
                                <div key={field} className="grid gap-2">
                                    <Label htmlFor={prefix + '-' + field}>
                                        {field === 'password'
                                            ? user
                                                ? 'New password (optional)'
                                                : 'Password'
                                            : 'Confirm password'}
                                    </Label>
                                    <Input
                                        id={prefix + '-' + field}
                                        type="password"
                                        autoComplete="new-password"
                                        value={form.data[field]}
                                        onChange={(event) =>
                                            form.setData(
                                                field,
                                                event.target.value,
                                            )
                                        }
                                        required={!user}
                                    />
                                    <InputError message={form.errors[field]} />
                                </div>
                            ),
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={prefix + '-role'}>System role</Label>
                        <Select
                            value={form.data.role}
                            onValueChange={(value: UserRole) => {
                                form.setData({
                                    ...form.data,
                                    role: value,
                                    assignments:
                                        value === 'system_admin'
                                            ? []
                                            : form.data.assignments.map(
                                                  (assignment) => ({
                                                      ...assignment,
                                                      assignment_type:
                                                          assignmentType(value),
                                                  }),
                                              ),
                                });
                            }}
                        >
                            <SelectTrigger
                                id={prefix + '-role'}
                                className="w-full"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem
                                        key={role.value}
                                        value={role.value}
                                    >
                                        {role.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.role} />
                    </div>
                    {form.data.role === 'system_admin' ? (
                        <p className="rounded-lg bg-muted p-3 text-sm">
                            System Admin manages configuration and can view
                            records for support. Executive approval and ratings
                            require executive authority.
                        </p>
                    ) : (
                        <div className="grid gap-4">
                            <div className="flex items-center justify-between gap-3">
                                <p className="font-medium">
                                    Organizational assignments
                                </p>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        form.setData('assignments', [
                                            ...form.data.assignments,
                                            {
                                                organizational_unit_id: '',
                                                assignment_type: assignmentType(
                                                    form.data.role,
                                                ),
                                                position_title: '',
                                                is_primary:
                                                    form.data.assignments
                                                        .length === 0,
                                                is_active: true,
                                                starts_on: '',
                                                ends_on: '',
                                            },
                                        ])
                                    }
                                >
                                    <Plus />
                                    Add assignment
                                </Button>
                            </div>
                            {form.data.assignments.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    This account has no organizational access
                                    until an assignment is added.
                                </p>
                            )}
                            {form.data.assignments.map((assignment, row) => {
                                const rowPrefix = `${prefix}-assignment-${row}`;

                                return (
                                    <fieldset
                                        key={row}
                                        className="grid gap-4 rounded-lg border p-4"
                                    >
                                        <legend className="px-2 text-sm font-medium">
                                            Assignment {row + 1}
                                        </legend>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={rowPrefix + '-unit'}
                                            >
                                                Organizational unit
                                            </Label>
                                            <Select
                                                value={
                                                    assignment.organizational_unit_id
                                                }
                                                onValueChange={(value) =>
                                                    patchAssignment(row, {
                                                        organizational_unit_id:
                                                            value,
                                                    })
                                                }
                                            >
                                                <SelectTrigger
                                                    id={rowPrefix + '-unit'}
                                                    className="w-full"
                                                >
                                                    <SelectValue placeholder="Select a unit" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {units.map((unit) => (
                                                        <SelectItem
                                                            key={unit.id}
                                                            value={String(
                                                                unit.id,
                                                            )}
                                                        >
                                                            {unit.name}
                                                            {!unit.is_active
                                                                ? ' (inactive)'
                                                                : ''}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    errors[
                                                        `assignments.${row}.organizational_unit_id`
                                                    ]
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={rowPrefix + '-title'}
                                            >
                                                Position title
                                            </Label>
                                            <Input
                                                id={rowPrefix + '-title'}
                                                value={
                                                    assignment.position_title
                                                }
                                                onChange={(event) =>
                                                    patchAssignment(row, {
                                                        position_title:
                                                            event.target.value,
                                                    })
                                                }
                                                placeholder="e.g. Vice President for Academic Affairs"
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `assignments.${row}.position_title`
                                                    ]
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            {(
                                                [
                                                    'starts_on',
                                                    'ends_on',
                                                ] as const
                                            ).map((field) => (
                                                <div
                                                    key={field}
                                                    className="grid gap-2"
                                                >
                                                    <Label
                                                        htmlFor={
                                                            rowPrefix +
                                                            '-' +
                                                            field
                                                        }
                                                    >
                                                        {field === 'starts_on'
                                                            ? 'Starts on (optional)'
                                                            : 'Ends on (optional)'}
                                                    </Label>
                                                    <Input
                                                        id={
                                                            rowPrefix +
                                                            '-' +
                                                            field
                                                        }
                                                        type="date"
                                                        value={
                                                            assignment[field]
                                                        }
                                                        onChange={(event) =>
                                                            patchAssignment(
                                                                row,
                                                                {
                                                                    [field]:
                                                                        event
                                                                            .target
                                                                            .value,
                                                                },
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `assignments.${row}.${field}`
                                                            ]
                                                        }
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                        <div className="flex flex-wrap items-center gap-4">
                                            <div className="flex items-center gap-2">
                                                <Checkbox
                                                    id={rowPrefix + '-active'}
                                                    checked={
                                                        assignment.is_active
                                                    }
                                                    onCheckedChange={(value) =>
                                                        patchAssignment(row, {
                                                            is_active:
                                                                value === true,
                                                        })
                                                    }
                                                />
                                                <Label
                                                    htmlFor={
                                                        rowPrefix + '-active'
                                                    }
                                                >
                                                    Active
                                                </Label>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Checkbox
                                                    id={rowPrefix + '-primary'}
                                                    checked={
                                                        assignment.is_primary
                                                    }
                                                    onCheckedChange={(value) =>
                                                        patchAssignment(row, {
                                                            is_primary:
                                                                value === true,
                                                        })
                                                    }
                                                />
                                                <Label
                                                    htmlFor={
                                                        rowPrefix + '-primary'
                                                    }
                                                >
                                                    Primary
                                                </Label>
                                            </div>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                className="ml-auto"
                                                onClick={() =>
                                                    form.setData(
                                                        'assignments',
                                                        form.data.assignments.filter(
                                                            (_, index) =>
                                                                index !== row,
                                                        ),
                                                    )
                                                }
                                            >
                                                <Trash2 />
                                                Remove assignment
                                            </Button>
                                        </div>
                                        <InputError
                                            message={
                                                errors[
                                                    `assignments.${row}.assignment_type`
                                                ]
                                            }
                                        />
                                    </fieldset>
                                );
                            })}
                            <InputError message={form.errors.assignments} />
                        </div>
                    )}
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button disabled={form.processing}>
                            {form.processing ? 'Saving...' : 'Save user'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function UsersIndex({
    users,
    organizationalUnits,
    roles,
}: Props) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return null;
    }

    return (
        <>
            <Head title="Users" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:justify-between">
                    <Heading
                        title="Users"
                        description="Manage accounts, system roles, and organizational assignments."
                    />
                    <UserDialog
                        roles={roles}
                        units={organizationalUnits}
                        teamSlug={currentTeam.slug}
                    />
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>User directory</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="divide-y rounded-lg border">
                            {users.map((user) => (
                                <div
                                    key={user.id}
                                    className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {user.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {user.email}
                                        </p>
                                        <p className="mt-1 text-sm">
                                            {user.assignments
                                                .filter(
                                                    (assignment) =>
                                                        assignment.is_active,
                                                )
                                                .map(
                                                    (assignment) =>
                                                        assignment.unit_name,
                                                )
                                                .join(' / ') ||
                                                'No active unit assignments'}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <StatusBadge
                                            status={user.role}
                                            label={user.roleLabel}
                                        />
                                        <UserDialog
                                            key={JSON.stringify(user)}
                                            user={user}
                                            roles={roles}
                                            units={organizationalUnits}
                                            teamSlug={currentTeam.slug}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
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
