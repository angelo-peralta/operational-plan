import { Form } from '@inertiajs/react';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    approve,
    close,
    reopen,
    returnMethod,
    submit as submitPlan,
} from '@/routes/operational-plans';

type WorkflowAction = 'submit' | 'approve' | 'return' | 'close' | 'reopen';

const actionContent: Record<
    WorkflowAction,
    { title: string; description: string; submitLabel: string }
> = {
    submit: {
        title: 'Submit Operational Plan',
        description:
            'Submitting locks plan editing while reviewers evaluate the plan.',
        submitLabel: 'Submit plan',
    },
    approve: {
        title: 'Approve Operational Plan',
        description:
            'Approval confirms that the submitted plan is ready for implementation.',
        submitLabel: 'Approve plan',
    },
    return: {
        title: 'Return for Revision',
        description:
            'Explain what the department must revise. The remarks become part of the permanent workflow history.',
        submitLabel: 'Return plan',
    },
    close: {
        title: 'Close Operational Plan',
        description: 'Closing marks this approved plan as final and read-only.',
        submitLabel: 'Close plan',
    },
    reopen: {
        title: 'Reopen for Revision',
        description:
            'Explain why this approved or closed plan must be revised. Its approved revision remains in the workflow history.',
        submitLabel: 'Reopen plan',
    },
};

export function WorkflowActionDialog({
    action,
    teamSlug,
    operationalPlanId,
    trigger,
}: {
    action: WorkflowAction;
    teamSlug: string;
    operationalPlanId: number;
    trigger: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const content = actionContent[action];
    const routeArguments: [string, number] = [teamSlug, operationalPlanId];
    const formRoute =
        action === 'submit'
            ? submitPlan.form(routeArguments)
            : action === 'approve'
              ? approve.form(routeArguments)
              : action === 'return'
                ? returnMethod.form(routeArguments)
                : action === 'close'
                  ? close.form(routeArguments)
                  : reopen.form(routeArguments);
    const requiresRemarks = action === 'return' || action === 'reopen';

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{content.title}</DialogTitle>
                    <DialogDescription>{content.description}</DialogDescription>
                </DialogHeader>

                <Form
                    {...formRoute}
                    errorBag={`${action}OperationalPlan${operationalPlanId}`}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-5"
                >
                    {({ errors, processing }) => (
                        <>
                            {requiresRemarks && (
                                <div className="grid gap-2">
                                    <Label htmlFor={`${action}-plan-remarks`}>
                                        Remarks
                                    </Label>
                                    <Textarea
                                        id={`${action}-plan-remarks`}
                                        name="remarks"
                                        className="min-h-32"
                                        required
                                        aria-invalid={Boolean(errors.remarks)}
                                    />
                                    <InputError message={errors.remarks} />
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
                                <Button
                                    type="submit"
                                    variant={
                                        action === 'return'
                                            ? 'destructive'
                                            : 'default'
                                    }
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Processing...'
                                        : content.submitLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
