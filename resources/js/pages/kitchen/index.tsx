import { ConnectionPill, type ConnectionState } from '@/components/connection-pill';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import echo from '@/lib/echo';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

const POLL_MS = 5000;

export default function KitchenIndex({ orders: initialOrders }: { orders: Record<string, unknown>[] }) {
    const { t } = useTranslation();
    const [orders, setOrders] = useState(initialOrders);
    const [connectionState, setConnectionState] = useState<ConnectionState>('live');
    const [lastRefresh, setLastRefresh] = useState<string | null>(null);
    const consecutiveFailures = useRef(0);

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.kitchen'), href: '/kitchen' }];
    const branchId = usePage<SharedData>().props.auth.user?.branch_id;

    const poll = useCallback(async () => {
        if (typeof document !== 'undefined' && document.visibilityState !== 'visible') {
            return;
        }
        if (typeof navigator !== 'undefined' && !navigator.onLine) {
            setConnectionState('offline');
            return;
        }
        try {
            const response = await fetch(route('kitchen.queue'), {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                throw new Error('poll failed');
            }
            const payload = (await response.json()) as { orders: Record<string, unknown>[] };
            setOrders(payload.orders);
            const ts = new Date().toISOString();
            setLastRefresh(ts);
            consecutiveFailures.current = 0;
            setConnectionState('live');
        } catch {
            consecutiveFailures.current += 1;
            setConnectionState(consecutiveFailures.current >= 2 ? 'offline' : 'reconnecting');
        }
    }, []);

    useEffect(() => {
        void poll();
        const interval = window.setInterval(() => void poll(), POLL_MS);

        const onVisibility = () => {
            if (document.visibilityState === 'visible') {
                void poll();
            }
        };
        const onOnline = () => {
            consecutiveFailures.current = 0;
            setConnectionState('live');
            void poll();
        };
        const onOffline = () => setConnectionState('offline');

        document.addEventListener('visibilitychange', onVisibility);
        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);

        return () => {
            window.clearInterval(interval);
            document.removeEventListener('visibilitychange', onVisibility);
            window.removeEventListener('online', onOnline);
            window.removeEventListener('offline', onOffline);
        };
    }, [poll]);

    useEffect(() => {
        if (!branchId) return;
        const channel = echo.private(`branch.${branchId}`);
        channel.listen('.order.created', () => void poll());
        channel.listen('.order.status_changed', () => void poll());
        return () => {
            echo.leave(`branch.${branchId}`);
        };
    }, [branchId, poll]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('kitchen.title')} />
            <div className="data-[density=kitchen]:density-compact space-y-6 p-4" data-density="kitchen">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <PageHeader title={t('kitchen.title')} description={t('kitchen.description')} />
                    <div className="flex flex-col items-start gap-2 sm:items-end">
                        <ConnectionPill state={connectionState} lastUpdate={lastRefresh ?? undefined} />
                        <p className="text-muted-foreground text-xs" aria-live="polite">
                            {t('kitchen.poll_interval', { seconds: POLL_MS / 1000 })}
                            {lastRefresh ? (
                                <>
                                    {' · '}
                                    {t('kitchen.last_refresh')}: <time dateTime={lastRefresh}>{new Date(lastRefresh).toLocaleTimeString()}</time>
                                </>
                            ) : null}
                        </p>
                    </div>
                </div>

                <div aria-live="polite" className="sr-only">
                    {t('kitchen.screen_reader_count', { count: orders.length })}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                    {orders.length === 0 ? (
                        <p className="text-muted-foreground col-span-full text-center">{t('kitchen.empty')}</p>
                    ) : (
                        orders.map((order) => {
                            const o = order as {
                                id: number;
                                status: string;
                                table?: { number: number };
                                items: Array<{ id: number; menu_item_name: string; quantity: number; notes?: string | null }>;
                            };
                            return (
                                <Card key={o.id} className="border-primary/10 shadow-sm">
                                    <CardHeader className="pb-3">
                                        <CardTitle className="flex flex-wrap items-center justify-between gap-2 text-lg">
                                            <span className="font-semibold tracking-tight">{t('kitchen.order_number', { id: o.id })}</span>
                                            <StatusBadge value={o.status} />
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <p className="text-muted-foreground text-base">
                                            {t('kitchen.table')}:{' '}
                                            <span className="text-foreground font-medium">{o.table?.number ?? t('common.na')}</span>
                                        </p>
                                        <div className="space-y-2">
                                            {o.items.map((item) => (
                                                <div
                                                    key={item.id}
                                                    className="border-border/80 bg-muted/30 rounded-lg border p-3 text-base leading-snug"
                                                >
                                                    <div className="flex justify-between gap-2 font-medium">
                                                        <span>{item.menu_item_name}</span>
                                                        <span className="text-muted-foreground tabular-nums">×{item.quantity}</span>
                                                    </div>
                                                    {item.notes ? <p className="text-muted-foreground mt-1 text-sm">{item.notes}</p> : null}
                                                </div>
                                            ))}
                                        </div>
                                        <Button
                                            type="button"
                                            className="min-h-12 w-full touch-manipulation text-base font-semibold"
                                            onClick={() => router.patch(route('kitchen.ready', o.id))}
                                        >
                                            {t('kitchen.mark_ready')}
                                        </Button>
                                    </CardContent>
                                </Card>
                            );
                        })
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
