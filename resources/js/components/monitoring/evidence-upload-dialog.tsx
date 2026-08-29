import { Form } from '@inertiajs/react';
import { Upload } from 'lucide-react';
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
import type {
    Accomplishment,
    MonitoringPlanItem,
    MonitoringReportingPeriod,
} from '@/types';
import { store } from '@/routes/monitoring/accomplishments/evidence';

export function EvidenceUploadDialog({
    teamSlug,
    reportingPeriod,
    planItem,
    accomplishment,
    trigger,
}: {
    teamSlug: string;
    reportingPeriod: MonitoringReportingPeriod;
    planItem: MonitoringPlanItem;
    accomplishment: Accomplishment;
    trigger: ReactNode;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Upload documentary evidence</DialogTitle>
                    <DialogDescription>
                        Files are stored privately and are available only to
                        authorized users. Accepted formats: PDF, JPG, JPEG, and
                        PNG, up to 10 MB.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...store.form([
                        teamSlug,
                        reportingPeriod.id,
                        planItem.id,
                        accomplishment.id,
                    ])}
                    errorBag={`uploadEvidence${accomplishment.id}`}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    disableWhileProcessing
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`evidence-file-${accomplishment.id}`}
                                >
                                    Evidence file
                                </Label>
                                <Input
                                    id={`evidence-file-${accomplishment.id}`}
                                    name="file"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                    required
                                    aria-invalid={Boolean(errors.file)}
                                />
                                <InputError message={errors.file} />
                            </div>
                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`evidence-title-${accomplishment.id}`}
                                    >
                                        Title
                                    </Label>
                                    <Input
                                        id={`evidence-title-${accomplishment.id}`}
                                        name="title"
                                        maxLength={255}
                                    />
                                    <InputError message={errors.title} />
                                </div>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`evidence-type-${accomplishment.id}`}
                                    >
                                        Evidence type
                                    </Label>
                                    <Input
                                        id={`evidence-type-${accomplishment.id}`}
                                        name="evidence_type"
                                        placeholder="Report, certificate, photo…"
                                        maxLength={100}
                                    />
                                    <InputError
                                        message={errors.evidence_type}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`evidence-description-${accomplishment.id}`}
                                >
                                    Description
                                </Label>
                                <Textarea
                                    id={`evidence-description-${accomplishment.id}`}
                                    name="description"
                                    maxLength={5000}
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
                                    {processing ? <Spinner /> : <Upload />}
                                    {processing
                                        ? 'Uploading…'
                                        : 'Upload evidence'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
