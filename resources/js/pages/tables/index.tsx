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

interface TableRecord {
    id: number;
    number: number;
    name: string | null;
    capacity: number;
    status: string;
}

interface TableStatusEvent {
    id: number;
    status: string;
}

export default function TablesIndex({ tables, canManage, canCreateOrder }: { tables: TableRecord[]; canManage: boolean; canCreateOrder: boolean }) {
    const { t } = useTranslation();
    const branchId = usePage<SharedData>().props.auth.user?.branch_id;
    const [tableList, setTableList] = useState(tables);

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('breadcrumbs.tables'), href: '/tables' }];

    useEffect(() => {
        if (!branchId) return;
        const channel = echo.private(`branch.${branchId}`);
        channel.listen('.table.status_changed', (e: TableStatusEvent) => {
            setTableList((prev) => prev.map((tbl) => (tbl.id === e.id ? { ...tbl, status: e.status } : tbl)));
        });
        return () => {
            echo.leave(`branch.${branchId}`);
        };
    }, [branchId]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('tables.title')} />
            <div className="space-y-6 p-4">
                <PageHeader
                    title={t('tables.title')}
                    description={t('tables.description')}
                    actions={
                        canManage ? (
                            <Button asChild>
                                <Link href={route('tables.create')}>{t('tables.add')}</Link>
                            </Button>
                        ) : null
                    }
                />
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {tableList.map((table) => (
                        <Card key={table.id}>
                            <CardContent className="space-y-4 pt-6">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="text-muted-foreground text-xs tracking-[0.2em] uppercase">
                                            {t('tables.label', { n: table.number })}
                                        </p>
                                        <h3 className="text-lg font-semibold">{table.name ?? t('tables.label', { n: table.number })}</h3>
                                    </div>
                                    <StatusBadge value={table.status} />
                                </div>
                                <p className="text-muted-foreground text-sm">{t('tables.capacity', { n: table.capacity })}</p>
                                <div className="flex gap-2">
                                    {canCreateOrder && table.status === 'available' ? (
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={route('orders.create', { table_id: table.id })}>{t('tables.new_order')}</Link>
                                        </Button>
                                    ) : table.status !== 'available' ? (
                                        <Button variant="outline" size="sm" disabled>
                                            {table.status === 'occupied' ? t('table.status.occupied') : t('table.status.reserved')}
                                        </Button>
                                    ) : null}
                                    {canManage ? (
                                        <Button asChild variant="ghost" size="sm">
                                            <Link href={route('tables.edit', table.id)}>{t('common.edit')}</Link>
                                        </Button>
                                    ) : null}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
