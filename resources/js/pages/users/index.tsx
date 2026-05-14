import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

export default function UsersIndex({ users }: { users: Array<{ id: number; name: string; email: string; roles: string[]; is_active: boolean }> }) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('breadcrumbs.users'), href: '/users' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('users.title')} />
            <div className="space-y-6 p-4">
                <PageHeader
                    title={t('users.title')}
                    description={t('users.description')}
                    actions={
                        <Button asChild>
                            <Link href={route('users.create')}>{t('users.add_user')}</Link>
                        </Button>
                    }
                />
                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="text-muted-foreground">
                                    <tr>
                                        <th className="pb-3">{t('users.col_name')}</th>
                                        <th className="pb-3">{t('users.col_email')}</th>
                                        <th className="pb-3">{t('users.col_role')}</th>
                                        <th className="pb-3">{t('users.col_status')}</th>
                                        <th className="pb-3 text-right">{t('users.col_actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.map((user) => (
                                        <tr key={user.id} className="border-t">
                                            <td className="py-3">{user.name}</td>
                                            <td className="py-3">{user.email}</td>
                                            <td className="py-3">{user.roles[0] ?? t('common.na')}</td>
                                            <td className="py-3">
                                                <StatusBadge value={user.is_active ? 'active' : 'inactive'} />
                                            </td>
                                            <td className="py-3 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button asChild variant="ghost" size="sm">
                                                        <Link href={route('users.edit', user.id)}>{t('users.edit_action')}</Link>
                                                    </Button>
                                                    {user.is_active ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => router.patch(route('users.deactivate', user.id))}
                                                        >
                                                            {t('users.deactivate')}
                                                        </Button>
                                                    ) : null}
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
