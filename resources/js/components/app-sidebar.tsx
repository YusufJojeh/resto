import { useTranslation } from '@/i18n/use-translation';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BookOpen,
    Bot,
    ChartColumnBig,
    ChefHat,
    Layers,
    ChevronsUpDown,
    ClipboardList,
    LayoutDashboard,
    MessageSquare,
    Package,
    Receipt,
    Settings,
    Store,
    Table2,
    Users,
} from 'lucide-react';
import { AppLogo } from './app-logo-mark';
import { Avatar, AvatarFallback, AvatarImage } from './ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from './ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from './ui/sidebar';
import { UserMenuContent } from './user-menu-content';

interface NavItem {
    label: string;
    href: string;
    icon: React.ElementType;
    roles?: string[];
    exact?: boolean;
}

function initials(name: string | undefined) {
    return String(name ?? 'RC')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

export function AppSidebar() {
    const page = usePage<SharedData>();
    const { t } = useTranslation();
    const roles = page.props.auth.user?.roles ?? [];
    const current = page.url;
    const branding = page.props.branding;
    const features = page.props.features ?? { messages: true, notifications: true, assistant: true, realtime: true };

    const groups: Array<{ label: string; items: NavItem[] }> = [
        {
            label: t('nav.dashboard'),
            items: [
                { label: t('nav.dashboard'), href: '/dashboard', icon: LayoutDashboard, exact: true },
                { label: t('nav.tables'), href: '/tables', icon: Table2 },
                { label: t('nav.orders'), href: '/orders', icon: ClipboardList },
                { label: t('nav.kitchen'), href: '/kitchen', icon: ChefHat, roles: ['admin', 'manager', 'kitchen'] },
                { label: t('nav.invoices'), href: '/invoices', icon: Receipt, roles: ['admin', 'manager', 'cashier'] },
            ],
        },
        {
            label: 'Management',
            items: [
                { label: 'Menu Management', href: '/menu/items', icon: BookOpen, roles: ['admin', 'manager'] },
                { label: t('nav.inventory'), href: '/inventory', icon: Package, roles: ['admin', 'manager'] },
                { label: t('nav.reports'), href: '/reports', icon: ChartColumnBig, roles: ['admin', 'manager'] },
                { label: 'Team', href: '/users', icon: Users, roles: ['admin'] },
            ],
        },
        {
            label: 'Communication',
            items: [
                { label: t('nav.messages'), href: '/messages', icon: MessageSquare },
                { label: t('nav.notifications'), href: '/notifications', icon: Bell },
                { label: t('ai.title'), href: '/assistant', icon: Bot },
            ],
        },
        {
            label: 'Settings',
            items: [
                { label: 'Branch', href: '/settings/branch', icon: Store, roles: ['admin', 'manager'] },
                { label: 'Subscription plans', href: '/settings/plans', icon: Layers, roles: ['admin'] },
                { label: 'Settings', href: '/settings/profile', icon: Settings },
            ],
        },
    ];

    const canView = (item: NavItem) => {
        if (item.href === '/messages' && !features.messages) return false;
        if (item.href === '/notifications' && !features.notifications) return false;
        if (item.href === '/assistant' && !features.assistant) return false;
        return !item.roles || item.roles.some((role) => roles.includes(role));
    };
    const isActive = (item: NavItem) => (item.exact ? current === item.href : current === item.href || current.startsWith(`${item.href}/`));

    return (
        <Sidebar collapsible="icon" variant="inset" className="border-sidebar-border">
            <SidebarHeader className="border-sidebar-border/70 border-b px-3 py-4">
                <Link href="/dashboard" className="flex items-center gap-3">
                    <AppLogo name={branding.business_name || 'RestoCafe'} subtitle={branding.tagline || 'Hospitality OS'} />
                </Link>
            </SidebarHeader>

            <SidebarContent className="px-2 py-4">
                {groups.map((group) => {
                    const items = group.items.filter(canView);
                    if (!items.length) return null;

                    return (
                        <SidebarGroup key={group.label} className="px-0 py-0">
                            <SidebarGroupLabel className="text-sidebar-foreground/45 px-3 text-[11px] tracking-[0.22em] uppercase">
                                {group.label}
                            </SidebarGroupLabel>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    {items.map((item) => (
                                        <SidebarMenuItem key={item.href}>
                                            <SidebarMenuButton
                                                asChild
                                                isActive={isActive(item)}
                                                tooltip={item.label}
                                                className={cn(
                                                    'text-sidebar-foreground/75 hover:bg-sidebar-accent/90 hover:text-sidebar-foreground data-[active=true]:bg-primary/12 data-[active=true]:text-primary h-11 rounded-xl px-3 text-sm',
                                                    isActive(item) && 'shadow-primary/10 shadow-lg',
                                                )}
                                            >
                                                <Link href={item.href}>
                                                    <item.icon className="h-4 w-4" />
                                                    <span>{item.label}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    ))}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </SidebarGroup>
                    );
                })}
            </SidebarContent>

            <SidebarFooter className="border-sidebar-border/70 border-t p-3">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button className="border-sidebar-border/80 bg-sidebar-accent/70 hover:bg-sidebar-accent flex w-full items-center gap-3 rounded-2xl border px-3 py-3 text-left transition">
                            <Avatar className="h-10 w-10 rounded-2xl">
                                <AvatarImage src={page.props.auth.user?.avatar} alt={page.props.auth.user?.name} />
                                <AvatarFallback className="bg-primary/15 text-primary rounded-2xl">
                                    {initials(page.props.auth.user?.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0 flex-1 group-data-[collapsible=icon]:hidden">
                                <p className="text-sidebar-foreground truncate text-sm font-medium">{page.props.auth.user?.name}</p>
                                <p className="text-sidebar-foreground/55 truncate text-xs">{page.props.auth.user?.email}</p>
                            </div>
                            <ChevronsUpDown className="text-sidebar-foreground/40 h-4 w-4 group-data-[collapsible=icon]:hidden" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="w-64" align="end" side="top">
                        {page.props.auth.user ? <UserMenuContent user={page.props.auth.user} /> : null}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
