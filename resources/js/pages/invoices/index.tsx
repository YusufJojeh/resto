import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/format-currency';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface InvoiceRow {
    id: number;
    invoice_number: string;
    total: number | string;
    payment_method?: string | null;
    paid_at?: string | null;
    order?: {
        id: number;
        table?: { number: number };
    };
}

export default function InvoicesIndex({ invoices }: { invoices: InvoiceRow[] }) {
    const { t } = useTranslation();
    const { branding, locale } = usePage<SharedData>().props;
    const loc = (locale as 'en' | 'ar') ?? 'en';
    const currency = branding.currency_code;

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.invoices'), href: '/invoices' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('invoices.title')} />
            <div className="space-y-6 p-4">
                <PageHeader title={t('invoices.title')} description={t('invoices.description')} />
                <Card>
                    <CardContent className="pt-6">
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full text-start text-sm">
                                <thead className="text-muted-foreground">
                                    <tr>
                                        <th className="pb-3">{t('invoices.col_invoice')}</th>
                                        <th className="pb-3">{t('invoices.col_order')}</th>
                                        <th className="pb-3">{t('invoices.col_table')}</th>
                                        <th className="pb-3">{t('invoices.col_total')}</th>
                                        <th className="pb-3">{t('invoices.col_payment')}</th>
                                        <th className="pb-3">{t('invoices.col_status')}</th>
                                        <th className="pb-3 text-end">{t('orders.col_actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {invoices.map((invoice) => (
                                        <tr key={invoice.id} className="border-t">
                                            <td className="py-3">{invoice.invoice_number}</td>
                                            <td className="py-3">#{invoice.order?.id}</td>
                                            <td className="py-3">{t('pos.table_number', { n: invoice.order?.table?.number ?? '—' })}</td>
                                            <td className="py-3 tabular-nums">{formatCurrency(Number(invoice.total), currency, loc)}</td>
                                            <td className="py-3">{invoice.payment_method ?? t('invoices.payment_pending')}</td>
                                            <td className="py-3">
                                                <StatusBadge value={invoice.paid_at ? 'paid' : 'unpaid'} />
                                            </td>
                                            <td className="py-3 text-end">
                                                <Button asChild variant="ghost" size="sm" className="min-h-11">
                                                    <Link href={route('invoices.show', invoice.id)}>{t('common.view')}</Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <ul className="flex flex-col gap-3 md:hidden">
                            {invoices.map((invoice) => (
                                <li key={invoice.id} className="bg-card rounded-xl border p-4 shadow-sm">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p className="font-semibold">{invoice.invoice_number}</p>
                                            <p className="text-muted-foreground text-sm">
                                                {t('invoices.col_order')}: #{invoice.order?.id}
                                                {' · '}
                                                {t('pos.table_number', { n: invoice.order?.table?.number ?? '—' })}
                                            </p>
                                            <p className="mt-2 text-lg font-semibold tabular-nums">
                                                {formatCurrency(Number(invoice.total), currency, loc)}
                                            </p>
                                            <p className="text-muted-foreground text-sm">{invoice.payment_method ?? t('invoices.payment_pending')}</p>
                                        </div>
                                        <StatusBadge value={invoice.paid_at ? 'paid' : 'unpaid'} />
                                    </div>
                                    <div className="mt-4 flex justify-end">
                                        <Button asChild size="sm" className="min-h-11">
                                            <Link href={route('invoices.show', invoice.id)}>{t('common.view')}</Link>
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
