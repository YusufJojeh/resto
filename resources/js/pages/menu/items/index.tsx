import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/format-currency';
import { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FolderKanban, LayoutGrid, Plus, SquareMenu, Tags } from 'lucide-react';

type MenuItemRow = {
    id: number;
    name: string;
    price: string;
    is_available: boolean;
    category?: { name: string };
};

export default function MenuItemsIndex({ items }: { items: MenuItemRow[] }) {
    const { t, locale } = useTranslation();
    const { branding } = usePage<SharedData>().props;
    const loc = (locale as 'en' | 'ar') ?? 'en';

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Menu Management', href: '/menu/items' }];
    const available = items.filter((item) => item.is_available).length;
    const categories = new Set(items.map((item) => item.category?.name).filter(Boolean)).size;
    const average = items.length > 0 ? items.reduce((sum, item) => sum + Number(item.price), 0) / items.length : 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('menu.items.title')} />
            <div className="app-page">
                <PageHeader
                    title="Menu Management"
                    description="Manage dishes, availability, pricing, and category structure without leaving the real Laravel menu routes."
                    actions={
                        <>
                            <Button asChild variant="outline">
                                <Link href={route('menu.categories.index')}>Categories</Link>
                            </Button>
                            <Button asChild className="gap-2">
                                <Link href={route('menu.items.create')}>
                                    <Plus className="h-4 w-4" />
                                    {t('menu.items.add')}
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {[
                        { label: 'Total Items', value: items.length, icon: SquareMenu },
                        { label: 'Available Now', value: available, icon: LayoutGrid },
                        { label: 'Categories', value: categories, icon: Tags },
                        { label: 'Average Price', value: formatCurrency(average, branding.currency_code, loc), icon: FolderKanban },
                    ].map((stat) => (
                        <Card key={stat.label}>
                            <CardContent className="flex items-center justify-between p-6">
                                <div>
                                    <div className="text-muted-foreground text-xs font-medium tracking-[0.18em] uppercase">{stat.label}</div>
                                    <div className="mt-2 text-3xl font-semibold">{stat.value}</div>
                                </div>
                                <div className="bg-primary/10 text-primary flex h-12 w-12 items-center justify-center rounded-2xl">
                                    <stat.icon className="h-5 w-5" />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div className="text-sm font-semibold">Items</div>
                                <div className="text-muted-foreground text-sm">Route-aware menu list using real category and availability data.</div>
                            </div>
                            <Button asChild variant="outline">
                                <Link href={route('menu.categories.index')}>Manage Categories</Link>
                            </Button>
                        </div>

                        <div className="table-surface overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th>{t('menu.items.col_item')}</th>
                                        <th>{t('menu.items.col_category')}</th>
                                        <th>{t('menu.items.col_price')}</th>
                                        <th>{t('menu.items.col_status')}</th>
                                        <th className="text-right">{t('menu.items.col_actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id}>
                                            <td className="font-medium">{item.name}</td>
                                            <td>{item.category?.name ?? t('menu.items.unassigned')}</td>
                                            <td className="tabular-nums">{formatCurrency(Number(item.price), branding.currency_code, loc)}</td>
                                            <td>
                                                <StatusBadge value={item.is_available ? 'item_available' : 'item_unavailable'} />
                                            </td>
                                            <td>
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => router.patch(route('menu.items.availability', item.id))}
                                                    >
                                                        {t('menu.items.toggle')}
                                                    </Button>
                                                    <Button asChild variant="ghost" size="sm">
                                                        <Link href={route('menu.items.edit', item.id)}>{t('menu.items.edit')}</Link>
                                                    </Button>
                                                </div>
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
