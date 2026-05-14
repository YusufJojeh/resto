import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Bell, CheckCheck, Trash2 } from 'lucide-react';
import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

interface NotificationItem {
    id: string;
    data: { title?: string; body?: string };
    read_at: string | null;
    created_at: string;
}
interface Paginated<T> { data: T[] }

interface NotificationsProps {
    notifications: Paginated<NotificationItem>;
    unreadCount: number;
}

function timeAgo(dateStr: string, t: (key: string, params?: Record<string, string | number>) => string): string {
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000 / 60);
    if (diff < 1) return t('notifications.just_now');
    if (diff < 60) return t('notifications.minutes_ago', { n: diff });
    return t('notifications.hours_ago', { n: Math.floor(diff / 60) });
}

export default function NotificationsIndex({ notifications, unreadCount }: NotificationsProps) {
    const { t } = useTranslation();
    const { auth } = usePage<SharedData>().props;
    const breadcrumbs: BreadcrumbItem[] = [{ title: t('breadcrumbs.notifications'), href: '/notifications' }];

    useEffect(() => {
        if (!auth.user?.id || !(window as any).Echo) return;
        const channel = (window as any).Echo.private(`user.${auth.user.id}`);
        channel.notification(() => {
            router.reload({ only: ['notifications', 'unreadCount', 'auth'] });
        });
        return () => {
            (window as any).Echo.leave(`private-user.${auth.user.id}`);
        };
    }, [auth.user?.id]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('notifications.title')} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <PageHeader title={t('notifications.title')} description={unreadCount > 0 ? t('notifications.unread_count', { n: unreadCount }) : undefined} />
                    {notifications.data.length > 0 && (
                        <Button variant="outline" size="sm" className="shrink-0 gap-2" onClick={() => router.post(route('notifications.read_all'))}>
                            <CheckCheck className="h-4 w-4" aria-hidden />
                            {t('notifications.mark_all_read')}
                        </Button>
                    )}
                </div>

                {notifications.data.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border border-dashed py-20 text-center">
                        <div className="bg-muted flex h-14 w-14 items-center justify-center rounded-full">
                            <Bell className="text-muted-foreground h-7 w-7" aria-hidden />
                        </div>
                        <p className="text-muted-foreground text-sm">{t('notifications.empty')}</p>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        {notifications.data.map((notification, idx) => (
                            <div key={notification.id}>
                                <div className={`hover:bg-muted/40 flex items-start gap-4 px-4 py-4 transition-colors ${!notification.read_at ? 'bg-accent/5' : ''}`}>
                                    <div className={`mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${!notification.read_at ? 'bg-accent/10' : 'bg-muted'}`}>
                                        <Bell className={`h-4 w-4 ${!notification.read_at ? 'text-accent' : 'text-muted-foreground'}`} aria-hidden />
                                    </div>
                                    <button
                                        type="button"
                                        className="min-w-0 flex-1 text-left"
                                        onClick={() => router.post(route('notifications.read', notification.id))}
                                    >
                                        <div className="flex items-center gap-2">
                                            <p className="text-sm font-medium">{notification.data.title ?? t('notifications.title')}</p>
                                            {!notification.read_at && (
                                                <Badge variant="secondary" className="bg-accent/10 text-accent h-5 px-1.5 text-[10px]">
                                                    {t('notifications.new')}
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-muted-foreground mt-0.5 text-xs">{notification.data.body ?? t('common.na')}</p>
                                    </button>
                                    <div className="flex items-center gap-2">
                                        <span className="text-muted-foreground shrink-0 text-xs">{timeAgo(notification.created_at, t)}</span>
                                        <Button type="button" size="icon" variant="ghost" onClick={() => router.delete(route('notifications.destroy', notification.id))}>
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                {idx < notifications.data.length - 1 && <Separator />}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
