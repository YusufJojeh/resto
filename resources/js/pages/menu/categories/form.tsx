import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

export default function MenuCategoryForm({ category }: { category: { id: number; name: string; sort_order: number } | null }) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('breadcrumbs.menu_categories'), href: '/menu/categories' },
        {
            title: category ? t('breadcrumbs.edit_menu_category') : t('breadcrumbs.create_menu_category'),
            href: category ? `/menu/categories/${category.id}/edit` : '/menu/categories/create',
        },
    ];

    const { data, setData, post, put, processing } = useForm({
        name: category?.name ?? '',
        sort_order: String(category?.sort_order ?? 0),
    });

    const submit = () => (category ? put(route('menu.categories.update', category.id)) : post(route('menu.categories.store')));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={category ? t('menu.categories.edit_title') : t('menu.categories.create_title')} />
            <div className="space-y-6 p-4">
                <PageHeader title={category ? t('menu.categories.edit_title') : t('menu.categories.create_title')} />
                <Card>
                    <CardContent className="grid gap-4 pt-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="name">{t('menu.categories.form_name')}</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sort_order">{t('menu.categories.form_sort')}</Label>
                            <Input id="sort_order" type="number" value={data.sort_order} onChange={(e) => setData('sort_order', e.target.value)} />
                        </div>
                        <div className="md:col-span-2">
                            <Button onClick={submit} disabled={processing}>
                                {category ? t('menu.categories.submit_edit') : t('menu.categories.submit_create')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
