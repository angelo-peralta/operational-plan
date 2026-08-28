import { Form } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useState } from 'react';
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
import { update } from '@/routes/operational-plans';
import type { DepartmentAccountableUser, OperationalPlan } from '@/types';

export function PlanHeaderDialog({
    teamSlug,
    operationalPlan,
    accountableUsers,
}: {
    teamSlug: string;
    operationalPlan: OperationalPlan;
    accountableUsers: DepartmentAccountableUser[];
}) {
    const [open, setOpen] = useState(false);
    const [accountableUserId, setAccountableUserId] = useState(
        operationalPlan.accountableUser?.id.toString() ?? 'none',
    );

    const resetSelection = (): void => {
        setAccountableUserId(
            operationalPlan.accountableUser?.id.toString() ?? 'none',
        );
    };

    const updateOpen = (nextOpen: boolean): void => {
        resetSelection();
        setOpen(nextOpen);
    };

    return (
        <Dialog open={open} onOpenChange={updateOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <Pencil />
                    Edit plan details
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit Operational Plan details</DialogTitle>
                    <DialogDescription>
                        Update the accountable person and institutional goal.
                        Academic Year and department ownership cannot be
                        changed.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...update.form([teamSlug, operationalPlan.id])}
                    errorBag={`updateOperationalPlan${operationalPlan.id}`}
                    options={{ preserveScroll: true }}
                    setDefaultsOnSuccess
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-plan-accountable-user">
                                    Accountable user
                                </Label>
                                <input
                                    type="hidden"
                                    name="accountable_user_id"
                                    value={
                                        accountableUserId === 'none'
                                            ? ''
                                            : accountableUserId
                                    }
                                />
                                <Select
                                    value={accountableUserId}
                                    onValueChange={setAccountableUserId}
                                >
                                    <SelectTrigger
                                        id="edit-plan-accountable-user"
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
                                        {accountableUsers.map((user) => (
                                            <SelectItem
                                                key={user.id}
                                                value={user.id.toString()}
                                            >
                                                {user.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.accountable_user_id}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-plan-accountable-name">
                                        Accountable name
                                    </Label>
                                    <Input
                                        id="edit-plan-accountable-name"
                                        name="accountable_name"
                                        defaultValue={
                                            operationalPlan.accountableName ??
                                            ''
                                        }
                                        aria-invalid={Boolean(
                                            errors.accountable_name,
                                        )}
                                    />
                                    <InputError
                                        message={errors.accountable_name}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-plan-accountable-position">
                                        Accountable position
                                    </Label>
                                    <Input
                                        id="edit-plan-accountable-position"
                                        name="accountable_position"
                                        defaultValue={
                                            operationalPlan.accountablePosition ??
                                            ''
                                        }
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
                                <Label htmlFor="edit-plan-goal">Goal</Label>
                                <Textarea
                                    id="edit-plan-goal"
                                    name="goal"
                                    className="min-h-36"
                                    defaultValue={operationalPlan.goal}
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
