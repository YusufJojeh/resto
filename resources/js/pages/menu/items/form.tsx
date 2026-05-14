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

export default function MenuItemForm({
    item,
    categories,
}: {
    item: { id: number; category_id: number; name: string; description: string | null; price: string; is_available: boolean } | null;
    categories: Array<{ id: number; name: string }>;
}) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('breadcrumbs.menu_items'), href: '/menu/items' },
        {
            title: item ? t('breadcrumbs.edit_menu_item') : t('breadcrumbs.create_menu_item'),
            href: item ? `/menu/items/${item.id}/edit` : '/menu/items/create',
        },
    ];

    const { data, setData, post, put, processing } = useForm({
        category_id: String(item?.category_id ?? categories[0]?.id ?? ''),
        name: item?.name ?? '',
        description: item?.description ?? '',
        price: String(item?.price ?? ''),
        is_available: item?.is_available ?? true,
    });

    const submit = () => (item ? put(route('menu.items.update', item.id)) : post(route('menu.items.store')));

    const title = item ? t('menu.item.form.title_edit') : t('menu.item.form.title_create');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <div className="space-y-6 p-4">
                <PageHeader title={title} />
                <Card>
                    <CardContent className="grid gap-4 pt-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>{t('menu.item.form.category')}</Label>
                            <Select value={data.category_id} onValueChange={(value) => setData('category_id', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={String(category.id)}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="name">{t('menu.item.form.name')}</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="description">{t('menu.item.form.description')}</Label>
                            <Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="price">{t('menu.item.form.price')}</Label>
                            <Input id="price" type="number" step="0.01" value={data.price} onChange={(e) => setData('price', e.target.value)} />
                        </div>
                        <div className="flex items-end">
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={data.is_available} onChange={(e) => setData('is_available', e.target.checked)} />
                                {t('menu.item.form.available')}
                            </label>
                        </div>
                        <div className="md:col-span-2">
                            <Button onClick={submit} disabled={processing}>
                                {item ? t('menu.item.form.submit_edit') : t('menu.item.form.submit_create')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
