import { Head, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import { home } from '@/routes';
import {
    index,
    store,
    update,
} from '@/routes/administration/organizational-units';

type Unit = {
    id: number;
    parentId: number | null;
    name: string;
    code: string | null;
    acronym: string | null;
    type: string;
    typeLabel: string;
    description: string | null;
    isActive: boolean;
    sortOrder: number;
    assignmentCount: number;
    parent: { id: number; name: string } | null;
};
type Props = {
    organizationalUnits: Unit[];
    unitTypes: { value: string; label: string }[];
};

function UnitDialog({
    unit,
    units,
    unitTypes,
    teamSlug,
}: {
    unit?: Unit;
    units: Unit[];
    unitTypes: Props['unitTypes'];
    teamSlug: string;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: unit?.name ?? '',
        code: unit?.code ?? '',
        acronym: unit?.acronym ?? '',
        parent_id: unit?.parentId?.toString() ?? '',
        type: unit?.type ?? 'office',
        description: unit?.description ?? '',
        is_active: unit?.isActive ?? true,
        sort_order: unit?.sortOrder ?? 0,
    });
    const prefix = `unit-${unit?.id ?? 'new'}`;

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
                    variant={unit ? 'outline' : 'default'}
                    size={unit ? 'sm' : 'default'}
                >
                    {unit ? <Pencil /> : <Plus />}
                    {unit ? 'Edit' : 'New organizational unit'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {unit
                            ? 'Edit organizational unit'
                            : 'Create organizational unit'}
                    </DialogTitle>
                    <DialogDescription>
                        Set the parent unit to define executive scope. Activate
                        only units that participate in planning.
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

                        if (unit) {
                            form.patch(
                                update.url([teamSlug, unit.id]),
                                options,
                            );
                        } else {
                            form.post(store.url(teamSlug), options);
                        }
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor={prefix + '-name'}>Name</Label>
                        <Input
                            id={prefix + '-name'}
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {(['code', 'acronym'] as const).map((field) => (
                            <div key={field} className="grid gap-2">
                                <Label htmlFor={prefix + '-' + field}>
                                    {field === 'code' ? 'Code' : 'Acronym'}
                                </Label>
                                <Input
                                    id={prefix + '-' + field}
                                    value={form.data[field]}
                                    onChange={(event) =>
                                        form.setData(field, event.target.value)
                                    }
                                />
                                <InputError message={form.errors[field]} />
                            </div>
                        ))}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={prefix + '-parent'}>Parent unit</Label>
                        <Select
                            value={form.data.parent_id || 'none'}
                            onValueChange={(value) =>
                                form.setData(
                                    'parent_id',
                                    value === 'none' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger
                                id={prefix + '-parent'}
                                className="w-full"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    No parent (root)
                                </SelectItem>
                                {units
                                    .filter(
                                        (candidate) =>
                                            candidate.id !== unit?.id,
                                    )
                                    .map((candidate) => (
                                        <SelectItem
                                            key={candidate.id}
                                            value={String(candidate.id)}
                                        >
                                            {candidate.name}
                                        </SelectItem>
                                    ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.parent_id} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={prefix + '-type'}>Unit type</Label>
                            <Select
                                value={form.data.type}
                                onValueChange={(value) =>
                                    form.setData('type', value)
                                }
                            >
                                <SelectTrigger
                                    id={prefix + '-type'}
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {unitTypes.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.type} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={prefix + '-order'}>
                                Display order
                            </Label>
                            <Input
                                id={prefix + '-order'}
                                type="number"
                                min={0}
                                value={form.data.sort_order}
                                onChange={(event) =>
                                    form.setData(
                                        'sort_order',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            <InputError message={form.errors.sort_order} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={prefix + '-description'}>
                            Description
                        </Label>
                        <Textarea
                            id={prefix + '-description'}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    <div className="flex items-center gap-3">
                        <Checkbox
                            id={prefix + '-active'}
                            checked={form.data.is_active}
                            onCheckedChange={(value) =>
                                form.setData('is_active', value === true)
                            }
                        />
                        <Label htmlFor={prefix + '-active'}>
                            Active participating unit
                        </Label>
                    </div>
                    <InputError message={form.errors.is_active} />
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button disabled={form.processing}>
                            {form.processing ? 'Saving...' : 'Save unit'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function OrganizationalUnitsIndex({
    organizationalUnits,
    unitTypes,
}: Props) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return null;
    }

    const ordered: { unit: Unit; depth: number }[] = [];
    const visited = new Set<number>();
    function append(parentId: number | null, depth: number) {
        for (const unit of organizationalUnits.filter(
            (candidate) => candidate.parentId === parentId,
        )) {
            if (visited.has(unit.id)) {
                continue;
            }

            visited.add(unit.id);
            ordered.push({ unit, depth });
            append(unit.id, depth + 1);
        }
    }
    append(null, 0);

    for (const unit of organizationalUnits) {
        if (!visited.has(unit.id)) {
            ordered.push({ unit, depth: 0 });
        }
    }

    return (
        <>
            <Head title="Organizational units" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:justify-between">
                    <Heading
                        title="Organizational units"
                        description="Configure participating offices and the hierarchy that determines executive authority."
                    />
                    <UnitDialog
                        units={organizationalUnits}
                        unitTypes={unitTypes}
                        teamSlug={currentTeam.slug}
                    />
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Organizational hierarchy</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {ordered.length === 0 ? (
                            <p className="rounded-lg border border-dashed p-8 text-center text-muted-foreground">
                                Create the university root, then add
                                participating units beneath it.
                            </p>
                        ) : (
                            <div className="divide-y rounded-lg border">
                                {ordered.map(({ unit, depth }) => (
                                    <div
                                        key={unit.id}
                                        className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div
                                            className="min-w-0"
                                            style={{
                                                paddingInlineStart:
                                                    Math.min(depth, 4) * 16,
                                            }}
                                        >
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-medium">
                                                    {depth > 0 ? '\u21b3 ' : ''}
                                                    {unit.name}
                                                </span>
                                                <StatusBadge
                                                    status={
                                                        unit.isActive
                                                            ? 'active'
                                                            : 'inactive'
                                                    }
                                                />
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {unit.typeLabel}
                                                {unit.code
                                                    ? ` / ${unit.code}`
                                                    : ''}{' '}
                                                / {unit.assignmentCount}{' '}
                                                assignments
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {unit.parent
                                                    ? `Reports to ${unit.parent.name}`
                                                    : 'Root organizational unit'}
                                            </p>
                                        </div>
                                        <UnitDialog
                                            key={JSON.stringify(unit)}
                                            unit={unit}
                                            units={organizationalUnits}
                                            unitTypes={unitTypes}
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

OrganizationalUnitsIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Organizational units',
            href: props.currentTeam ? index(props.currentTeam.slug) : home(),
        },
    ],
});
