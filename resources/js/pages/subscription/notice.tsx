import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, LogOut, Settings } from 'lucide-react';

interface SubscriptionNoticeProps {
    branch: {
        name: string | null;
    };
}

function formatDate(value: string | null) {
    if (!value) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}

export default function SubscriptionNotice({ branch }: SubscriptionNoticeProps) {
    const { subscription, branding } = usePage<SharedData>().props;
    const status = subscription.status ?? 'unassigned';
    const plan = subscription.plan ?? 'No plan';
    const title = branch.name ?? branding.business_name ?? 'RestoCafe';

    return (
        <main className="bg-background text-foreground flex min-h-screen items-center justify-center px-4 py-10">
            <Head title="Subscription required" />
            <Card className="w-full max-w-2xl">
                <CardHeader className="space-y-4">
                    <div className="bg-destructive/10 text-destructive flex h-12 w-12 items-center justify-center rounded-xl">
                        <AlertTriangle className="h-6 w-6" aria-hidden />
                    </div>
                    <div>
                        <CardTitle className="text-2xl">Subscription access required</CardTitle>
                        <p className="text-muted-foreground mt-2 text-sm">
                            {title} is currently in {status} status. Operational modules are paused until access is restored.
                        </p>
                    </div>
                </CardHeader>
                <CardContent className="space-y-6">
                    <dl className="grid gap-3 rounded-lg border p-4 text-sm md:grid-cols-2">
                        <div>
                            <dt className="text-muted-foreground">Access</dt>
                            <dd className="font-semibold capitalize">
                                {subscription.has_access ? 'Currently allowed' : 'Currently blocked'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Reason code</dt>
                            <dd className="font-mono text-xs uppercase">{subscription.reason_code}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Plan</dt>
                            <dd className="font-medium capitalize">{plan}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium capitalize">{status}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Trial ends</dt>
                            <dd className="font-medium">{formatDate(subscription.trial_ends_at)}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Billing anchor ends</dt>
                            <dd className="font-medium">{formatDate(subscription.subscription_ends_at)}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Service period ends</dt>
                            <dd className="font-medium">{formatDate(subscription.current_period_ends_at ?? null)}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Past-due grace (days)</dt>
                            <dd className="font-medium">{subscription.grace_days ?? 0}</dd>
                        </div>
                    </dl>

                    <div className="bg-muted/50 rounded-lg border px-4 py-3 text-sm">{subscription.reason}</div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Button asChild>
                            <Link href={route('profile.edit')}>
                                <Settings className="h-4 w-4" aria-hidden />
                                Profile settings
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link method="post" href={route('logout')} as="button">
                                <LogOut className="h-4 w-4" aria-hidden />
                                Log out
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </main>
    );
}
