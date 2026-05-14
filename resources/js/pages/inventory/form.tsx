import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

interface InventoryRecord {
    id?: number;
    menu_item_id?: number | null;
    name?: string;
    unit?: string;
    quantity?: number | string;
    low_threshold?: number | string;
}

interface MenuItemOption {
    id: number;
    name: string;
}

export default function InventoryForm({ item, menuItems }: { item: InventoryRecord | null; menuItems: MenuItemOption[] }) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('breadcrumbs.inventory'), href: '/inventory' },
        {
            title: item ? t('breadcrumbs.edit_inventory_item') : t('breadcrumbs.create_inventory_item'),
            href: item ? `/inventory/${item.id}/edit` : '/inventory/create',
        },
    ];

    const form = useForm({
        menu_item_id: String(item?.menu_item_id ?? 'none'),
        name: item?.name ?? '',
        unit: item?.unit ?? 'pcs',
        quantity: String(item?.quantity ?? 0),
        low_threshold: String(item?.low_threshold ?? 0),
    });
    const { data, setData, processing } = form;

    const submit = () => {
        form.transform((payload) => ({
            ...payload,
            menu_item_id: payload.menu_item_id === 'none' ? '' : payload.menu_item_id,
        }));
        if (item) {
            form.put(route('inventory.update', item.id));
            return;
        }
        form.post(route('inventory.store'));
    };

    const title = item ? t('inventory.form.title_edit') : t('inventory.form.title_create');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <div className="space-y-6 p-4">
                <PageHeader title={title} />
                <Card>
                    <CardContent className="grid gap-4 pt-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>{t('inventory.form.linked_item')}</Label>
                            <Select value={data.menu_item_id} onValueChange={(value) => setData('menu_item_id', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">{t('inventory.form.no_link')}</SelectItem>
                                    {menuItems.map((menuItem) => (
                                        <SelectItem key={menuItem.id} value={String(menuItem.id)}>
                                            {menuItem.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>{t('inventory.form.name')}</Label>
                            <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('inventory.form.unit')}</Label>
                            <Input value={data.unit} onChange={(e) => setData('unit', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('inventory.form.quantity')}</Label>
                            <Input type="number" step="0.001" value={data.quantity} onChange={(e) => setData('quantity', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('inventory.form.low_threshold')}</Label>
                            <Input type="number" step="0.001" value={data.low_threshold} onChange={(e) => setData('low_threshold', e.target.value)} />
                        </div>
                        <div className="md:col-span-2">
                            <Button onClick={submit} disabled={processing}>
                                {item ? t('inventory.form.submit_edit') : t('inventory.form.submit_create')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
