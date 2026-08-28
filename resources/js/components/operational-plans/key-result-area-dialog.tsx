import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store, update } from '@/routes/operational-plans/key-result-areas';
import type { KeyResultArea } from '@/types';

export function KeyResultAreaDialog({
    mode,
    teamSlug,
    operationalPlanId,
    keyResultArea,
    trigger,
}: {
    mode: 'create' | 'edit';
    teamSlug: string;
    operationalPlanId: number;
    keyResultArea?: KeyResultArea;
    trigger: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const formRoute =
        mode === 'create'
            ? store.form([teamSlug, operationalPlanId])
            : update.form([
                  teamSlug,
                  operationalPlanId,
                  keyResultArea?.id ?? 0,
              ]);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'create'
                            ? 'Add Key Result Area'
                            : 'Edit Key Result Area'}
                    </DialogTitle>
                    <DialogDescription>
                        Group related Plan Items under a named institutional
                        result area.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...formRoute}
                    errorBag={`${mode}KeyResultArea${keyResultArea?.id ?? operationalPlanId}`}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={mode === 'create'}
                    setDefaultsOnSuccess={mode === 'edit'}
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`${mode}-kra-${keyResultArea?.id ?? operationalPlanId}-code`}
                                >
                                    Code
                                </Label>
                                <Input
                                    id={`${mode}-kra-${keyResultArea?.id ?? operationalPlanId}-code`}
                                    name="code"
                                    defaultValue={keyResultArea?.code ?? ''}
                                    placeholder="e.g. KRA 1"
                                    aria-invalid={Boolean(errors.code)}
                                />
                                <InputError message={errors.code} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`${mode}-kra-${keyResultArea?.id ?? operationalPlanId}-name`}
                                >
                                    Name
                                </Label>
                                <Input
                                    id={`${mode}-kra-${keyResultArea?.id ?? operationalPlanId}-name`}
                                    name="name"
                                    defaultValue={keyResultArea?.name ?? ''}
                                    required
                                    aria-invalid={Boolean(errors.name)}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`${mode}-kra-${keyResultArea?.id ?? operationalPlanId}-description`}
                                >
                                    Description
                                </Label>
                                <Textarea
                                    id={`${mode}-kra-${keyResultArea?.id ?? operationalPlanId}-description`}
                                    name="description"
                                    defaultValue={
                                        keyResultArea?.description ?? ''
                                    }
                                    aria-invalid={Boolean(errors.description)}
                                />
                                <InputError message={errors.description} />
                            </div>

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
                                    {processing
                                        ? 'Saving...'
                                        : mode === 'create'
                                          ? 'Add KRA'
                                          : 'Save changes'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
