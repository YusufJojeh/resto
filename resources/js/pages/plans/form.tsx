import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

function labelFeature(key: string): string {
    return key.replace(/_/g, ' ');
}

export default function PlanForm({
    plan,
    featureKeys,
    limitKeys,
}: {
    plan: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        price_amount: string | number | null;
        billing_interval: string | null;
        provider_price_id: string | null;
        is_active: boolean;
        sort_order: number;
        features: Record<string, boolean>;
        limits?: Record<string, number | undefined>;
    } | null;
    featureKeys: string[];
    limitKeys: string[];
}) {
    const buildFeatures = (): Record<string, boolean> =>
        featureKeys.reduce(
            (acc, key) => {
                acc[key] = plan?.features[key] ?? true;
                return acc;
            },
            {} as Record<string, boolean>,
        );

    const buildLimits = (): Record<string, string> =>
        limitKeys.reduce(
            (acc, key) => {
                const raw = plan?.limits?.[key];
                acc[key] = raw !== undefined ? String(raw) : '';
                return acc;
            },
            {} as Record<string, string>,
        );

    const form = useForm({
        name: plan?.name ?? '',
        slug: plan?.slug ?? '',
        description: plan?.description ?? '',
        price_amount: plan?.price_amount != null ? String(plan.price_amount) : '',
        billing_interval: plan?.billing_interval ?? '',
        provider_price_id: plan?.provider_price_id ?? '',
        is_active: plan?.is_active ?? true,
        sort_order: String(plan?.sort_order ?? 0),
        features: buildFeatures(),
        limits: buildLimits(),
    });

    form.transform((raw) => {
        const limitsOut: Record<string, number> = {};
        Object.entries(raw.limits ?? {}).forEach(([key, val]) => {
            if (typeof val !== 'string' || val.trim() === '') return;
            const n = Number(val);
            if (Number.isFinite(n) && n >= 0) {
                limitsOut[key] = n;
            }
        });

        const priceFormatted = typeof raw.price_amount === 'string' ? raw.price_amount.trim() : String(raw.price_amount ?? '');
        const providerPid = typeof raw.provider_price_id === 'string' ? raw.provider_price_id.trim() : '';

        return {
            name: raw.name,
            slug: raw.slug,
            description: raw.description === '' ? null : raw.description,
            price_amount: priceFormatted === '' ? null : priceFormatted,
            billing_interval: raw.billing_interval === '' ? null : raw.billing_interval,
            provider_price_id: providerPid === '' ? null : providerPid,
            is_active: raw.is_active,
            sort_order: Number(raw.sort_order) || 0,
            features: raw.features,
            limits: limitsOut,
        };
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Plans', href: '/settings/plans' },
        { title: plan ? 'Edit plan' : 'Create plan', href: plan ? `/settings/plans/${plan.id}/edit` : '/settings/plans/create' },
    ];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (plan) {
            form.put(route('plans.update', plan.id));
        } else {
            form.post(route('plans.store'));
        }
    };

    const toggleFeature = (key: string, checked: boolean) => {
        form.setData('features', { ...form.data.features, [key]: checked });
    };

    const setLimit = (key: string, raw: string) => {
        form.setData('limits', { ...form.data.limits, [key]: raw });
    };

    const { processing, errors, data } = form;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={plan ? `Edit · ${plan.name}` : 'Create plan'} />
            <form className="space-y-6 p-4" onSubmit={submit}>
                <PageHeader title={plan ? 'Edit plan' : 'Create plan'} description="Controls feature flags and optional caps enforced server-side." />
                <Card>
                    <CardContent className="grid gap-4 pt-6 md:grid-cols-2">
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="name">Name</Label>
                            <Input id="name" value={data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            {errors.name && <p className="text-destructive text-xs">{errors.name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input id="slug" value={data.slug} onChange={(e) => form.setData('slug', e.target.value)} />
                            {errors.slug && <p className="text-destructive text-xs">{errors.slug}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="sort_order">Sort priority</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                min={0}
                                value={data.sort_order}
                                onChange={(e) => form.setData('sort_order', e.target.value)}
                            />
                            {errors.sort_order && <p className="text-destructive text-xs">{errors.sort_order}</p>}
                        </div>

                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="description">Description</Label>
                            <Input id="description" value={data.description ?? ''} onChange={(e) => form.setData('description', e.target.value)} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="price">Price (informational)</Label>
                            <Input
                                id="price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.price_amount}
                                onChange={(e) => form.setData('price_amount', e.target.value)}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>Billing interval</Label>
                            <Select
                                value={data.billing_interval === '' ? '__none__' : data.billing_interval}
                                onValueChange={(v) => form.setData('billing_interval', v === '__none__' ? '' : v)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Unset" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">Unset</SelectItem>
                                    <SelectItem value="month">Monthly</SelectItem>
                                    <SelectItem value="year">Yearly</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="provider_price_id">Provider price id (optional)</Label>
                            <Input
                                id="provider_price_id"
                                placeholder="Stripe price_… · Lemon variant id · etc."
                                value={data.provider_price_id ?? ''}
                                onChange={(e) => form.setData('provider_price_id', e.target.value)}
                                autoComplete="off"
                            />
                            {errors.provider_price_id && <p className="text-destructive text-xs">{errors.provider_price_id}</p>}
                            <p className="text-muted-foreground text-xs">For future webhook / checkout correlation only — not used without a billing integration.</p>
                        </div>

                        <label className="flex items-center gap-2 text-sm md:col-span-2">
                            <Checkbox checked={data.is_active} onCheckedChange={(v) => form.setData('is_active', v === true)} />
                            Active (inactive plans cannot be assigned to branches except their current tier)
                        </label>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="space-y-3 pt-6">
                        <h3 className="text-sm font-semibold">Features</h3>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {featureKeys.map((key) => (
                                <label key={key} className="flex items-start gap-2 text-sm capitalize">
                                    <Checkbox checked={data.features[key] ?? false} onCheckedChange={(v) => toggleFeature(key, v === true)} />
                                    <span>{labelFeature(key)}</span>
                                </label>
                            ))}
                        </div>
                        {errors.features && (
                            <p className="text-destructive text-xs">{typeof errors.features === 'string' ? errors.features : 'Invalid features'}</p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="grid gap-4 pt-6 md:grid-cols-2">
                        <div className="space-y-1 md:col-span-2">
                            <h3 className="text-sm font-semibold">Operational limits</h3>
                            <p className="text-muted-foreground text-xs">Leave blank for no cap.</p>
                        </div>
                        {limitKeys.map((key) => (
                            <div key={key} className="space-y-2">
                                <Label className="capitalize" htmlFor={key}>
                                    {labelFeature(key)}
                                </Label>
                                <Input id={key} type="number" min={0} value={data.limits[key] ?? ''} onChange={(e) => setLimit(key, e.target.value)} />
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Button type="submit" disabled={processing}>
                    {processing ? 'Saving…' : 'Save plan'}
                </Button>
            </form>
        </AppLayout>
    );
}
