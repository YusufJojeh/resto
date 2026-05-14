import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

type PlanRow = {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    sort_order: number;
    price_amount: string | null;
    billing_interval: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Subscription plans', href: '/settings/plans' }];

export default function PlansIndex({ plans }: { plans: PlanRow[] }) {
    const destroyPlan = (id: number, name: string) => {
        if (!confirm(`Delete plan “${name}”? Branches referencing it must be reassigned first.`)) return;
        router.delete(route('plans.destroy', id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Subscription plans" />
            <div className="space-y-6 p-4">
                <PageHeader
                    title="Subscription plans"
                    description="Internal tiers define feature access and operational limits before any payment integration."
                    actions={
                        <Button asChild>
                            <Link href={route('plans.create')}>Create plan</Link>
                        </Button>
                    }
                />

                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="text-foreground w-full text-left text-sm">
                                <thead className="text-muted-foreground">
                                    <tr>
                                        <th className="pb-3 font-medium">Name</th>
                                        <th className="pb-3 font-medium">Slug</th>
                                        <th className="pb-3 font-medium">Status</th>
                                        <th className="pb-3 font-medium">Billing</th>
                                        <th className="pb-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {plans.map((plan) => (
                                        <tr key={plan.id} className="border-t">
                                            <td className="py-3">
                                                <p className="font-medium">{plan.name}</p>
                                                <p className="text-muted-foreground text-xs uppercase">Priority {plan.sort_order}</p>
                                            </td>
                                            <td className="py-3 font-mono text-xs">{plan.slug}</td>
                                            <td className="py-3">
                                                <Badge variant={plan.is_active ? 'default' : 'outline'}>{plan.is_active ? 'Active' : 'Inactive'}</Badge>
                                            </td>
                                            <td className="text-muted-foreground py-3 text-xs">
                                                {plan.price_amount != null && plan.billing_interval
                                                    ? `$${plan.price_amount} / ${plan.billing_interval}`
                                                    : '—'}
                                            </td>
                                            <td className="flex justify-end gap-2 py-3">
                                                <Button asChild variant="outline" size="sm">
                                                    <Link href={route('plans.edit', plan.id)}>Edit</Link>
                                                </Button>
                                                <Button variant="destructive" size="sm" onClick={() => destroyPlan(plan.id, plan.name)}>
                                                    Delete
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                    {plans.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="text-muted-foreground py-6 text-center">
                                                No plans yet. Create one to start assigning tiers to branches.
                                            </td>
                                        </tr>
                                    ) : null}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
