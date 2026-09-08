import { Form } from '@inertiajs/react';
import { CirclePlus, Minus, Plus } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    store as storePlanItem,
    update as updatePlanItem,
} from '@/routes/operational-plans/key-result-areas/plan-items';
import type {
    CoAccountableOrganizationalUnit,
    PlanItem,
    SelectOption,
    TargetOperator,
} from '@/types';

type Props = {
    mode: 'create' | 'edit';
    teamSlug: string;
    operationalPlanId: number;
    keyResultAreaId: number;
    item?: PlanItem;
    organizationalUnits: CoAccountableOrganizationalUnit[];
    targetOperators: SelectOption<TargetOperator>[];
    trigger: ReactNode;
};

function initialList(values: string[] | undefined): string[] {
    return values && values.length > 0 ? [...values] : [''];
}

function StringListFields({
    idPrefix,
    label,
    description,
    fieldName,
    values,
    errors,
    onChange,
}: {
    idPrefix: string;
    label: string;
    description: string;
    fieldName:
        'documentary_evidence_requirements' | 'manual_co_accountable_units';
    values: string[];
    errors: Record<string, string>;
    onChange: (values: string[]) => void;
}) {
    return (
        <fieldset className="grid gap-3 rounded-xl border bg-muted/20 p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <Label asChild>
                        <legend>{label}</legend>
                    </Label>
                    <p className="text-xs leading-5 text-muted-foreground">
                        {description}
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => onChange([...values, ''])}
                >
                    <Plus />
                    Add
                </Button>
            </div>

            <InputError message={errors[fieldName]} />

            <div className="grid gap-2">
                {values.map((value, index) => (
                    <div key={`${idPrefix}-${index}`} className="grid gap-1.5">
                        <div className="flex items-center gap-2">
                            <Input
                                id={`${idPrefix}-${index}`}
                                name={`${fieldName}[${index}]`}
                                value={value}
                                onChange={(event) => {
                                    const nextValues = [...values];
                                    nextValues[index] = event.target.value;
                                    onChange(nextValues);
                                }}
                                placeholder={
                                    fieldName ===
                                    'documentary_evidence_requirements'
                                        ? 'e.g. Approved policy document'
                                        : 'e.g. VPA'
                                }
                                aria-label={`${label} ${index + 1}`}
                                aria-invalid={Boolean(
                                    errors[`${fieldName}.${index}`],
                                )}
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => {
                                    const nextValues = values.filter(
                                        (_, valueIndex) => valueIndex !== index,
                                    );
                                    onChange(
                                        nextValues.length > 0
                                            ? nextValues
                                            : [''],
                                    );
                                }}
                                aria-label={`Remove ${label.toLowerCase()} ${index + 1}`}
                            >
                                <Minus />
                            </Button>
                        </div>
                        <InputError message={errors[`${fieldName}.${index}`]} />
                    </div>
                ))}
            </div>
        </fieldset>
    );
}

export function PlanItemDialog({
    mode,
    teamSlug,
    operationalPlanId,
    keyResultAreaId,
    item,
    organizationalUnits,
    targetOperators,
    trigger,
}: Props) {
    const [open, setOpen] = useState(false);
    const [targetOperator, setTargetOperator] = useState<
        TargetOperator | 'none'
    >(item?.targetOperator ?? 'none');
    const [selectedOrganizationalUnitIds, setSelectedOrganizationalUnitIds] =
        useState<number[]>(
            item?.coAccountableOrganizationalUnits.map(
                (organizationalUnit) => organizationalUnit.id,
            ) ?? [],
        );
    const [evidenceRequirements, setEvidenceRequirements] = useState<string[]>(
        initialList(item?.documentaryEvidenceRequirements),
    );
    const [manualUnits, setManualUnits] = useState<string[]>(
        initialList(item?.manualCoAccountableUnits),
    );

    const resetEditorState = (): void => {
        setTargetOperator(item?.targetOperator ?? 'none');
        setSelectedOrganizationalUnitIds(
            item?.coAccountableOrganizationalUnits.map(
                (organizationalUnit) => organizationalUnit.id,
            ) ?? [],
        );
        setEvidenceRequirements(
            initialList(item?.documentaryEvidenceRequirements),
        );
        setManualUnits(initialList(item?.manualCoAccountableUnits));
    };

    const updateOpen = (nextOpen: boolean): void => {
        resetEditorState();
        setOpen(nextOpen);
    };

    const formRoute =
        mode === 'create'
            ? storePlanItem.form([teamSlug, operationalPlanId, keyResultAreaId])
            : updatePlanItem.form([
                  teamSlug,
                  operationalPlanId,
                  keyResultAreaId,
                  item?.id ?? 0,
              ]);
    const idPrefix = `${mode}-plan-item-${item?.id ?? keyResultAreaId}`;

    return (
        <Dialog open={open} onOpenChange={updateOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'create' ? 'Add Plan Item' : 'Edit Plan Item'}
                    </DialogTitle>
                    <DialogDescription>
                        Keep the official KPI wording in the KPI / Target field.
                        Structured target fields are optional.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...formRoute}
                    errorBag={`${mode}PlanItem${item?.id ?? keyResultAreaId}`}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={mode === 'create'}
                    disableWhileProcessing
                    onSuccess={() => {
                        resetEditorState();
                        setOpen(false);
                    }}
                    className="grid gap-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-5 lg:grid-cols-2">
                                <div className="grid gap-2 lg:col-span-2">
                                    <Label htmlFor={`${idPrefix}-objective`}>
                                        Objective
                                    </Label>
                                    <Textarea
                                        id={`${idPrefix}-objective`}
                                        name="objective"
                                        defaultValue={item?.objective ?? ''}
                                        required
                                        aria-invalid={Boolean(errors.objective)}
                                    />
                                    <InputError message={errors.objective} />
                                </div>

                                <div className="grid gap-2 lg:col-span-2">
                                    <Label htmlFor={`${idPrefix}-strategy`}>
                                        Strategy
                                    </Label>
                                    <Textarea
                                        id={`${idPrefix}-strategy`}
                                        name="strategy"
                                        defaultValue={item?.strategy ?? ''}
                                        aria-invalid={Boolean(errors.strategy)}
                                    />
                                    <InputError message={errors.strategy} />
                                </div>

                                <div className="grid gap-2 lg:col-span-2">
                                    <Label htmlFor={`${idPrefix}-kpi`}>
                                        Key Performance Indicator / Target
                                    </Label>
                                    <Textarea
                                        id={`${idPrefix}-kpi`}
                                        name="kpi_target_text"
                                        defaultValue={item?.kpiTargetText ?? ''}
                                        required
                                        aria-invalid={Boolean(
                                            errors.kpi_target_text,
                                        )}
                                    />
                                    <p className="text-xs leading-5 text-muted-foreground">
                                        This text is authoritative in the
                                        official plan.
                                    </p>
                                    <InputError
                                        message={errors.kpi_target_text}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`${idPrefix}-target-operator`}
                                    >
                                        Structured target operator
                                    </Label>
                                    <input
                                        type="hidden"
                                        name="target_operator"
                                        value={
                                            targetOperator === 'none'
                                                ? ''
                                                : targetOperator
                                        }
                                    />
                                    <Select
                                        value={targetOperator}
                                        onValueChange={(value) =>
                                            setTargetOperator(
                                                value as
                                                    TargetOperator | 'none',
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id={`${idPrefix}-target-operator`}
                                            className="w-full"
                                            aria-invalid={Boolean(
                                                errors.target_operator,
                                            )}
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                Not specified
                                            </SelectItem>
                                            {targetOperators.map((operator) => (
                                                <SelectItem
                                                    key={operator.value}
                                                    value={operator.value}
                                                >
                                                    {operator.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.target_operator}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`${idPrefix}-target-value`}>
                                        Structured target value
                                    </Label>
                                    <Input
                                        id={`${idPrefix}-target-value`}
                                        name="target_value"
                                        type="number"
                                        min="0"
                                        step="any"
                                        defaultValue={item?.targetValue ?? ''}
                                        aria-invalid={Boolean(
                                            errors.target_value,
                                        )}
                                    />
                                    <InputError message={errors.target_value} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`${idPrefix}-target-unit`}>
                                        Target unit
                                    </Label>
                                    <Input
                                        id={`${idPrefix}-target-unit`}
                                        name="target_unit"
                                        defaultValue={item?.targetUnit ?? ''}
                                        placeholder="e.g. percent, reports"
                                        aria-invalid={Boolean(
                                            errors.target_unit,
                                        )}
                                    />
                                    <InputError message={errors.target_unit} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`${idPrefix}-target-frequency`}
                                    >
                                        Target frequency
                                    </Label>
                                    <Input
                                        id={`${idPrefix}-target-frequency`}
                                        name="target_frequency"
                                        defaultValue={
                                            item?.targetFrequency ?? ''
                                        }
                                        placeholder="e.g. per year"
                                        aria-invalid={Boolean(
                                            errors.target_frequency,
                                        )}
                                    />
                                    <InputError
                                        message={errors.target_frequency}
                                    />
                                </div>
                            </div>

                            <fieldset className="grid gap-3 rounded-xl border bg-muted/20 p-4">
                                <div className="space-y-1">
                                    <Label asChild>
                                        <legend>
                                            Linked co-accountable units
                                        </legend>
                                    </Label>
                                    <p className="text-xs leading-5 text-muted-foreground">
                                        Prefer registered organizational units.
                                        Use the manual list below only for units
                                        not yet registered.
                                    </p>
                                </div>
                                {selectedOrganizationalUnitIds.map(
                                    (organizationalUnitId) => (
                                        <input
                                            key={organizationalUnitId}
                                            type="hidden"
                                            name="co_accountable_organizational_unit_ids[]"
                                            value={organizationalUnitId}
                                        />
                                    ),
                                )}
                                <InputError
                                    message={
                                        errors.co_accountable_organizational_unit_ids
                                    }
                                />
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {organizationalUnits.map(
                                        (organizationalUnit) => {
                                            const selected =
                                                selectedOrganizationalUnitIds.includes(
                                                    organizationalUnit.id,
                                                );

                                            return (
                                                <div
                                                    key={organizationalUnit.id}
                                                    className="flex items-start gap-3 rounded-lg border bg-background p-3"
                                                >
                                                    <Checkbox
                                                        id={`${idPrefix}-organizational-unit-${organizationalUnit.id}`}
                                                        checked={selected}
                                                        disabled={
                                                            !organizationalUnit.isActive &&
                                                            !selected
                                                        }
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            setSelectedOrganizationalUnitIds(
                                                                checked === true
                                                                    ? [
                                                                          ...selectedOrganizationalUnitIds,
                                                                          organizationalUnit.id,
                                                                      ]
                                                                    : selectedOrganizationalUnitIds.filter(
                                                                          (
                                                                              id,
                                                                          ) =>
                                                                              id !==
                                                                              organizationalUnit.id,
                                                                      ),
                                                            )
                                                        }
                                                    />
                                                    <Label
                                                        htmlFor={`${idPrefix}-organizational-unit-${organizationalUnit.id}`}
                                                        className="min-w-0 font-normal"
                                                    >
                                                        <span className="block font-medium">
                                                            {
                                                                organizationalUnit.name
                                                            }
                                                        </span>
                                                        <span className="text-xs text-muted-foreground">
                                                            {organizationalUnit.code ??
                                                                'No code'}
                                                            {!organizationalUnit.isActive &&
                                                                ' · Inactive'}
                                                        </span>
                                                    </Label>
                                                </div>
                                            );
                                        },
                                    )}
                                </div>
                            </fieldset>

                            <StringListFields
                                idPrefix={`${idPrefix}-manual-unit`}
                                label="Manual co-accountable units"
                                description="Temporary text fallback for units that do not exist in the organizational unit directory."
                                fieldName="manual_co_accountable_units"
                                values={manualUnits}
                                errors={errors}
                                onChange={setManualUnits}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor={`${idPrefix}-resources`}>
                                    Resources Needed
                                </Label>
                                <Textarea
                                    id={`${idPrefix}-resources`}
                                    name="resources_needed"
                                    defaultValue={item?.resourcesNeeded ?? ''}
                                    aria-invalid={Boolean(
                                        errors.resources_needed,
                                    )}
                                />
                                <InputError message={errors.resources_needed} />
                            </div>

                            <StringListFields
                                idPrefix={`${idPrefix}-evidence`}
                                label="Documentary Evidence Requirements"
                                description="Describe the evidence that should exist when an accomplishment is reported."
                                fieldName="documentary_evidence_requirements"
                                values={evidenceRequirements}
                                errors={errors}
                                onChange={setEvidenceRequirements}
                            />

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => updateOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? <Spinner /> : <CirclePlus />}
                                    {processing
                                        ? 'Saving...'
                                        : mode === 'create'
                                          ? 'Add Plan Item'
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
