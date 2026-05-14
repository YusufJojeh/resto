import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/format-currency';
import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';

interface OrderItemLine {
    id: number;
    menu_item_name: string;
    quantity: number;
    subtotal: string | number;
}

interface InvoicePayload {
    id: number;
    invoice_number: string;
    payment_method?: string | null;
    paid_at?: string | null;
    subtotal: string | number;
    tax_amount: string | number;
    total: string | number;
    branch?: { currency_code: string };
    order?: {
        id: number;
        table?: { number: number };
        items?: OrderItemLine[];
    };
}

export default function InvoiceShow({ invoice }: { invoice: InvoicePayload }) {
    const { t } = useTranslation();
    const { locale, branding } = usePage<SharedData>().props;
    const loc = (locale as 'en' | 'ar') ?? 'en';
    const currencyCode = invoice.branch?.currency_code ?? branding.currency_code ?? 'USD';

    const fmt = (value: string | number) => formatCurrency(Number(value), currencyCode, loc);

    const breadcrumbs = [
        { title: t('breadcrumbs.invoices'), href: '/invoices' },
        { title: invoice.invoice_number, href: `/invoices/${invoice.id}` },
    ];

    const { data, setData, patch, processing } = useForm({
        payment_method: invoice.payment_method ?? 'cash',
    });

    const orderDesc = invoice.order
        ? t('invoices.show.order_description', {
              id: String(invoice.order.id),
              table: String(invoice.order.table?.number ?? t('common.na')),
          })
        : invoice.invoice_number;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={invoice.invoice_number} />
            <div className="space-y-6 p-4">
                <PageHeader
                    title={invoice.invoice_number}
                    description={orderDesc}
                    actions={<StatusBadge value={invoice.paid_at ? 'paid' : 'unpaid'} />}
                />
                <div className="grid gap-6 xl:grid-cols-[1fr_320px]">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('invoices.show.receipt')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {invoice.order?.items?.map((item) => (
                                <div key={item.id} className="flex items-center justify-between rounded-lg border p-3 text-sm">
                                    <div>
                                        <p className="font-medium">{item.menu_item_name}</p>
                                        <p className="text-muted-foreground">
                                            {t('invoices.show.qty')} {item.quantity}
                                        </p>
                                    </div>
                                    <div>{fmt(item.subtotal)}</div>
                                </div>
                            ))}
                            <div className="space-y-2 border-t pt-4 text-sm">
                                <div className="flex justify-between">
                                    <span>{t('invoices.show.subtotal')}</span>
                                    <span>{fmt(invoice.subtotal)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>{t('invoices.show.tax')}</span>
                                    <span>{fmt(invoice.tax_amount)}</span>
                                </div>
                                <div className="flex justify-between font-semibold">
                                    <span>{t('invoices.show.total')}</span>
                                    <span>{fmt(invoice.total)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('invoices.show.payment')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {invoice.paid_at ? (
                                <div className="space-y-2 text-sm">
                                    <p>
                                        {t('invoices.show.payment_method')}: <span className="font-medium">{invoice.payment_method}</span>
                                    </p>
                                    <p>
                                        {t('invoices.show.paid_at')}:{' '}
                                        <span className="font-medium">{new Date(invoice.paid_at).toLocaleString()}</span>
                                    </p>
                                </div>
                            ) : (
                                <>
                                    <div className="space-y-2">
                                        <Label>{t('invoices.show.payment_method')}</Label>
                                        <Select value={data.payment_method} onValueChange={(value) => setData('payment_method', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="cash">{t('invoices.show.cash')}</SelectItem>
                                                <SelectItem value="card">{t('invoices.show.card')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <Button className="w-full" disabled={processing} onClick={() => patch(route('invoices.pay', invoice.id))}>
                                        {t('invoices.show.confirm_payment')}
                                    </Button>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
