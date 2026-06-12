import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    LifeBuoy,
    Package,
    ReceiptText,
    Settings2,
    Store,
    Tags,
    Truck,
    UsersRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminCategoriesIndex } from '@/routes/admin/categories';
import { index as adminOrdersIndex } from '@/routes/admin/orders';
import { index as adminProductsIndex } from '@/routes/admin/products';
import { edit as adminSettingsEdit } from '@/routes/admin/settings';
import { index as adminShippingIndex } from '@/routes/admin/shipping';
import { index as adminTicketsIndex } from '@/routes/admin/tickets';
import { index as adminUsersIndex } from '@/routes/admin/users';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Admin',
        href: adminDashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Products',
        href: adminProductsIndex(),
        icon: Package,
    },
    {
        title: 'Categories',
        href: adminCategoriesIndex(),
        icon: Tags,
    },
    {
        title: 'Orders',
        href: adminOrdersIndex(),
        icon: ReceiptText,
    },
    {
        title: 'Shipping',
        href: adminShippingIndex(),
        icon: Truck,
    },
    {
        title: 'Support',
        href: adminTicketsIndex(),
        icon: LifeBuoy,
    },
];

const adminOnlyNavItems: NavItem[] = [
    {
        title: 'Users',
        href: adminUsersIndex(),
        icon: UsersRound,
    },
    {
        title: 'Settings',
        href: adminSettingsEdit(),
        icon: Settings2,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Storefront',
        href: home(),
        icon: Store,
    },
];

export function AppSidebar() {
    const { auth } = usePage<{
        auth: { isStaff: boolean; isAdmin: boolean };
        [key: string]: unknown;
    }>().props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {auth.isStaff && (
                    <NavMain
                        items={
                            auth.isAdmin
                                ? [...adminNavItems, ...adminOnlyNavItems]
                                : adminNavItems
                        }
                        label="Shop admin"
                    />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
