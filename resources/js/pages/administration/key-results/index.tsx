import { Head, useForm, usePage } from '@inertiajs/react';
import { ListTree, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { Textarea } from '@/components/ui/textarea';
import {
    destroy,
    index as keyResultsIndex,
    store,
    update,
} from '@/routes/key-results';

type KeyResultAreaTemplate = {
    id: number;
    code: string | null;
    name: string;
    description: string | null;
    sortOrder: number;
    isActive: boolean;
};

type Props = {
    templates: KeyResultAreaTemplate[];
};

function TemplateDialog({
    teamSlug,
    template,
    nextSortOrder,
}: {
    teamSlug: string;
    template?: KeyResultAreaTemplate;
    nextSortOrder: number;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        code: template?.code ?? '',
        name: template?.name ?? '',
        description: template?.description ?? '',
        sort_order: template?.sortOrder ?? nextSortOrder,
        is_active: template?.isActive ?? true,
    });
    const prefix = `kra-template-${template?.id ?? 'new'}`;

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                setOpen(nextOpen);

                if (nextOpen) {
                    form.setData({
                        code: template?.code ?? '',
                        name: template?.name ?? '',
                        description: template?.description ?? '',
                        sort_order: template?.sortOrder ?? nextSortOrder,
                        is_active: template?.isActive ?? true,
                    });
                    form.clearErrors();
                }
            }}
        >
            <DialogTrigger asChild>
                <Button
                    variant={template ? 'outline' : 'default'}
                    size={template ? 'sm' : 'default'}
                >
                    {template ? <Pencil /> : <Plus />}
                    {template ? 'Edit' : 'New KRA template'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {template
                            ? 'Edit global KRA template'
                            : 'Create global KRA template'}
                    </DialogTitle>
                    <DialogDescription>
                        Active templates are copied into every new Operational
                        Plan. Existing plans keep their current KRA values.
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

                        if (template) {
                            form.patch(
                                update.url([teamSlug, template.id]),
                                options,
                            );
                        } else {
                            form.post(store.url(teamSlug), options);
                        }
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor={`${prefix}-code`}>Code</Label>
                        <Input
                            id={`${prefix}-code`}
                            value={form.data.code}
                            onChange={(event) =>
                                form.setData('code', event.target.value)
                            }
                            placeholder="e.g. KRA 1"
                            aria-invalid={Boolean(form.errors.code)}
                        />
                        <InputError message={form.errors.code} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`${prefix}-name`}>Name</Label>
                        <Input
                            id={`${prefix}-name`}
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required
                            aria-invalid={Boolean(form.errors.name)}
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`${prefix}-description`}>
                            Description
                        </Label>
                        <Textarea
                            id={`${prefix}-description`}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            rows={5}
                            aria-invalid={Boolean(form.errors.description)}
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`${prefix}-order`}>Display order</Label>
                        <Input
                            id={`${prefix}-order`}
                            type="number"
                            min={1}
                            max={9999}
                            value={form.data.sort_order}
                            onChange={(event) =>
                                form.setData(
                                    'sort_order',
                                    Number(event.target.value),
                                )
                            }
                            required
                            aria-invalid={Boolean(form.errors.sort_order)}
                        />
                        <InputError message={form.errors.sort_order} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id={`${prefix}-active`}
                                checked={form.data.is_active}
                                onCheckedChange={(value) =>
                                    form.setData('is_active', value === true)
                                }
                            />
                            <Label htmlFor={`${prefix}-active`}>
                                Use for newly created Operational Plans
                            </Label>
                        </div>
                        <InputError message={form.errors.is_active} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {form.processing
                                ? 'Saving...'
                                : template
                                  ? 'Save changes'
                                  : 'Create template'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteTemplateDialog({
    teamSlug,
    template,
}: {
    teamSlug: string;
    template: KeyResultAreaTemplate;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm<{ template?: string }>({});

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
                    <DialogTitle>Remove global KRA template?</DialogTitle>
                    <DialogDescription>
                        This removes “{template.name}” from future Operational
                        Plans. Existing plans and their review history will not
                        change.
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.delete(destroy.url([teamSlug, template.id]), {
                            preserveScroll: true,
                            onSuccess: () => setOpen(false),
                        });
                    }}
                >
                    <InputError message={form.errors.template} />
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
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {form.processing
                                ? 'Removing...'
                                : 'Remove template'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function KeyResultsIndex({ templates }: Props) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return null;
    }

    const nextSortOrder =
        templates.reduce(
            (highest, template) => Math.max(highest, template.sortOrder),
            0,
        ) + 1;

    return (
        <>
            <Head title="KRA Management" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="KRA Management"
                        description="Configure the global Key Result Areas copied into every new Operational Plan."
                    />
                    <TemplateDialog
                        teamSlug={currentTeam.slug}
                        nextSortOrder={nextSortOrder}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Global KRA templates</CardTitle>
                        <CardDescription>
                            Changes apply to future plans. Existing plan KRAs,
                            remarks, approvals, and evaluation history remain
                            unchanged.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {templates.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-10 text-center">
                                <ListTree className="mx-auto mb-3 size-8 text-muted-foreground" />
                                <p className="font-medium">
                                    No global KRA templates
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Create at least one active template before
                                    departments create Operational Plans.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-lg border">
                                <table className="w-full min-w-[760px] text-left text-sm">
                                    <thead className="bg-muted/40 text-xs tracking-wide text-muted-foreground uppercase">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">
                                                Order
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Code
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Key Result Area
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {templates.map((template) => (
                                            <tr key={template.id}>
                                                <td className="px-4 py-4 tabular-nums">
                                                    {template.sortOrder}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <Badge variant="secondary">
                                                        {template.code ?? '—'}
                                                    </Badge>
                                                </td>
                                                <td className="max-w-xl px-4 py-4">
                                                    <p className="font-medium">
                                                        {template.name}
                                                    </p>
                                                    {template.description && (
                                                        <p className="mt-1 whitespace-pre-line text-muted-foreground">
                                                            {
                                                                template.description
                                                            }
                                                        </p>
                                                    )}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <Badge
                                                        variant={
                                                            template.isActive
                                                                ? 'default'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {template.isActive
                                                            ? 'Active'
                                                            : 'Inactive'}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div className="flex justify-end gap-1">
                                                        <TemplateDialog
                                                            teamSlug={
                                                                currentTeam.slug
                                                            }
                                                            template={template}
                                                            nextSortOrder={
                                                                nextSortOrder
                                                            }
                                                        />
                                                        <DeleteTemplateDialog
                                                            teamSlug={
                                                                currentTeam.slug
                                                            }
                                                            template={template}
                                                        />
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

KeyResultsIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'KRA Management',
            href: props.currentTeam
                ? keyResultsIndex(props.currentTeam.slug)
                : '/',
        },
    ],
});
