import type { NavItem } from '@/types';
import {
    Bell,
    ClipboardList,
    CookingPot,
    LayoutGrid,
    MessageSquare,
    Package,
    Receipt,
    Settings2,
    SquareMenu,
    Table2,
    Users,
    UtensilsCrossed,
} from 'lucide-react';

const NAV_ITEMS: NavItem[] = [
    { titleKey: 'nav.dashboard', url: '/dashboard', icon: LayoutGrid, roles: ['admin', 'manager', 'waiter', 'cashier', 'kitchen'] },
    { titleKey: 'nav.tables', url: '/tables', icon: Table2, roles: ['admin', 'manager', 'waiter', 'cashier'] },
    { titleKey: 'nav.orders', url: '/orders', icon: ClipboardList, roles: ['admin', 'manager', 'waiter', 'cashier'] },
    { titleKey: 'nav.kitchen', url: '/kitchen', icon: CookingPot, roles: ['admin', 'manager', 'kitchen'] },
    { titleKey: 'nav.invoices', url: '/invoices', icon: Receipt, roles: ['admin', 'manager', 'cashier'] },
    { titleKey: 'nav.menu.categories', url: '/menu/categories', icon: UtensilsCrossed, roles: ['admin', 'manager'] },
    { titleKey: 'nav.menu.items', url: '/menu/items', icon: SquareMenu, roles: ['admin', 'manager'] },
    { titleKey: 'nav.inventory', url: '/inventory', icon: Package, roles: ['admin', 'manager'] },
    { titleKey: 'nav.reports', url: '/reports', icon: LayoutGrid, roles: ['admin', 'manager'] },
    { titleKey: 'nav.messages', url: '/messages', icon: MessageSquare, roles: ['admin', 'manager', 'waiter', 'cashier', 'kitchen'] },
    { titleKey: 'nav.notifications', url: '/notifications', icon: Bell, roles: ['admin', 'manager', 'waiter', 'cashier', 'kitchen'] },
    { titleKey: 'nav.users', url: '/users', icon: Users, roles: ['admin'] },
    { titleKey: 'nav.branch_settings', url: '/settings/branch', icon: Settings2, roles: ['admin', 'manager'] },
];

export function getNavigationForRoles(roles: string[] = []): NavItem[] {
    return NAV_ITEMS.filter((item) => item.roles?.some((role) => roles.includes(role)));
}
