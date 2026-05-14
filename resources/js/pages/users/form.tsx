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

const roles = ['admin', 'manager', 'waiter', 'cashier', 'kitchen'];

export default function UserForm({ user }: { user: { id: number; name: string; email: string; role: string; is_active: boolean } | null }) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('breadcrumbs.users'), href: '/users' },
        { title: user ? t('breadcrumbs.edit_user') : t('breadcrumbs.create_user'), href: user ? `/users/${user.id}/edit` : '/users/create' },
    ];

    const { data, setData, post, put, processing } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        role: user?.role ?? 'waiter',
        is_active: user?.is_active ?? true,
    });

    const submit = () => (user ? put(route('users.update', user.id)) : post(route('users.store')));

    const title = user ? t('users.form.title_edit') : t('users.form.title_create');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <div className="space-y-6 p-4">
                <PageHeader title={title} />
                <Card>
                    <CardContent className="grid gap-4 pt-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>{t('users.form.name')}</Label>
                            <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('users.form.email')}</Label>
                            <Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('users.form.password')}</Label>
                            <Input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('users.form.password_confirm')}</Label>
                            <Input
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>{t('users.form.role')}</Label>
                            <Select value={data.role} onValueChange={(value) => setData('role', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {roles.map((role) => (
                                        <SelectItem key={role} value={role}>
                                            {role}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex items-end">
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                                {t('users.form.is_active')}
                            </label>
                        </div>
                        <div className="md:col-span-2">
                            <Button onClick={submit} disabled={processing}>
                                {user ? t('users.form.submit_edit') : t('users.form.submit_create')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
