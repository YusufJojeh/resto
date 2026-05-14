import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { BrandingTokens, BranchBillingCatalogPlan, BranchBillingProps, BranchCurrentPlanRow, BranchPlanAssignmentOption, BranchDisplayPlanRow, BreadcrumbItem, SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Branch Settings', href: '/settings/branch' }];

interface BranchData {
    id: number;
    plan_id?: number | null;
    /** Present for admins only; stripped for managers. */
    provider_name?: string | null;
    provider_customer_id?: string | null;
    provider_subscription_id?: string | null;
    name: string;
    address: string | null;
    phone: string | null;
    tax_rate: string | number | null;
    currency_code: string | null;
    public_slug: string | null;
    is_public: boolean;
    business_name: string | null;
    tagline: string | null;
    story: string | null;
    logo_path: string | null;
    cover_path: string | null;
    primary_color: string | null;
    secondary_color: string | null;
    accent_color: string | null;
    whatsapp: string | null;
    instagram_url: string | null;
    facebook_url: string | null;
    tiktok_url: string | null;
    google_maps_url: string | null;
}

const TABS = ['Settings', 'Branding', 'Subscription'] as const;
type Tab = (typeof TABS)[number];

function formatSubscriptionDate(value: string | null) {
    if (!value) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}

function getStatusVariant(status: string | null): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
            return 'default';
        case 'trialing':
            return 'secondary';
        case 'expired':
        case 'suspended':
        case 'past_due':
            return 'destructive';
        case 'canceled':
            return 'secondary';
        default:
            return 'outline';
    }
}

function formatFeatureTitle(key: string) {
    return key.replace(/_/g, ' ');
}

export default function BranchEdit({
    branch,
    branding,
    display_plans,
    current_plan,
    plans_for_assignment,
    billing_configured,
    billing_plans,
    can_start_checkout,
    can_open_billing_portal,
    billing,
}: {
    branch: BranchData;
    branding: BrandingTokens;
    display_plans: BranchDisplayPlanRow[];
    current_plan: BranchCurrentPlanRow | null;
    billing_configured?: boolean;
    billing_plans?: BranchBillingCatalogPlan[] | null;
    can_start_checkout?: boolean;
    can_open_billing_portal?: boolean;
    plans_for_assignment?:
        | Array<BranchPlanAssignmentOption>
        | null;
    billing?: BranchBillingProps | null;
}) {
    const { subscription, auth } = usePage<SharedData>().props;
    const [activeTab, setActiveTab] = useState<Tab>('Settings');
    const canManageSubscription = auth.user?.can_manage_subscription ?? false;

    useEffect(() => {
        const tab = new URLSearchParams(window.location.search).get('tab');
        if (tab?.toLowerCase() === 'subscription') {
            setActiveTab('Subscription');
        }
    }, []);

    const { data, setData: setDataBranch, post, processing, errors } = useForm({
        _method: 'PUT',
        name: branch.name ?? '',
        address: branch.address ?? '',
        phone: branch.phone ?? '',
        tax_rate: String(branch.tax_rate ?? '0'),
        currency_code: branch.currency_code ?? 'USD',
        public_slug: branch.public_slug ?? '',
        is_public: branch.is_public ?? false,
        business_name: branch.business_name ?? '',
        tagline: branch.tagline ?? '',
        story: branch.story ?? '',
        primary_color: branch.primary_color ?? '#1a1a2e',
        secondary_color: branch.secondary_color ?? '#16213e',
        accent_color: branch.accent_color ?? '#e94560',
        whatsapp: branch.whatsapp ?? '',
        instagram_url: branch.instagram_url ?? '',
        facebook_url: branch.facebook_url ?? '',
        tiktok_url: branch.tiktok_url ?? '',
        google_maps_url: branch.google_maps_url ?? '',
        logo: null as File | null,
        cover: null as File | null,
    });

    const { data: subData, setData: setSubData, patch: patchSub, processing: subProcessing, errors: subErrors } = useForm({
        subscription_status: subscription.status ?? 'active',
        trial_ends_at: subscription.trial_ends_at ?? '',
        subscription_ends_at: subscription.subscription_ends_at ?? '',
        current_period_ends_at: subscription.current_period_ends_at ?? '',
        plan_id:
            subscription.plan_id != null ? String(subscription.plan_id) : '__none__',
    });

    const submitBranch = () => post(route('branch.update'), { forceFormData: true });

    const submitSubscription = () => patchSub(route('branch.subscription.update'));

    const inertiaErrors = usePage<SharedData>().props.errors ?? {};

    const [billingCheckoutBusy, setBillingCheckoutBusy] = useState(false);
    const [billingCancelBusy, setBillingCancelBusy] = useState(false);
    const [billingPortalBusy, setBillingPortalBusy] = useState(false);

    const startStripeCheckout = (planId: number) => {
        setBillingCheckoutBusy(true);
        router.post(
            route('branch.billing.checkout'),
            { plan_id: planId },
            {
                preserveScroll: true,
                onFinish: () => setBillingCheckoutBusy(false),
            },
        );
    };

    const openStripePortal = () => {
        setBillingPortalBusy(true);
        router.post(route('branch.billing.portal'), {}, { preserveScroll: true, onFinish: () => setBillingPortalBusy(false) });
    };

    const scheduleStripeCancel = () => {
        if (!confirm('Schedule cancel-at-period-end in Stripe for this workspace? Confirm with webhooks / manual overrides as needed.')) {
            return;
        }
        setBillingCancelBusy(true);
        router.post(route('branch.billing.cancel'), {}, { preserveScroll: true, onFinish: () => setBillingCancelBusy(false) });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Branch Settings" />
            <div className="space-y-6 p-4">
                <PageHeader title="Branch Settings" description="Manage your restaurant profile, branding and public menu." />

                {/* Tab bar */}
                <div className="flex gap-1 border-b">
                    {TABS.map((tab) => (
                        <button
                            key={tab}
                            onClick={() => setActiveTab(tab)}
                            className={[
                                'px-4 py-2 text-sm font-medium transition-colors',
                                activeTab === tab ? 'border-primary text-primary border-b-2' : 'text-muted-foreground hover:text-foreground',
                            ].join(' ')}
                        >
                            {tab}
                        </button>
                    ))}
                </div>

                {/* — Settings tab — */}
                {activeTab === 'Settings' && (
                    <div className="space-y-6">
                        <Card>
                            <CardContent className="grid gap-4 pt-6 md:grid-cols-4">
                                <div className="space-y-1">
                                    <p className="text-muted-foreground text-xs font-medium uppercase">Subscription</p>
                                    <p className="text-sm font-semibold">
                                        {subscription.entitlement_summary?.name ??
                                            subscription.plan ??
                                            (subscription.grandfathered_without_plan ? 'Grandfathered (no tier)' : 'No tier record')}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground text-xs font-medium uppercase">Status</p>
                                    <Badge variant={getStatusVariant(subscription.status)}>
                                        {subscription.status ?? 'unassigned'}
                                    </Badge>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground text-xs font-medium uppercase">Trial ends</p>
                                    <p className="text-sm font-medium">{formatSubscriptionDate(subscription.trial_ends_at)}</p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground text-xs font-medium uppercase">Subscription ends</p>
                                    <p className="text-sm font-medium">{formatSubscriptionDate(subscription.subscription_ends_at)}</p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Images */}
                        <Card>
                            <CardContent className="grid gap-6 pt-6 md:grid-cols-2">
                                <div className="space-y-2 md:col-span-2">
                                    <h3 className="text-sm font-semibold">Images</h3>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="logo">Logo</Label>
                                    {branding.logo_path && (
                                        <img src={branding.logo_path} alt="Current logo" className="mb-2 h-16 w-16 rounded-lg object-cover" />
                                    )}
                                    <Input
                                        id="logo"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={(e) => setDataBranch('logo', e.target.files?.[0] ?? null)}
                                    />
                                    <p className="text-muted-foreground text-xs">Max 2 MB · JPG, PNG, WebP</p>
                                    {errors.logo && <p className="text-destructive text-xs">{errors.logo}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="cover">Cover image</Label>
                                    {branding.cover_path && (
                                        <img src={branding.cover_path} alt="Current cover" className="mb-2 h-20 w-full rounded-lg object-cover" />
                                    )}
                                    <Input
                                        id="cover"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={(e) => setDataBranch('cover', e.target.files?.[0] ?? null)}
                                    />
                                    <p className="text-muted-foreground text-xs">Max 4 MB · JPG, PNG, WebP</p>
                                    {errors.cover && <p className="text-destructive text-xs">{errors.cover}</p>}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Social */}
                        <Card>
                            <CardContent className="grid gap-4 pt-6 md:grid-cols-2">
                                <div className="space-y-2 md:col-span-2">
                                    <h3 className="text-sm font-semibold">Social &amp; Contact</h3>
                                </div>
                                {(
                                    [
                                        ['whatsapp', 'WhatsApp number', '+1234567890'],
                                        ['instagram_url', 'Instagram URL', 'https://instagram.com/…'],
                                        ['facebook_url', 'Facebook URL', 'https://facebook.com/…'],
                                        ['tiktok_url', 'TikTok URL', 'https://tiktok.com/@…'],
                                        ['google_maps_url', 'Google Maps link', 'https://maps.google.com/…'],
                                    ] as const
                                ).map(([field, label, placeholder]) => (
                                    <div key={field} className="space-y-2">
                                        <Label htmlFor={field}>{label}</Label>
                                        <Input
                                            id={field}
                                            placeholder={placeholder}
                                            value={data[field]}
                                            onChange={(e) => setDataBranch(field, e.target.value)}
                                        />
                                        {errors[field] && <p className="text-destructive text-xs">{errors[field]}</p>}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Button onClick={submitBranch} disabled={processing}>
                            Save Branding
                        </Button>
                    </div>
                )}

                {/* — Subscription tab — */}
                {activeTab === 'Subscription' && (
                    <div className="space-y-6">
                        <Card>
                            <CardContent className="space-y-4 pt-6">
                                <h3 className="text-sm font-semibold">Current subscription</h3>
                                <div className="grid gap-3 text-sm md:grid-cols-2">
                                    <div>
                                        <dt className="text-muted-foreground">Operational access</dt>
                                        <dd className="font-medium">
                                            <Badge variant={subscription.has_access ? 'default' : 'destructive'}>
                                                {subscription.has_access ? 'Allowed' : 'Blocked'}
                                            </Badge>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Access reason (code)</dt>
                                        <dd className="font-mono text-xs uppercase">{subscription.reason_code ?? '—'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Plan tier</dt>
                                        <dd className="font-medium">
                                            {subscription.entitlement_summary?.name ??
                                                (subscription.grandfathered_without_plan ? 'Grandfathered (no tier record)' : 'Not assigned')}
                                        </dd>
                                        <p className="text-muted-foreground text-xs">Legacy label: {subscription.plan ?? '—'}</p>
                                        {current_plan && !current_plan.is_active ? (
                                            <div className="text-amber-600 dark:text-amber-400 mt-2 rounded-md border border-amber-200/60 bg-amber-50 px-3 py-2 dark:border-amber-900/60 dark:bg-amber-950/40">
                                                <p className="font-semibold">Current inactive plan</p>
                                                <p className="mt-1">
                                                    {current_plan.name} is archived — it stays linked for entitlements until you migrate manually
                                                    or through checkout. Stripe checkout lists purchasable active tiers only.
                                                </p>
                                            </div>
                                        ) : null}
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Billing / status</dt>
                                        <dd>
                                            <Badge variant={getStatusVariant(subscription.status)}>
                                                {subscription.status ?? 'unassigned'}
                                            </Badge>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Past-due grace window</dt>
                                        <dd className="font-medium">
                                            {subscription.grace_days ?? 0} day(s) after the billing anchor ({' '}
                                            <code className="text-xs">subscription_ends_at</code> or fallback period end)
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Trial ends</dt>
                                        <dd className="font-medium">{formatSubscriptionDate(subscription.trial_ends_at)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Billing anchor / commercial end</dt>
                                        <dd className="font-medium">{formatSubscriptionDate(subscription.subscription_ends_at)}</dd>
                                        <p className="text-muted-foreground text-xs">Used for Past Due grace + Cancel fallbacks.</p>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">Current service period ends</dt>
                                        <dd className="font-medium">{formatSubscriptionDate(subscription.current_period_ends_at ?? null)}</dd>
                                        <p className="text-muted-foreground text-xs">
                                            Preferred boundary for canceled service; falls back if unset.
                                        </p>
                                    </div>
                                </div>

                                {display_plans.length > 0 ? (
                                    <div className="text-muted-foreground border-t pt-3 text-xs">
                                        <span className="font-medium uppercase tracking-wide text-foreground">Active catalog tiers · read-only</span>
                                        <p className="mt-1">{display_plans.map((p) => p.name).join(' · ')}</p>
                                    </div>
                                ) : null}

                                <div className="space-y-2">
                                    <p className="text-muted-foreground text-xs font-medium uppercase">Enabled features</p>
                                    {subscription.grandfathered_without_plan ? (
                                        <p className="text-muted-foreground text-sm">
                                            No internal tier is linked yet — operational access follows subscription status until a plan is enforced.
                                        </p>
                                    ) : subscription.entitlement_summary ? (
                                        <div className="flex flex-wrap gap-2">
                                            {Object.entries(subscription.entitlement_summary.features)
                                                .filter(([, v]) => v)
                                                .map(([key]) => (
                                                    <Badge key={key} variant="secondary" className="capitalize">
                                                        {formatFeatureTitle(key)}
                                                    </Badge>
                                                ))}
                                            {Object.values(subscription.entitlement_summary.features).every((v) => !v) ? (
                                                <span className="text-muted-foreground text-sm">No features turned on.</span>
                                            ) : null}
                                        </div>
                                    ) : (
                                        <span className="text-muted-foreground text-sm">No entitlement data.</span>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <p className="text-muted-foreground text-xs font-medium uppercase">Plan limits · enforced now</p>
                                    {subscription.grandfathered_without_plan || !subscription.entitlement_summary ? (
                                        <p className="text-muted-foreground text-sm">Caps apply only once a tier is assigned.</p>
                                    ) : Object.keys(subscription.entitlement_summary.limits).length === 0 ? (
                                        <p className="text-muted-foreground text-sm">None configured.</p>
                                    ) : (
                                        <ul className="text-muted-foreground space-y-1 text-sm">
                                            {Object.entries(subscription.entitlement_summary.limits).map(([k, v]) => (
                                                <li key={k} className="flex justify-between gap-4 capitalize">
                                                    <span>{formatFeatureTitle(k)}</span>
                                                    <span className="text-foreground font-medium">{v}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <p className="text-muted-foreground text-xs font-medium uppercase">Plan caps · informational</p>
                                    {subscription.grandfathered_without_plan || !subscription.entitlement_summary ? (
                                        <p className="text-muted-foreground text-sm">Displayed for packaging only unless noted in docs.</p>
                                    ) : !subscription.entitlement_summary.informational_limits ||
                                      Object.keys(subscription.entitlement_summary.informational_limits).length === 0 ? (
                                        <p className="text-muted-foreground text-sm">None declared on this tier.</p>
                                    ) : (
                                        <ul className="text-muted-foreground space-y-1 text-sm">
                                            {Object.entries(subscription.entitlement_summary.informational_limits).map(([k, v]) => (
                                                <li key={k} className="flex justify-between gap-4 capitalize">
                                                    <span>{formatFeatureTitle(k)}</span>
                                                    <span className="text-foreground font-medium">{v}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>

                                <div className="space-y-1 rounded-lg border bg-muted/20 px-4 py-3 text-sm">
                                    <p className="text-muted-foreground text-[11px] font-medium uppercase">Access explanation</p>
                                    <p className="text-foreground">{subscription.reason}</p>
                                </div>
                            </CardContent>
                        </Card>

                        {canManageSubscription && billing ? (
                            <Card>
                                <CardContent className="space-y-4 pt-6">
                                    <h3 className="text-sm font-semibold">Stripe billing · admin operators</h3>
                                    <div className="text-muted-foreground grid gap-2 text-xs md:grid-cols-2 lg:grid-cols-3">
                                        <p>
                                            Operator flag:{' '}
                                            <span className="text-foreground font-medium">
                                                {billing.state.explicitly_enabled ? 'Enabled' : 'Disabled'}
                                            </span>
                                        </p>
                                        <p>
                                            Checkout ready:{' '}
                                            <span className="text-foreground font-medium">
                                                {billing.state.checkout_ready ? 'Yes' : 'No'}
                                            </span>
                                        </p>
                                        <p>
                                            Secret key configured:{' '}
                                            <span className="text-foreground font-medium">
                                                {billing.state.stripe_secret_configured ? 'Yes' : 'No'}
                                            </span>
                                        </p>
                                        <p>
                                            Webhook secret configured:{' '}
                                            <span className="text-foreground font-medium">
                                                {billing.state.webhook_configured ? 'Yes' : 'No'}
                                            </span>
                                        </p>
                                        <p>
                                            Billing portal configured:{' '}
                                            <span className="text-foreground font-medium">
                                                {billing.state.portal_ready ? 'Yes' : 'No'}
                                            </span>
                                        </p>
                                        <p>
                                            Stripe customer on branch:{' '}
                                            <span className="text-foreground font-medium">
                                                {billing.state.branch_has_customer ? 'Yes' : 'No'}
                                            </span>
                                        </p>
                                    </div>
                                    <div className="space-y-1 rounded-lg border bg-muted/30 px-3 py-2 text-sm">
                                        <p className="text-muted-foreground text-[11px] font-medium uppercase">Provider linkage</p>
                                        <dl className="grid gap-1 text-xs md:grid-cols-2">
                                            <div>
                                                <dt className="text-muted-foreground">Connector</dt>
                                                <dd className="font-medium">{branch.provider_name ?? '—'}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-muted-foreground">Customer id</dt>
                                                <dd className="break-all font-mono text-[11px]">{branch.provider_customer_id ?? '—'}</dd>
                                            </div>
                                            <div className="md:col-span-2">
                                                <dt className="text-muted-foreground">Subscription id</dt>
                                                <dd className="break-all font-mono text-[11px]">{branch.provider_subscription_id ?? '—'}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div className="rounded-lg border border-dashed px-3 py-2">
                                        <p className="text-muted-foreground mb-2 text-xs">
                                            Hosted Customer Portal handles payment methods, invoices, and subscription changes in Stripe —
                                            webhook events continue to reconcile this workspace locally.
                                        </p>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={!can_open_billing_portal || billingPortalBusy}
                                            onClick={openStripePortal}
                                        >
                                            {billingPortalBusy ? 'Opening…' : 'Open Stripe billing portal'}
                                        </Button>
                                        {!can_open_billing_portal ? (
                                            <p className="text-muted-foreground mt-2 text-xs">
                                                Requires billing enabled plus a Stripe secret key and a synced customer id (typically after Checkout).
                                            </p>
                                        ) : null}
                                    </div>
                                    <div className="space-y-2">
                                        <p className="text-muted-foreground text-xs font-medium uppercase">Purchasable via checkout</p>
                                        <p className="text-muted-foreground text-xs">
                                            Only active plans with a configured Stripe price id appear here — server rejects anything else even if URLs are
                                            crafted.
                                        </p>
                                        {!billing_configured ? (
                                            <p className="text-muted-foreground text-sm">
                                                Enable billing (<code className="rounded bg-muted px-1">BILLING_ENABLED=true</code>) and Stripe keys plus
                                                checkout return URLs before checkout begins.
                                            </p>
                                        ) : (billing_plans ?? []).length === 0 ? (
                                            <p className="text-muted-foreground text-sm">
                                                No purchasable plans yet — create an active tier with{' '}
                                                <code className="text-xs">provider_price_id</code> populated.
                                            </p>
                                        ) : (
                                            <div className="space-y-2">
                                                {(billing_plans ?? []).map((p: BranchBillingCatalogPlan) => (
                                                    <div
                                                        key={p.id}
                                                        className="flex flex-wrap items-center justify-between gap-2 rounded-md border px-3 py-2"
                                                    >
                                                        <div>
                                                            <p className="font-medium">{p.name}</p>
                                                            <p className="text-muted-foreground text-xs">{p.slug}</p>
                                                            <p className="text-muted-foreground text-xs">
                                                                Stripe price: <span className="font-mono">{p.provider_price_id}</span>
                                                            </p>
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            disabled={!can_start_checkout || billingCheckoutBusy}
                                                            onClick={() => startStripeCheckout(p.id)}
                                                        >
                                                            {billingCheckoutBusy ? 'Opening…' : 'Start checkout'}
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                        {!can_start_checkout && billing_configured && (billing_plans ?? []).length > 0 ? (
                                            <p className="text-destructive text-xs">Checkout unavailable — verify billing readiness above.</p>
                                        ) : null}
                                        {inertiaErrors.plan_id ? (
                                            <p className="text-destructive text-xs">{inertiaErrors.plan_id}</p>
                                        ) : null}
                                    </div>
                                    <div className="rounded-lg border border-dashed px-3 py-2">
                                        <p className="text-muted-foreground mb-2 text-xs">
                                            Cancel subscriptions at period end (Stripe API). Manual overrides remain available regardless.
                                        </p>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            disabled={
                                                billingCancelBusy || !billing.state.stripe_secret_configured || !branch.provider_subscription_id
                                            }
                                            onClick={scheduleStripeCancel}
                                        >
                                            {billingCancelBusy ? 'Requesting…' : 'Schedule cancel at period end'}
                                        </Button>
                                        {typeof inertiaErrors.billing === 'string' && inertiaErrors.billing !== '' ? (
                                            <p className="text-destructive mt-1 text-xs">{inertiaErrors.billing}</p>
                                        ) : null}
                                        {!branch.provider_subscription_id ? (
                                            <p className="text-muted-foreground mt-1 text-xs">Requires a synced Stripe subscription id.</p>
                                        ) : null}
                                    </div>
                                </CardContent>
                            </Card>
                        ) : null}

                        {canManageSubscription ? (
                            <>
                                <Card>
                                    <CardContent className="space-y-4 pt-6">
                                        {plans_for_assignment && plans_for_assignment.length > 0 ? (
                                            <div className="space-y-2">
                                                <Label htmlFor="plan_id">Assigned plan tier</Label>
                                                <Select
                                                    value={subData.plan_id ?? '__none__'}
                                                    onValueChange={(value) => setSubData('plan_id', value)}
                                                >
                                                    <SelectTrigger id="plan_id">
                                                        <SelectValue placeholder="Select plan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="__none__">None (grandfathered)</SelectItem>
                                                        {plans_for_assignment.map((p) => (
                                                            <SelectItem key={p.id} value={String(p.id)}>
                                                                {p.name}
                                                                {!p.is_active ? ' (inactive · current tier only)' : ''}
                                                                {!p.has_provider_price ? ' · manual assign only — no Stripe price' : ''}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {subErrors.plan_id && (
                                                    <p className="text-destructive text-xs">{subErrors.plan_id}</p>
                                                )}
                                            </div>
                                        ) : (
                                            <p className="text-muted-foreground text-sm">
                                                Create subscription plans first (Settings · Subscription plans) before assigning tiers.
                                            </p>
                                        )}

                                        <div className="space-y-2">
                                            <Label htmlFor="subscription_status">Billing / access status</Label>
                                            <Select
                                                value={subData.subscription_status}
                                                onValueChange={(value) => setSubData('subscription_status', value)}
                                            >
                                                <SelectTrigger id="subscription_status">
                                                    <SelectValue placeholder="Select status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="active">Active</SelectItem>
                                                    <SelectItem value="trialing">Trialing</SelectItem>
                                                    <SelectItem value="past_due">Past Due</SelectItem>
                                                    <SelectItem value="canceled">Canceled (service until period end)</SelectItem>
                                                    <SelectItem value="expired">Expired</SelectItem>
                                                    <SelectItem value="suspended">Suspended</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {subErrors.subscription_status && (
                                                <p className="text-destructive text-xs">{subErrors.subscription_status}</p>
                                            )}
                                        </div>

                                        <div className="grid gap-4 md:grid-cols-3">
                                            <div className="space-y-2">
                                                <Label htmlFor="trial_ends_at">Trial ends</Label>
                                                <Input
                                                    id="trial_ends_at"
                                                    type="datetime-local"
                                                    value={subData.trial_ends_at ? subData.trial_ends_at.slice(0, 16) : ''}
                                                    onChange={(e) => setSubData('trial_ends_at', e.target.value || null)}
                                                />
                                                <p className="text-muted-foreground text-xs">Leave empty for an indefinite trial</p>
                                                {subErrors.trial_ends_at && (
                                                    <p className="text-destructive text-xs">{subErrors.trial_ends_at}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="subscription_ends_at">Billing anchor ends</Label>
                                                <Input
                                                    id="subscription_ends_at"
                                                    type="datetime-local"
                                                    value={
                                                        subData.subscription_ends_at
                                                            ? subData.subscription_ends_at.slice(0, 16)
                                                            : ''
                                                    }
                                                    onChange={(e) =>
                                                        setSubData('subscription_ends_at', e.target.value || null)
                                                    }
                                                />
                                                <p className="text-muted-foreground text-xs">Past Due grace extends from here.</p>
                                                {subErrors.subscription_ends_at && (
                                                    <p className="text-destructive text-xs">{subErrors.subscription_ends_at}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="current_period_ends_at">Current service period ends</Label>
                                                <Input
                                                    id="current_period_ends_at"
                                                    type="datetime-local"
                                                    value={
                                                        subData.current_period_ends_at
                                                            ? subData.current_period_ends_at.slice(0, 16)
                                                            : ''
                                                    }
                                                    onChange={(e) =>
                                                        setSubData('current_period_ends_at', e.target.value || null)
                                                    }
                                                />
                                                <p className="text-muted-foreground text-xs">Primary end date for canceled access.</p>
                                                {subErrors.current_period_ends_at && (
                                                    <p className="text-destructive text-xs">{subErrors.current_period_ends_at}</p>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <div className="flex items-center gap-2 rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
                                    <p className="text-sm text-yellow-800 dark:text-yellow-200">
                                        <strong>Note:</strong> Subscription status gates whether staff can operate the dashboard at all.
                                        Assigned plans control feature pages and quantitative caps layered on top of that status.
                                    </p>
                                </div>

                                <Button onClick={submitSubscription} disabled={subProcessing}>
                                    {subProcessing ? 'Saving...' : 'Save subscription'}
                                </Button>
                            </>
                        ) : (
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="rounded-lg border p-4">
                                        <p className="text-muted-foreground text-sm">
                                            Subscription changes require an administrator. Your team membership may still navigate based on
                                            the plan rules above once a tier applies.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
