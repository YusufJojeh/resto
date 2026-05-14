import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function MenuCategoriesIndex({
    categories,
}: {
    categories: Array<{ id: number; name: string; sort_order: number; items_count: number }>;
}) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('breadcrumbs.menu_categories'), href: '/menu/categories' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('menu.categories.title')} />
            <div className="space-y-6 p-4">
                <PageHeader
                    title={t('menu.categories.title')}
                    description={t('menu.categories.description')}
                    actions={
                        <Button asChild>
                            <Link href={route('menu.categories.create')}>{t('menu.categories.add')}</Link>
                        </Button>
                    }
                />
                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="text-muted-foreground">
                                    <tr>
                                        <th className="pb-3">{t('menu.categories.col_name')}</th>
                                        <th className="pb-3">{t('menu.categories.col_sort')}</th>
                                        <th className="pb-3">{t('menu.categories.col_items')}</th>
                                        <th className="pb-3 text-right">{t('menu.categories.col_actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {categories.map((category) => (
                                        <tr key={category.id} className="border-t">
                                            <td className="py-3">{category.name}</td>
                                            <td className="py-3">{category.sort_order}</td>
                                            <td className="py-3">{category.items_count}</td>
                                            <td className="py-3 text-right">
                                                <Button asChild variant="ghost" size="sm">
                                                    <Link href={route('menu.categories.edit', category.id)}>{t('menu.categories.edit')}</Link>
                                                </Button>
                                            </td>
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
