import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/format-currency';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';

interface MenuCategory {
    id: number;
    name: string;
    items: Array<{ id: number; name: string; price: string }>;
}

export default function OrderShow({
    order,
    categories,
}: {
    order: Record<string, unknown> & {
        id: number;
        status: string;
        items: Array<{ id: number; menu_item_name: string; quantity: number; notes?: string | null; subtotal: string }>;
        table?: { number: number };
        user?: { name: string };
        subtotal?: string;
    };
    categories: MenuCategory[];
}) {
    const { auth, branding, locale } = usePage<SharedData>().props;
    const { t } = useTranslation();
    const loc = (locale as 'en' | 'ar') ?? 'en';
    const currency = branding.currency_code;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('breadcrumbs.orders'), href: '/orders' },
        { title: t('order.show.title', { id: order.id }), href: `/orders/${order.id}` },
    ];

    const availableItems = categories.flatMap((category) =>
        category.items.map((item) => ({
            menu_item_id: String(item.id),
            quantity: '1',
            notes: '',
            selected: false,
            name: item.name,
            price: item.price,
        })),
    );

    const form = useForm({ items: availableItems });
    const { data, setData, processing } = form;

    const tableLabel = order.table?.number != null ? String(order.table.number) : t('common.na');
    const userLabel = order.user?.name ?? t('common.na');

    const computedSubtotal = Number(order.subtotal ?? order.items.reduce((sum, item) => sum + Number(item.subtotal), 0));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('order.show.title', { id: order.id })} />
            <div className="space-y-6 p-4">
                <PageHeader
                    title={t('order.show.title', { id: order.id })}
                    description={t('order.show.table_user', { table: tableLabel, user: userLabel })}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <StatusBadge value={order.status} />
                            {order.status === 'new' ? (
                                <>
                                    <Button variant="outline" className="min-h-11" onClick={() => router.patch(route('orders.submit', order.id))}>
                                        {t('order.show.send_kitchen')}
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        className="min-h-11"
                                        onClick={() =>
                                            router.patch(route('orders.cancel', order.id), {
                                                reason: 'Cancelled from order screen',
                                            })
                                        }
                                    >
                                        {t('order.show.cancel')}
                                    </Button>
                                </>
                            ) : null}
                            {order.status === 'ready' && ['admin', 'manager', 'cashier'].some((r) => auth.user?.roles?.includes(r)) ? (
                                <Button className="min-h-11" onClick={() => router.post(route('invoices.store', order.id))}>
                                    {t('order.show.create_invoice')}
                                </Button>
                            ) : null}
                        </div>
                    }
                />
                <div className="grid gap-6 xl:grid-cols-[1fr_360px] xl:items-start">
                    <Card className="min-w-0">
                        <CardHeader>
                            <CardTitle>{t('order.show.items_title')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {order.items.map((item) => (
                                <div key={item.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm">
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">{item.menu_item_name}</p>
                                        <p className="text-muted-foreground">
                                            {t('common.quantity_short')} {item.quantity}
                                            {item.notes ? ` • ${item.notes}` : ''}
                                        </p>
                                    </div>
                                    <div className="shrink-0 tabular-nums">{formatCurrency(Number(item.subtotal), currency, loc)}</div>
                                </div>
                            ))}
                            <div className="flex justify-between border-t pt-4 text-sm font-medium">
                                <span>{t('pos.subtotal')}</span>
                                <span className="tabular-nums">{formatCurrency(computedSubtotal, currency, loc)}</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="min-w-0 xl:sticky xl:top-4">
                        <CardHeader>
                            <CardTitle>{t('order.show.add_items_title')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {order.status === 'new' ? (
                                <>
                                    {data.items.slice(0, 6).map((item, index) => (
                                        <div key={item.menu_item_id} className="rounded-lg border p-3">
                                            <label className="flex min-h-11 touch-manipulation items-center justify-between gap-2 text-sm font-medium">
                                                <span>{item.name}</span>
                                                <input
                                                    type="checkbox"
                                                    className="size-5"
                                                    checked={item.selected}
                                                    onChange={(e) =>
                                                        setData(
                                                            'items',
                                                            data.items.map((entry, entryIndex) =>
                                                                entryIndex === index ? { ...entry, selected: e.target.checked } : entry,
                                                            ),
                                                        )
                                                    }
                                                />
                                            </label>
                                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label>{t('common.quantity_short')}</Label>
                                                    <Input
                                                        type="number"
                                                        min="1"
                                                        className="min-h-11"
                                                        value={item.quantity}
                                                        onChange={(e) =>
                                                            setData(
                                                                'items',
                                                                data.items.map((entry, entryIndex) =>
                                                                    entryIndex === index ? { ...entry, quantity: e.target.value } : entry,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label>{t('pos.item_note')}</Label>
                                                    <Input
                                                        className="min-h-11"
                                                        value={item.notes}
                                                        onChange={(e) =>
                                                            setData(
                                                                'items',
                                                                data.items.map((entry, entryIndex) =>
                                                                    entryIndex === index ? { ...entry, notes: e.target.value } : entry,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    <Button
                                        type="button"
                                        className="min-h-12 w-full touch-manipulation"
                                        disabled={processing}
                                        onClick={() => {
                                            form.transform((payload) => ({
                                                items: payload.items
                                                    .filter((item) => item.selected)
                                                    .map((item) => ({
                                                        menu_item_id: item.menu_item_id,
                                                        quantity: item.quantity,
                                                        notes: item.notes,
                                                    })),
                                            }));
                                            form.post(route('orders.items.store', order.id));
                                        }}
                                    >
                                        {t('order.show.add_selected')}
                                    </Button>
                                </>
                            ) : (
                                <p className="text-muted-foreground text-sm">{t('order.show.not_editable')}</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
