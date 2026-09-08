import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { home } from '@/routes';
import { index } from '@/routes/administration/roles';
import type { UserRole } from '@/types';

const descriptions: Record<UserRole, string> = {
    system_admin:
        'Manage Academic Years, organizational units, users and assignments. Administrative access does not grant executive evaluation authority.',
    executive:
        'President and Vice Presidents share this role. Assign the President to the university root and each VP to their area. Authority follows the hierarchy.',
    office_head:
        'Manage and submit plans and accomplishments for directly assigned units.',
    staff: 'Prepare drafts, record accomplishments and upload evidence for assigned units. Final submission belongs to the accountable head.',
};

export default function RolesIndex({
    roles,
}: {
    roles: { value: UserRole; label: string }[];
}) {
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return null;
    }

    return (
        <>
            <Head title="System roles" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="System roles"
                    description="Four roles define responsibilities. Organizational assignments determine where those responsibilities apply."
                />
                <div className="grid gap-4 md:grid-cols-2">
                    {roles.map((role) => (
                        <Card key={role.value}>
                            <CardHeader>
                                <CardTitle>{role.label}</CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm text-muted-foreground">
                                {descriptions[role.value]}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
RolesIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'System roles',
            href: props.currentTeam ? index(props.currentTeam.slug) : home(),
        },
    ],
});
