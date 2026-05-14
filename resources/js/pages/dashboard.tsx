import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/use-translation';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/format-currency';
import { useMotionVariants } from '@/motion/use-motion';
import { slideUp } from '@/motion/variants';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { AlertTriangle, ArrowRight, Banknote, CheckCircle2, ClipboardList, PackageSearch, Table2 } from 'lucide-react';

interface DashboardProps {
    stats: {
        tables: number;
        activeOrders: number;
        readyOrders: number;
        todayRevenue: number;
        lowStockCount: number;
        currency_code: string;
    };
}

export default function Dashboard({ stats }: DashboardProps) {
    const { auth, locale } = usePage<SharedData>().props;
    const { t } = useTranslation();
    const loc = (locale as 'en' | 'ar') ?? 'en';
    const statMotion = useMotionVariants(slideUp);
    const role = String(auth.user?.roles?.[0] ?? 'staff');

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('breadcrumbs.dashboard'), href: '/dashboard' }];

    const revenue = formatCurrency(Number(stats.todayRevenue), stats.currency_code, loc);

    const quickActions = [
        {
            icon: ClipboardList,
            title: t('dashboard.quick_action.active_orders'),
            description: t('dashboard.quick_action.active_orders_desc'),
            href: '/orders',
            accent: 'text-blue-600 dark:text-blue-400',
            bg: 'bg-blue-50 dark:bg-blue-950/30',
            roles: ['admin', 'manager', 'waiter', 'cashier'],
        },
        {
            icon: CheckCircle2,
            title: t('dashboard.quick_action.ready_to_serve'),
            description: t('dashboard.quick_action.ready_to_serve_desc'),
            href: '/orders?status=ready',
            accent: 'text-emerald-600 dark:text-emerald-400',
            bg: 'bg-emerald-50 dark:bg-emerald-950/30',
            roles: ['admin', 'manager', 'waiter', 'cashier'],
        },
        {
            icon: PackageSearch,
            title: t('dashboard.quick_action.low_stock'),
            description: t('dashboard.quick_action.low_stock_desc'),
            href: '/inventory',
            accent: 'text-amber-600 dark:text-amber-400',
            bg: 'bg-amber-50 dark:bg-amber-950/30',
            roles: ['admin', 'manager'],
        },
    ];
    const visibleQuickActions = quickActions.filter((action) => action.roles.includes(role));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('breadcrumbs.dashboard')} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <PageHeader title={t('dashboard.title')} description={t('dashboard.description', { name: auth.user?.name ?? '', role })} />
                <motion.div
                    className="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
                    initial="hidden"
                    animate="visible"
                    variants={{
                        hidden: {},
                        visible: {
                            transition: { staggerChildren: 0.04 },
                        },
                    }}
                >
                    <motion.div variants={statMotion}>
                        <StatCard label={t('dashboard.stat.tables')} value={stats.tables} icon={Table2} accent="bg-accent" />
                    </motion.div>
                    <motion.div variants={statMotion}>
                        <StatCard label={t('dashboard.stat.active_orders')} value={stats.activeOrders} icon={ClipboardList} accent="bg-blue-500" />
                    </motion.div>
                    <motion.div variants={statMotion}>
                        <StatCard label={t('dashboard.stat.ready_orders')} value={stats.readyOrders} icon={CheckCircle2} accent="bg-emerald-500" />
                    </motion.div>
                    <motion.div variants={statMotion}>
                        <StatCard label={t('dashboard.stat.today_revenue')} value={revenue} icon={Banknote} accent="bg-violet-500" />
                    </motion.div>
                    <motion.div variants={statMotion}>
                        <StatCard
                            label={t('dashboard.stat.low_stock')}
                            value={stats.lowStockCount}
                            icon={AlertTriangle}
                            accent={stats.lowStockCount > 0 ? 'bg-amber-500' : 'bg-muted-foreground/30'}
                        />
                    </motion.div>
                </motion.div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('dashboard.quick_actions')}</CardTitle>
                    </CardHeader>
                    <CardContent className={visibleQuickActions.length > 0 ? 'grid gap-3 md:grid-cols-3' : ''}>
                        {visibleQuickActions.length > 0 ? (
                            visibleQuickActions.map((action) => (
                                <Link
                                    key={action.href}
                                    href={action.href}
                                    className="group hover:bg-muted/50 flex items-start gap-4 rounded-lg border p-4 transition-colors"
                                >
                                    <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${action.bg}`}>
                                        <action.icon className={`h-5 w-5 ${action.accent}`} aria-hidden />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm leading-none font-medium">{action.title}</p>
                                        <p className="text-muted-foreground mt-1 text-xs">{action.description}</p>
                                    </div>
                                    <ArrowRight
                                        className="text-muted-foreground h-4 w-4 shrink-0 opacity-0 transition-opacity group-hover:opacity-100 rtl:rotate-180"
                                        aria-hidden
                                    />
                                </Link>
                            ))
                        ) : (
                            <div className="text-muted-foreground rounded-lg border border-dashed px-4 py-5 text-sm">
                                {t('dashboard.quick_actions_empty')}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
