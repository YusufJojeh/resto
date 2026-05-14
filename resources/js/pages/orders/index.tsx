import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import echo from '@/lib/echo';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface OrderRecord {
    id: number;
    status: string;
    created_at: string;
    table?: { number: number };
    user?: { name: string };
    items?: Array<unknown>;
    invoice?: { id: number } | null;
}

interface OrderStatusEvent {
    id: number;
    status: string;
}

interface OrderCreatedEvent {
    id: number;
    status: string;
    table_number?: number;
    created_at: string;
    items_count: number;
}

export default function OrdersIndex({ orders }: { orders: OrderRecord[] }) {
    const { locale, auth } = usePage<SharedData>().props;
    const { t } = useTranslation();
    const [orderList, setOrderList] = useState(orders);
    const breadcrumbs: BreadcrumbItem[] = [{ title: t('breadcrumbs.orders'), href: '/orders' }];
    const dateLocale = (locale as 'en' | 'ar') === 'ar' ? 'ar-SA' : undefined;
    const branchId = auth.user?.branch_id;

    useEffect(() => {
        if (!branchId) return;
        const channel = echo.private(`branch.${branchId}`);
        channel.listen('.order.created', (e: OrderCreatedEvent) => {
            setOrderList((prev) => {
                if (prev.some((o) => o.id === e.id)) return prev;
                const newOrder: OrderRecord = {
                    id: e.id,
                    status: e.status,
                    created_at: e.created_at,
                    table: e.table_number != null ? { number: e.table_number } : undefined,
                    items: Array.from({ length: e.items_count }),
                };
                return [newOrder, ...prev];
            });
        });
        channel.listen('.order.status_changed', (e: OrderStatusEvent) => {
            setOrderList((prev) => prev.map((o) => (o.id === e.id ? { ...o, status: e.status } : o)));
        });
        return () => {
            echo.leave(`branch.${branchId}`);
        };
    }, [branchId]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('orders.title')} />
            <div className="space-y-6 p-4">
                <PageHeader
                    title={t('orders.title')}
                    description={t('orders.description')}
                    actions={
                        <Button asChild className="min-h-11 touch-manipulation">
                            <Link href={route('orders.create')}>{t('pos.new_order')}</Link>
                        </Button>
                    }
                />
                <Card>
                    <CardContent className="pt-6">
                        {/* Desktop table */}
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full text-start text-sm">
                                <thead className="text-muted-foreground">
                                    <tr>
                                        <th className="pb-3">{t('orders.col_order')}</th>
                                        <th className="pb-3">{t('orders.col_table')}</th>
                                        <th className="pb-3">{t('orders.col_owner')}</th>
                                        <th className="pb-3">{t('orders.col_items')}</th>
                                        <th className="pb-3">{t('orders.col_status')}</th>
                                        <th className="pb-3">{t('orders.col_created')}</th>
                                        <th className="pb-3 text-end">{t('orders.col_actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {orderList.map((order) => (
                                        <tr key={order.id} className="border-t">
                                            <td className="py-3 font-medium">#{order.id}</td>
                                            <td className="py-3">{t('pos.table_number', { n: order.table?.number ?? '—' })}</td>
                                            <td className="py-3">{order.user?.name ?? t('common.na')}</td>
                                            <td className="py-3">{order.items?.length ?? 0}</td>
                                            <td className="py-3">
                                                <StatusBadge value={order.status} />
                                            </td>
                                            <td className="py-3 tabular-nums">{new Date(order.created_at).toLocaleString(dateLocale)}</td>
                                            <td className="py-3 text-end">
                                                <Button asChild variant="ghost" size="sm" className="min-h-11 min-w-[4.5rem]">
                                                    <Link href={route('orders.show', order.id)}>{t('common.view')}</Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile cards */}
                        <ul className="flex flex-col gap-3 md:hidden">
                            {orderList.map((order) => (
                                <li key={order.id} className="bg-card rounded-xl border p-4 shadow-sm">
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <p className="font-semibold">#{order.id}</p>
                                            <p className="text-muted-foreground text-sm">
                                                {t('pos.table_number', { n: order.table?.number ?? '—' })}
                                                {' · '}
                                                {order.user?.name ?? t('common.na')}
                                            </p>
                                            <p className="text-muted-foreground mt-1 text-xs tabular-nums">
                                                {new Date(order.created_at).toLocaleString(dateLocale)}
                                            </p>
                                        </div>
                                        <StatusBadge value={order.status} />
                                    </div>
                                    <div className="mt-4 flex items-center justify-between">
                                        <span className="text-muted-foreground text-sm">
                                            {t('orders.col_items')}: {order.items?.length ?? 0}
                                        </span>
                                        <Button asChild size="sm" className="min-h-11">
                                            <Link href={route('orders.show', order.id)}>{t('common.view')}</Link>
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
