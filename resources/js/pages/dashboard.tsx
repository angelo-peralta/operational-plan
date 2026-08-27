import { Head, usePage } from '@inertiajs/react';
import { Building2, CalendarRange, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { StatusBadge } from '@/components/status-badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, home } from '@/routes';
import type { DashboardInvitation } from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
};

export default function Dashboard({ pendingInvitations = [] }: Props) {
    const { auth, academicYears, selectedAcademicYear } = usePage().props;
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="relative overflow-hidden rounded-2xl border bg-card p-6 shadow-sm md:p-8">
                    <div className="absolute top-0 right-0 size-48 translate-x-12 -translate-y-16 rounded-full bg-primary/5" />
                    <div className="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-3">
                            <p className="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                                Active planning context
                            </p>
                            <div className="flex flex-wrap items-center gap-3">
                                <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">
                                    {selectedAcademicYear?.name ??
                                        'No Academic Year selected'}
                                </h1>
                                {selectedAcademicYear && (
                                    <StatusBadge
                                        status={selectedAcademicYear.status}
                                    />
                                )}
                            </div>
                            <p className="max-w-2xl text-sm leading-6 text-muted-foreground md:text-base">
                                {selectedAcademicYear
                                    ? `All planning, monitoring, evidence, and reporting views are scoped to ${selectedAcademicYear.name}.`
                                    : academicYears.length > 0
                                      ? 'Choose an Academic Year from the header before working with planning records.'
                                      : 'An administrator must create an Academic Year before planning can begin.'}
                            </p>
                        </div>
                        <div className="flex size-16 shrink-0 items-center justify-center rounded-2xl border bg-background shadow-sm">
                            <CalendarRange className="size-8 text-primary" />
                        </div>
                    </div>
                </section>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-4">
                            <div className="space-y-1.5">
                                <CardTitle>Academic Year</CardTitle>
                                <CardDescription>
                                    Current system-wide context
                                </CardDescription>
                            </div>
                            <CalendarRange className="size-5 text-muted-foreground" />
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <p className="text-lg font-semibold">
                                {selectedAcademicYear?.name ?? 'Not selected'}
                            </p>
                            {selectedAcademicYear ? (
                                <p className="text-sm text-muted-foreground">
                                    {selectedAcademicYear.startYear} to{' '}
                                    {selectedAcademicYear.endYear}
                                </p>
                            ) : (
                                <p className="text-sm text-amber-700 dark:text-amber-300">
                                    Select a year in the header to continue.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-4">
                            <div className="space-y-1.5">
                                <CardTitle>Department</CardTitle>
                                <CardDescription>
                                    Your organizational assignment
                                </CardDescription>
                            </div>
                            <Building2 className="size-5 text-muted-foreground" />
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <p className="text-lg font-semibold">
                                {auth.user.department?.name ?? 'Not assigned'}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {auth.user.department?.code ??
                                    'Administrative access is not department-scoped.'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-4">
                            <div className="space-y-1.5">
                                <CardTitle>Access role</CardTitle>
                                <CardDescription>
                                    Permissions are enforced by the server
                                </CardDescription>
                            </div>
                            <ShieldCheck className="size-5 text-muted-foreground" />
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <p className="text-lg font-semibold">
                                {auth.user.roleLabel ?? 'Unassigned'}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {auth.user.role === 'department_user'
                                    ? 'Access is limited to your assigned department.'
                                    : 'Available navigation reflects your assigned role.'}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam
                ? dashboard(props.currentTeam.slug)
                : home(),
        },
    ],
});
