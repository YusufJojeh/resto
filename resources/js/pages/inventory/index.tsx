import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface InventoryRow {
    id: number;
    name: string;
    quantity: number | string;
    low_threshold: number | string;
    unit: string;
}

export default function InventoryIndex({ items }: { items: InventoryRow[] }) {
    const { t } = useTranslation();
    const [adjustments, setAdjustments] = useState<Record<number, { adjustment: string; reason: string }>>({});

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('breadcrumbs.inventory'), href: '/inventory' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('inventory.title')} />
            <div className="space-y-6 p-4">
                <PageHeader
                    title={t('inventory.title')}
                    description={t('inventory.description')}
                    actions={
                        <Button asChild>
                            <Link href={route('inventory.create')}>{t('inventory.add')}</Link>
                        </Button>
                    }
                />
                <div className="grid gap-4">
                    {items.map((item) => {
                        const current = adjustments[item.id] ?? { adjustment: '', reason: '' };
                        const state =
                            Number(item.quantity) <= 0
                                ? 'out_of_stock'
                                : Number(item.quantity) <= Number(item.low_threshold)
                                  ? 'low_stock'
                                  : 'in_stock';

                        return (
                            <Card key={item.id}>
                                <CardContent className="grid gap-4 pt-6 xl:grid-cols-[1fr_320px]">
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-3">
                                            <h3 className="text-lg font-semibold">{item.name}</h3>
                                            <StatusBadge value={state} />
                                        </div>
                                        <p className="text-muted-foreground text-sm">
                                            {t('inventory.on_hand', {
                                                qty: Number(item.quantity).toFixed(3),
                                                unit: item.unit,
                                                threshold: Number(item.low_threshold).toFixed(3),
                                            })}
                                        </p>
                                        <div className="flex gap-2">
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={route('inventory.edit', item.id)}>{t('inventory.edit')}</Link>
                                            </Button>
                                        </div>
                                    </div>
                                    <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                                        <Input
                                            placeholder={t('inventory.adjustment')}
                                            value={current.adjustment}
                                            onChange={(e) => setAdjustments((s) => ({ ...s, [item.id]: { ...current, adjustment: e.target.value } }))}
                                        />
                                        <Input
                                            placeholder={t('inventory.reason')}
                                            value={current.reason}
                                            onChange={(e) => setAdjustments((s) => ({ ...s, [item.id]: { ...current, reason: e.target.value } }))}
                                        />
                                        <Button
                                            onClick={() => router.post(route('inventory.adjust', item.id), current)}
                                            disabled={!current.adjustment || !current.reason}
                                        >
                                            {t('inventory.apply')}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
