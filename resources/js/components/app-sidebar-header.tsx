import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { UserMenuContent } from '@/components/user-menu-content';
import { useTranslation } from '@/i18n/use-translation';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Bell, Bot, Search } from 'lucide-react';

interface AppSidebarHeaderProps {
    breadcrumbs?: BreadcrumbItemType[];
    onOpenAiPanel?: () => void;
}

function initials(name: string | undefined) {
    return String(name ?? 'RC')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

export function AppSidebarHeader({ breadcrumbs = [], onOpenAiPanel }: AppSidebarHeaderProps) {
    const { t } = useTranslation();
    const { auth } = usePage<SharedData>().props;

    return (
        <header className="glass-strong border-border/60 sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-3 border-b px-4 md:px-6">
            <div className="flex min-w-0 items-center gap-3">
                <SidebarTrigger className="border-border/70 bg-card/60 h-10 w-10 rounded-xl border" />
                <div className="hidden min-w-0 md:block">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            </div>

            <div className="hidden max-w-xl min-w-[220px] flex-1 lg:block">
                <div className="relative">
                    <Search className="text-muted-foreground absolute inset-y-0 left-3 my-auto h-4 w-4" />
                    <Input
                        aria-label="Search"
                        placeholder="Search orders, tables, guests..."
                        className="border-border/70 bg-card/70 h-11 pr-4 pl-10"
                    />
                </div>
            </div>

            <div className="flex items-center gap-2">
                <div className="hidden md:block">
                    <LocaleSwitcher variant="compact" className="border-border/70 bg-card/70" />
                </div>
                <AppearanceToggleDropdown />
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button asChild variant="ghost" size="icon" className="h-10 w-10 rounded-xl">
                            <Link href="/notifications">
                                <Bell className="h-4 w-4" />
                            </Link>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>{t('notifications.title')}</TooltipContent>
                </Tooltip>
                {onOpenAiPanel ? (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-10 w-10 rounded-xl" onClick={onOpenAiPanel}>
                                <Bot className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>{t('ai.title')}</TooltipContent>
                    </Tooltip>
                ) : null}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button className="border-border/70 bg-card/60 rounded-2xl border p-1">
                            <Avatar className="h-9 w-9 rounded-2xl">
                                <AvatarImage src={auth.user?.avatar} alt={auth.user?.name} />
                                <AvatarFallback className="bg-primary/15 text-primary rounded-2xl">{initials(auth.user?.name)}</AvatarFallback>
                            </Avatar>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="w-64" align="end">
                        {auth.user ? <UserMenuContent user={auth.user} /> : null}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
