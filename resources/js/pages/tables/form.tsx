import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

export default function TableForm({ table }: { table: { id: number; number: number; name: string | null; capacity: number } | null }) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('breadcrumbs.tables'), href: '/tables' },
        {
            title: table ? t('breadcrumbs.edit_table') : t('breadcrumbs.create_table'),
            href: table ? `/tables/${table.id}/edit` : '/tables/create',
        },
    ];

    const { data, setData, post, put, processing } = useForm({
        number: String(table?.number ?? ''),
        name: table?.name ?? '',
        capacity: String(table?.capacity ?? 4),
    });

    const submit = () => {
        if (table) {
            put(route('tables.update', table.id));
            return;
        }
        post(route('tables.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={table ? t('tables.edit_title') : t('tables.create_title')} />
            <div className="space-y-6 p-4">
                <PageHeader title={table ? t('tables.edit_title') : t('tables.create_title')} description={t('tables.form_description')} />
                <Card>
                    <CardContent className="grid gap-4 pt-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="number">{t('tables.form_number')}</Label>
                            <Input id="number" type="number" value={data.number} onChange={(e) => setData('number', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="capacity">{t('tables.form_capacity')}</Label>
                            <Input id="capacity" type="number" value={data.capacity} onChange={(e) => setData('capacity', e.target.value)} />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="name">{t('tables.form_name')}</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder={t('tables.form_name_placeholder')}
                            />
                        </div>
                        <div className="md:col-span-2">
                            <Button onClick={submit} disabled={processing}>
                                {table ? t('tables.submit_edit') : t('tables.submit_create')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
