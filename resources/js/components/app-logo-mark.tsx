import { Utensils } from 'lucide-react';

export function AppLogo({ name, subtitle }: { name: string; subtitle?: string | null }) {
    return (
        <>
            <div className="bg-primary text-primary-foreground shadow-primary/20 flex h-11 w-11 items-center justify-center rounded-2xl shadow-lg">
                <Utensils className="h-5 w-5" />
            </div>
            <div className="min-w-0 group-data-[collapsible=icon]:hidden">
                <div className="text-sidebar-foreground truncate text-sm font-semibold">{name}</div>
                <div className="text-sidebar-foreground/55 truncate text-xs">{subtitle || 'Operations Suite'}</div>
            </div>
        </>
    );
}
