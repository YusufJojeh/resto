import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/format-currency';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';

export default function ReportsIndex({
    selectedDate,
    summary,
    topItems,
}: {
    selectedDate: string;
    summary: { paidInvoices: number; revenue: number; averageOrderValue: number; cashRevenue: number; cardRevenue: number };
    topItems: Array<{ menu_item_name: string; total_quantity: number; total_revenue: number }>;
}) {
    const { t, locale } = useTranslation();
    const { branding } = usePage<SharedData>().props;
    const loc = (locale as 'en' | 'ar') ?? 'en';
    const currency = branding.currency_code;

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('breadcrumbs.reports'), href: '/reports' }];

    const fmt = (amount: number) => formatCurrency(amount, currency, loc);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('reports.title')} />
            <div className="space-y-6 p-4">
                <PageHeader
                    title={t('reports.title_lite')}
                    description={t('reports.description')}
                    actions={
                        <Input type="date" value={selectedDate} onChange={(e) => router.get(route('reports.index'), { date: e.target.value })} />
                    }
                />
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <StatCard label={t('reports.paid_invoices')} value={summary.paidInvoices} />
                    <StatCard label={t('reports.revenue')} value={fmt(summary.revenue)} />
                    <StatCard label={t('reports.avg_order')} value={fmt(summary.averageOrderValue)} />
                    <StatCard label={t('reports.cash')} value={fmt(summary.cashRevenue)} />
                    <StatCard label={t('reports.card')} value={fmt(summary.cardRevenue)} />
                </div>
                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="text-muted-foreground">
                                    <tr>
                                        <th className="pb-3">{t('reports.col_item')}</th>
                                        <th className="pb-3">{t('reports.col_qty')}</th>
                                        <th className="pb-3">{t('reports.col_revenue')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {topItems.map((item) => (
                                        <tr key={item.menu_item_name} className="border-t">
                                            <td className="py-3">{item.menu_item_name}</td>
                                            <td className="py-3">{item.total_quantity}</td>
                                            <td className="py-3">{fmt(item.total_revenue)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
