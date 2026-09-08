import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    CalendarRange,
    ClipboardCheck,
    ClipboardList,
    FileChartColumn,
    Gauge,
    LayoutGrid,
    ShieldCheck,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import { index as academicYearsIndex } from '@/routes/academic-years';
import { index as organizationalUnitsIndex } from '@/routes/administration/organizational-units';
import { index as rolesIndex } from '@/routes/administration/roles';
import { index as usersIndex } from '@/routes/administration/users';
import { index as evaluationsIndex } from '@/routes/evaluations';
import { index as keyResultsIndex } from '@/routes/key-results';
import { index as monitoringIndex } from '@/routes/monitoring';
import { index as operationalPlansIndex } from '@/routes/operational-plans';
import { index as reportsIndex } from '@/routes/reports';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const { auth, currentTeam } = page.props;
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : home();
    const canReview = auth.user.permissions.viewEvaluations;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(currentTeam && auth.user.permissions.viewPlans
            ? [
                  {
                      title: 'Operational Plans',
                      href: operationalPlansIndex(currentTeam.slug),
                      icon: ClipboardList,
                  },
                  {
                      title: 'Monitoring',
                      href: monitoringIndex(currentTeam.slug),
                      icon: Gauge,
                  },
                  {
                      title: 'Reports',
                      href: reportsIndex(currentTeam.slug),
                      icon: FileChartColumn,
                  },
              ]
            : []),
        ...(canReview && currentTeam
            ? [
                  {
                      title: 'Evidence Review',
                      href: evaluationsIndex(currentTeam.slug),
                      icon: ClipboardCheck,
                  },
              ]
            : []),
        ...(auth.user.role === 'system_admin' && currentTeam
            ? [
                  {
                      title: 'KRA Management',
                      href: keyResultsIndex(currentTeam.slug),
                      icon: ClipboardList,
                  },
                  {
                      title: 'Academic Years',
                      href: academicYearsIndex(currentTeam.slug),
                      icon: CalendarRange,
                  },
                  {
                      title: 'Organizational units',
                      href: organizationalUnitsIndex(currentTeam.slug),
                      icon: Building2,
                  },
                  {
                      title: 'Roles',
                      href: rolesIndex(currentTeam.slug),
                      icon: ShieldCheck,
                  },
                  {
                      title: 'Users',
                      href: usersIndex(currentTeam.slug),
                      icon: Users,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <p className="px-4 py-2 text-xs text-muted-foreground group-data-[collapsible=icon]:hidden">
                    Scope: {auth.user.scopeLabel}
                </p>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
