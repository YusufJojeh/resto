import { FormErrorSummary } from '@/components/form-error-summary';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/format-currency';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';

interface MenuCategory {
    id: number;
    name: string;
    items: Array<{ id: number; name: string; price: string }>;
}

export default function OrderCreate({
    tables,
    categories,
    selectedTableId,
}: {
    tables: Array<{ id: number; number: number; status: string }>;
    categories: MenuCategory[];
    selectedTableId?: number | null;
}) {
    const { branding, locale } = usePage<SharedData>().props;
    const { t } = useTranslation();
    const loc = (locale as 'en' | 'ar') ?? 'en';
    const currency = branding.currency_code;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('breadcrumbs.orders'), href: '/orders' },
        { title: t('breadcrumbs.create_order'), href: '/orders/create' },
    ];

    const initialItems = categories.flatMap((category) =>
        category.items.map((item) => ({
            menu_item_id: String(item.id),
            quantity: '1',
            notes: '',
            selected: false,
            name: item.name,
            price: item.price,
            category: category.name,
        })),
    );

    const form = useForm({
        table_id: selectedTableId ? String(selectedTableId) : '',
        notes: '',
        items: initialItems,
    });
    const { data, setData, processing, errors } = form;

    const submit = () => {
        form.transform((payload) => ({
            table_id: payload.table_id,
            notes: payload.notes,
            items: payload.items
                .filter((item) => item.selected)
                .map((item) => ({
                    menu_item_id: item.menu_item_id,
                    quantity: item.quantity,
                    notes: item.notes,
                })),
        }));
        form.post(route('orders.store'));
    };

    const selectedLines = data.items.filter((item) => item.selected);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('pos.create_title')} />
            <div className="data-[density=pos]:density-compact space-y-6 p-4" data-density="pos">
                <PageHeader title={t('pos.create_title')} description={t('pos.create_description')} />
                <FormErrorSummary errors={errors} />
                <div className="grid gap-6 xl:grid-cols-[1fr_320px] xl:items-start">
                    <Card className="min-w-0">
                        <CardContent className="space-y-6 pt-6">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>{t('pos.table')}</Label>
                                    <Select value={data.table_id} onValueChange={(value) => setData('table_id', value)}>
                                        <SelectTrigger className="min-h-11">
                                            <SelectValue placeholder={t('pos.choose_table')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {tables.map((table) => (
                                                <SelectItem key={table.id} value={String(table.id)}>
                                                    {t('pos.table_number', { n: table.number })}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.table_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="notes">{t('pos.order_notes')}</Label>
                                    <Input id="notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} className="min-h-11" />
                                    <InputError message={errors.notes} />
                                </div>
                            </div>
                            {tables.length === 0 ? (
                                <Card className="border-dashed">
                                    <CardContent className="text-muted-foreground pt-6 text-sm">{t('pos.no_tables')}</CardContent>
                                </Card>
                            ) : null}
                            <div className="space-y-6">
                                {categories.map((category) => (
                                    <div key={category.id} className="space-y-3">
                                        <div>
                                            <h2 className="text-muted-foreground text-xs font-semibold tracking-[0.2em] uppercase">
                                                {category.name}
                                            </h2>
                                        </div>
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            {category.items.map((item) => {
                                                const index = data.items.findIndex((entry) => entry.menu_item_id === String(item.id));
                                                const current = data.items[index];

                                                return (
                                                    <Card
                                                        key={item.id}
                                                        className={`min-w-0 ${current.selected ? 'border-primary ring-primary/20 ring-1' : ''}`}
                                                    >
                                                        <CardHeader className="pb-3">
                                                            <CardTitle className="flex items-start justify-between gap-2 text-base leading-snug font-semibold">
                                                                <span>{item.name}</span>
                                                                <span className="text-muted-foreground shrink-0 text-sm tabular-nums">
                                                                    {formatCurrency(Number(item.price), currency, loc)}
                                                                </span>
                                                            </CardTitle>
                                                        </CardHeader>
                                                        <CardContent className="space-y-3">
                                                            <label className="flex min-h-11 cursor-pointer touch-manipulation items-center gap-3 text-sm">
                                                                <input
                                                                    type="checkbox"
                                                                    className="size-5 shrink-0 rounded border"
                                                                    checked={current.selected}
                                                                    onChange={(e) =>
                                                                        setData(
                                                                            'items',
                                                                            data.items.map((entry, entryIndex) =>
                                                                                entryIndex === index
                                                                                    ? { ...entry, selected: e.target.checked }
                                                                                    : entry,
                                                                            ),
                                                                        )
                                                                    }
                                                                />
                                                                {t('pos.add_to_order')}
                                                            </label>
                                                            <div className="grid gap-3 sm:grid-cols-2">
                                                                <div className="space-y-2">
                                                                    <Label>{t('common.quantity_short')}</Label>
                                                                    <Input
                                                                        type="number"
                                                                        min="1"
                                                                        className="min-h-11"
                                                                        value={current.quantity}
                                                                        onChange={(e) =>
                                                                            setData(
                                                                                'items',
                                                                                data.items.map((entry, entryIndex) =>
                                                                                    entryIndex === index
                                                                                        ? { ...entry, quantity: e.target.value }
                                                                                        : entry,
                                                                                ),
                                                                            )
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="space-y-2">
                                                                    <Label>{t('pos.item_note')}</Label>
                                                                    <Input
                                                                        className="min-h-11"
                                                                        value={current.notes}
                                                                        onChange={(e) =>
                                                                            setData(
                                                                                'items',
                                                                                data.items.map((entry, entryIndex) =>
                                                                                    entryIndex === index
                                                                                        ? { ...entry, notes: e.target.value }
                                                                                        : entry,
                                                                                ),
                                                                            )
                                                                        }
                                                                    />
                                                                </div>
                                                            </div>
                                                        </CardContent>
                                                    </Card>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <InputError message={errors.items} />
                        </CardContent>
                    </Card>

                    <div className="xl:sticky xl:top-4 xl:self-start">
                        <Card className="border-primary/15 shadow-md">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-lg">{t('pos.summary')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="max-h-[40vh] space-y-2 overflow-y-auto text-sm xl:max-h-none">
                                    {selectedLines.map((item) => (
                                        <div
                                            key={item.menu_item_id}
                                            className="border-border/60 flex items-start justify-between gap-2 border-b py-2 last:border-0"
                                        >
                                            <span className="min-w-0 flex-1">
                                                {item.name} ×{item.quantity}
                                            </span>
                                            <span className="text-muted-foreground shrink-0 tabular-nums">
                                                {formatCurrency(Number(item.price) * Number(item.quantity), currency, loc)}
                                            </span>
                                        </div>
                                    ))}
                                    {selectedLines.length === 0 ? <p className="text-muted-foreground text-sm">{t('common.empty')}</p> : null}
                                </div>
                                <Button
                                    type="button"
                                    className="min-h-12 w-full touch-manipulation text-base"
                                    disabled={processing || !data.table_id || tables.length === 0}
                                    onClick={submit}
                                >
                                    {processing ? t('pos.creating') : t('pos.create_submit')}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
