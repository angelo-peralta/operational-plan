import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/operational-plans';
import type { KeyResultArea, OperationalPlan, PlanItem } from '@/types';

type Props = {
    institutionName: string;
    operationalPlan: OperationalPlan;
};

function accountableLabel(operationalPlan: OperationalPlan): string {
    const name =
        operationalPlan.accountableUser?.name ??
        operationalPlan.accountableName;

    return (
        [name, operationalPlan.accountablePosition]
            .filter((value): value is string => Boolean(value))
            .join(' — ') || '—'
    );
}

function coAccountableUnits(item: PlanItem): string[] {
    return [
        ...item.coAccountableDepartments.map(
            (department) => department.code ?? department.name,
        ),
        ...item.manualCoAccountableUnits,
    ];
}

function OfficialPlanItemRow({
    keyResultArea,
    item,
    itemIndex,
}: {
    keyResultArea: KeyResultArea;
    item: PlanItem;
    itemIndex: number;
}) {
    const units = coAccountableUnits(item);

    return (
        <tr>
            {itemIndex === 0 && (
                <td
                    rowSpan={keyResultArea.planItems.length}
                    className="official-kra-cell"
                >
                    {keyResultArea.code && (
                        <strong>{keyResultArea.code}</strong>
                    )}
                    {keyResultArea.code && <br />}
                    {keyResultArea.name}
                </td>
            )}
            <td>{item.objective}</td>
            <td>{item.strategy ?? '—'}</td>
            <td className="font-semibold">{item.kpiTargetText}</td>
            <td>{units.length > 0 ? units.join(', ') : '—'}</td>
            <td>{item.resourcesNeeded ?? '—'}</td>
            <td>
                {item.documentaryEvidenceRequirements.length > 0 ? (
                    <ul>
                        {item.documentaryEvidenceRequirements.map(
                            (requirement, index) => (
                                <li key={`${requirement}-${index}`}>
                                    {requirement}
                                </li>
                            ),
                        )}
                    </ul>
                ) : (
                    '—'
                )}
            </td>
            <td className="official-rating-cell">—</td>
            <td className="official-rating-cell">—</td>
            <td>—</td>
        </tr>
    );
}

function EmptyKeyResultAreaRow({
    keyResultArea,
}: {
    keyResultArea: KeyResultArea;
}) {
    return (
        <tr>
            <td className="official-kra-cell">
                {keyResultArea.code && <strong>{keyResultArea.code}</strong>}
                {keyResultArea.code && <br />}
                {keyResultArea.name}
            </td>
            <td colSpan={9} className="text-center italic">
                No Plan Items recorded.
            </td>
        </tr>
    );
}

export default function OperationalPlanOfficial({
    institutionName,
    operationalPlan,
}: Props) {
    const { currentTeam } = usePage().props;

    return (
        <main className="official-plan min-h-screen bg-slate-100 p-4 text-black sm:p-8">
            <Head
                title={`${operationalPlan.department.code ?? operationalPlan.department.name} Official Operational Plan`}
            />

            <div className="print-actions mx-auto mb-4 flex max-w-[1600px] flex-wrap items-center justify-between gap-3">
                {currentTeam && (
                    <Button variant="outline" asChild>
                        <Link
                            href={show([currentTeam.slug, operationalPlan.id])}
                        >
                            <ArrowLeft />
                            Back to editor
                        </Link>
                    </Button>
                )}
                <Button onClick={() => window.print()}>
                    <Printer />
                    Print
                </Button>
            </div>

            <article className="official-plan-sheet mx-auto max-w-[1600px] bg-white p-6 shadow-sm sm:p-10">
                <header className="official-header text-center">
                    <p className="text-sm font-semibold tracking-wide uppercase">
                        {institutionName}
                    </p>
                    <h1 className="mt-2 text-2xl font-bold tracking-wide uppercase">
                        Operational Plan
                    </h1>
                    <p className="mt-1 text-sm font-semibold">
                        {operationalPlan.academicYear.name}
                    </p>
                </header>

                <section className="mt-6 grid border border-black text-sm sm:grid-cols-2">
                    <div className="border-b border-black p-3 sm:border-r">
                        <span className="font-bold">Academic Year:</span>{' '}
                        {operationalPlan.academicYear.name}
                    </div>
                    <div className="border-b border-black p-3">
                        <span className="font-bold">Office:</span>{' '}
                        {operationalPlan.department.name}
                        {operationalPlan.department.code
                            ? ` (${operationalPlan.department.code})`
                            : ''}
                    </div>
                    <div className="border-b border-black p-3 sm:col-span-2">
                        <span className="font-bold">Accountable:</span>{' '}
                        {accountableLabel(operationalPlan)}
                    </div>
                    <div className="p-3 sm:col-span-2">
                        <span className="font-bold">Goal:</span>{' '}
                        {operationalPlan.goal}
                    </div>
                </section>

                <div className="mt-6 overflow-x-auto">
                    <table className="official-plan-table w-full min-w-[1400px] border-collapse text-[11px] leading-4">
                        <thead>
                            <tr>
                                <th rowSpan={2}>Key Result Area</th>
                                <th rowSpan={2}>Objectives</th>
                                <th rowSpan={2}>Strategies</th>
                                <th rowSpan={2}>
                                    Key Performance Indicators / Targets
                                </th>
                                <th rowSpan={2}>Unit Co-Accountable</th>
                                <th rowSpan={2}>Resources Needed</th>
                                <th rowSpan={2}>Documentary Evidence</th>
                                <th colSpan={2}>Evaluation Rating</th>
                                <th rowSpan={2}>Remarks</th>
                            </tr>
                            <tr>
                                <th>First Semester</th>
                                <th>Second Semester</th>
                            </tr>
                        </thead>
                        <tbody>
                            {operationalPlan.keyResultAreas.map(
                                (keyResultArea) =>
                                    keyResultArea.planItems.length > 0 ? (
                                        keyResultArea.planItems.map(
                                            (item, itemIndex) => (
                                                <OfficialPlanItemRow
                                                    key={item.id}
                                                    keyResultArea={
                                                        keyResultArea
                                                    }
                                                    item={item}
                                                    itemIndex={itemIndex}
                                                />
                                            ),
                                        )
                                    ) : (
                                        <EmptyKeyResultAreaRow
                                            key={keyResultArea.id}
                                            keyResultArea={keyResultArea}
                                        />
                                    ),
                            )}
                            {operationalPlan.keyResultAreas.length === 0 && (
                                <tr>
                                    <td colSpan={10} className="text-center">
                                        No Key Result Areas recorded.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <section className="mt-6 text-xs leading-5">
                    <p className="font-bold">Evaluation Criteria</p>
                    <p>
                        3 = Exceeded Requirements &nbsp; · &nbsp; 2 = Met
                        Requirements &nbsp; · &nbsp; 1 = Somehow Met
                        Requirements &nbsp; · &nbsp; 0 = Did Not Meet
                        Requirements
                    </p>
                </section>
            </article>
        </main>
    );
}
