import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    CalendarRange,
    ClipboardList,
    Gauge,
    LayoutGrid,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';
import { dashboard, home } from '@/routes';
import { index as academicYearsIndex } from '@/routes/academic-years';
import { index as departmentsIndex } from '@/routes/administration/departments';
import { index as usersIndex } from '@/routes/administration/users';
import { index as monitoringIndex } from '@/routes/monitoring';
import { index as operationalPlansIndex } from '@/routes/operational-plans';

export function AppSidebar() {
    const page = usePage();
    const { auth, currentTeam } = page.props;
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : home();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(currentTeam
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
              ]
            : []),
        ...(auth.user.role === 'super_admin' && currentTeam
            ? [
                  {
                      title: 'Academic Years',
                      href: academicYearsIndex(currentTeam.slug),
                      icon: CalendarRange,
                  },
                  {
                      title: 'Departments',
                      href: departmentsIndex(currentTeam.slug),
                      icon: Building2,
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
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
