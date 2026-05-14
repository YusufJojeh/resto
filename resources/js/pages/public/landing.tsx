import { LocaleSwitcher } from '@/components/locale-switcher';
import { Button } from '@/components/ui/button';
import { useHtmlDir } from '@/i18n/use-html-dir';
import { useTranslation } from '@/i18n/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { BrandingTokens } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion } from 'framer-motion';
import {
    ArrowRight,
    BarChart3,
    Bell,
    Bot,
    ChefHat,
    ChevronRight,
    ClipboardList,
    Clock,
    LayoutDashboard,
    LockKeyhole,
    MessageSquare,
    Package,
    Receipt,
    Settings,
    ShieldCheck,
    Sparkles,
    Store,
    Table2,
    Users,
    Utensils,
    UtensilsCrossed,
    Workflow,
} from 'lucide-react';
import type { ElementType } from 'react';
import { useMemo } from 'react';

interface FeaturedItem {
    id: number;
    name: string;
    description: string | null;
    price: string;
    image_path: string | null;
}

interface LandingCategory {
    id: number;
    name: string;
    item_count: number;
}

interface Props {
    branding: BrandingTokens;
    featured: FeaturedItem[];
    categories: LandingCategory[];
    currency_code: string;
}

interface ModuleCard {
    titleKey: string;
    descriptionKey: string;
    href: string;
    icon: ElementType;
    accessKey: string;
}

const referenceLandingHeroImage = '/assets/reference/photos/landing-hero.jpg';
const referenceAboutImage = '/assets/reference/photos/about-restaurant.jpg';

const referenceCategoryImages = [
    '/assets/reference/photos/category-appetizers.jpg',
    '/assets/reference/photos/category-main-courses.jpg',
    '/assets/reference/photos/category-pasta.jpg',
    '/assets/reference/photos/category-seafood.jpg',
    '/assets/reference/photos/category-desserts.jpg',
    '/assets/reference/photos/category-beverages.jpg',
];

const referenceDishImages = [
    '/assets/reference/photos/dish-truffle-bruschetta.jpg',
    '/assets/reference/photos/dish-caprese-salad.jpg',
    '/assets/reference/photos/dish-ribeye-steak.jpg',
    '/assets/reference/photos/dish-lamb-chops.jpg',
];

const platformModules: ModuleCard[] = [
    {
        titleKey: 'landing.module.dashboard.title',
        descriptionKey: 'landing.module.dashboard.description',
        href: '/dashboard',
        icon: LayoutDashboard,
        accessKey: 'landing.access.all_roles',
    },
    {
        titleKey: 'landing.module.tables.title',
        descriptionKey: 'landing.module.tables.description',
        href: '/tables',
        icon: Table2,
        accessKey: 'landing.access.service_roles',
    },
    {
        titleKey: 'landing.module.orders.title',
        descriptionKey: 'landing.module.orders.description',
        href: '/orders',
        icon: ClipboardList,
        accessKey: 'landing.access.service_roles',
    },
    {
        titleKey: 'landing.module.kitchen.title',
        descriptionKey: 'landing.module.kitchen.description',
        href: '/kitchen',
        icon: ChefHat,
        accessKey: 'landing.access.kitchen_roles',
    },
    {
        titleKey: 'landing.module.invoices.title',
        descriptionKey: 'landing.module.invoices.description',
        href: '/invoices',
        icon: Receipt,
        accessKey: 'landing.access.billing_roles',
    },
    {
        titleKey: 'landing.module.menu.title',
        descriptionKey: 'landing.module.menu.description',
        href: '/menu/items',
        icon: UtensilsCrossed,
        accessKey: 'landing.access.management_roles',
    },
    {
        titleKey: 'landing.module.inventory.title',
        descriptionKey: 'landing.module.inventory.description',
        href: '/inventory',
        icon: Package,
        accessKey: 'landing.access.management_roles',
    },
    {
        titleKey: 'landing.module.reports.title',
        descriptionKey: 'landing.module.reports.description',
        href: '/reports',
        icon: BarChart3,
        accessKey: 'landing.access.management_roles',
    },
    {
        titleKey: 'landing.module.users.title',
        descriptionKey: 'landing.module.users.description',
        href: '/users',
        icon: Users,
        accessKey: 'landing.access.admin_only',
    },
    {
        titleKey: 'landing.module.messages.title',
        descriptionKey: 'landing.module.messages.description',
        href: '/messages',
        icon: MessageSquare,
        accessKey: 'landing.access.all_roles',
    },
    {
        titleKey: 'landing.module.notifications.title',
        descriptionKey: 'landing.module.notifications.description',
        href: '/notifications',
        icon: Bell,
        accessKey: 'landing.access.all_roles',
    },
    {
        titleKey: 'landing.module.assistant.title',
        descriptionKey: 'landing.module.assistant.description',
        href: '/assistant',
        icon: Bot,
        accessKey: 'landing.access.all_roles',
    },
    {
        titleKey: 'landing.module.settings.title',
        descriptionKey: 'landing.module.settings.description',
        href: '/settings/branch',
        icon: Settings,
        accessKey: 'landing.access.management_roles',
    },
];

const staggerContainer = {
    hidden: {},
    visible: {
        transition: {
            staggerChildren: 0.05,
            delayChildren: 0.1,
        },
    },
};

const staggerItem = {
    hidden: { opacity: 0, y: 10 },
    visible: { opacity: 1, y: 0 },
};

const fadeInUp = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.45 } },
};

const hasArabicText = (value: string | null | undefined) => /[\u0600-\u06FF]/.test(value ?? '');

export default function Landing({ branding, featured, categories, currency_code }: Props) {
    useHtmlDir();

    const reduceMotion = useReducedMotion();
    const { t, locale } = useTranslation();
    const loc = locale ?? 'en';

    const heroImage = branding.cover_path || referenceLandingHeroImage;
    const brandName = branding.business_name || 'RestoCafe';
    const productName = brandName.toLowerCase().includes('os') ? brandName : `${brandName} OS`;
    const popularItems = featured.slice(0, 4);
    const publicStory =
        loc === 'ar' && !hasArabicText(branding.story)
            ? t('landing.public_menu.description')
            : branding.story || t('landing.public_menu.description');
    const hours = useMemo(() => {
        if (branding.opening_hours?.length) {
            return branding.opening_hours.map((entry) => Object.values(entry).join(': ')).join('\n');
        }

        return t('landing.hours_default');
    }, [branding.opening_hours, t]);

    const viewportMotion = reduceMotion ? {} : { initial: 'hidden', whileInView: 'visible', viewport: { once: true, amount: 0.2 } };

    const platformProof = [
        {
            icon: Store,
            title: t('landing.proof.workspace.title'),
            description: t('landing.proof.workspace.description'),
        },
        {
            icon: ShieldCheck,
            title: t('landing.proof.roles.title'),
            description: t('landing.proof.roles.description'),
        },
        {
            icon: Workflow,
            title: t('landing.proof.workflow.title'),
            description: t('landing.proof.workflow.description'),
        },
    ];

    const workflowSteps = [
        {
            eyebrow: t('landing.workflow.step1.eyebrow'),
            title: t('landing.workflow.step1.title'),
            description: t('landing.workflow.step1.description'),
        },
        {
            eyebrow: t('landing.workflow.step2.eyebrow'),
            title: t('landing.workflow.step2.title'),
            description: t('landing.workflow.step2.description'),
        },
        {
            eyebrow: t('landing.workflow.step3.eyebrow'),
            title: t('landing.workflow.step3.title'),
            description: t('landing.workflow.step3.description'),
        },
        {
            eyebrow: t('landing.workflow.step4.eyebrow'),
            title: t('landing.workflow.step4.title'),
            description: t('landing.workflow.step4.description'),
        },
    ];

    const previewSignals = [
        { label: t('landing.preview.tables'), value: t('landing.preview.tables_value') },
        { label: t('landing.preview.orders'), value: t('landing.preview.orders_value') },
        { label: t('landing.preview.reports'), value: t('landing.preview.reports_value') },
    ];

    return (
        <div className="reference-public bg-background text-foreground min-h-screen">
            <Head title={`${productName} | ${t('landing.meta_title')}`} />

            <motion.nav
                initial={reduceMotion ? false : { y: -20, opacity: 0 }}
                animate={reduceMotion ? undefined : { y: 0, opacity: 1 }}
                transition={{ duration: 0.5 }}
                className="glass-strong fixed inset-x-0 top-0 z-50"
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between lg:h-20">
                        <Link href="/" className="flex min-w-0 items-center gap-2">
                            <div className="bg-primary text-primary-foreground flex h-10 w-10 shrink-0 items-center justify-center rounded-xl">
                                <Utensils className="h-5 w-5" />
                            </div>
                            <div className="min-w-0">
                                <span className="block truncate text-xl font-semibold tracking-tight">{productName}</span>
                                <span className="text-muted-foreground hidden text-[11px] font-medium sm:block">{t('landing.nav_subtitle')}</span>
                            </div>
                        </Link>

                        <div className="hidden items-center gap-8 lg:flex">
                            <a href="#platform" className="animated-underline text-muted-foreground hover:text-foreground text-sm transition-colors">
                                {t('landing.nav.platform')}
                            </a>
                            <a href="#workflow" className="animated-underline text-muted-foreground hover:text-foreground text-sm transition-colors">
                                {t('landing.nav.workflow')}
                            </a>
                            <a href="#modules" className="animated-underline text-muted-foreground hover:text-foreground text-sm transition-colors">
                                {t('landing.nav.modules')}
                            </a>
                            <Link href="/menu" className="animated-underline text-muted-foreground hover:text-foreground text-sm transition-colors">
                                {t('landing.nav.public_menu')}
                            </Link>
                        </div>

                        <div className="flex items-center gap-2 sm:gap-3">
                            <LocaleSwitcher variant="compact" className="bg-secondary/50" />
                            <Button asChild variant="ghost" size="sm" className="hidden sm:inline-flex">
                                <Link href="/login">{t('landing.nav.login_workspace')}</Link>
                            </Button>
                            <Button asChild size="sm" className="gap-2">
                                <Link href="/dashboard">
                                    <span className="hidden sm:inline">{t('landing.cta.dashboard')}</span>
                                    <span className="sm:hidden">{t('landing.cta.login')}</span>
                                    <ArrowRight className="h-4 w-4 rtl:rotate-180" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </motion.nav>

            <section className="relative flex min-h-screen items-center overflow-hidden pt-20">
                <div className="absolute inset-0">
                    <img src={heroImage} alt="" className="h-full w-full object-cover" />
                    <div className="from-background/90 via-background/72 to-background absolute inset-0 bg-gradient-to-b" />
                    <div className="from-background/95 via-background/45 to-background/80 absolute inset-0 bg-gradient-to-r" />
                </div>

                <div className="relative z-10 mx-auto grid w-full max-w-7xl items-center gap-12 px-4 py-24 sm:px-6 lg:grid-cols-[minmax(0,0.95fr)_minmax(360px,0.75fr)] lg:px-8 lg:py-32">
                    <motion.div variants={staggerContainer} initial={reduceMotion ? false : 'hidden'} animate="visible" className="max-w-3xl">
                        <motion.div variants={staggerItem} className="mb-6">
                            <span className="border-primary/20 bg-primary/10 text-primary inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium">
                                <Sparkles className="h-4 w-4" />
                                {t('landing.hero_badge')}
                            </span>
                        </motion.div>

                        <motion.p variants={staggerItem} className="text-muted-foreground mb-4 text-sm font-medium tracking-[0.28em] uppercase">
                            {t('landing.hero_eyebrow')}
                        </motion.p>

                        <motion.h1 variants={staggerItem} className="max-w-4xl text-4xl font-bold tracking-tight sm:text-5xl lg:text-7xl">
                            <span className="block">{t('landing.hero_title_line1')}</span>
                            <span className="text-gradient block">{t('landing.hero_title_line2')}</span>
                        </motion.h1>

                        <motion.p variants={staggerItem} className="text-muted-foreground mt-6 max-w-2xl text-lg sm:text-xl">
                            {t('landing.hero_description')}
                        </motion.p>

                        <motion.div variants={staggerItem} className="mt-10 flex flex-wrap gap-4">
                            <Button asChild size="lg" className="gap-2 text-base">
                                <Link href="/login">
                                    {t('landing.cta.start')}
                                    <ChevronRight className="h-5 w-5 rtl:rotate-180" />
                                </Link>
                            </Button>
                            <Button asChild variant="outline" size="lg" className="gap-2 text-base">
                                <Link href="/dashboard">
                                    <LayoutDashboard className="h-5 w-5" />
                                    {t('landing.cta.dashboard')}
                                </Link>
                            </Button>
                            <Button asChild variant="ghost" size="lg" className="gap-2 text-base">
                                <Link href="/menu">
                                    {t('landing.cta.public_menu')}
                                    <ArrowRight className="h-5 w-5 rtl:rotate-180" />
                                </Link>
                            </Button>
                        </motion.div>

                        <motion.div variants={staggerItem} className="mt-14 grid max-w-xl grid-cols-3 gap-4 sm:gap-8">
                            {[
                                { value: '13', label: t('landing.stat.modules') },
                                { value: '5', label: t('landing.stat.roles') },
                                { value: t('landing.stat.workspace_value'), label: t('landing.stat.workspace') },
                            ].map((stat) => (
                                <div key={stat.label} className="border-border/50 bg-card/35 rounded-2xl border px-3 py-4 text-center backdrop-blur">
                                    <span className="text-foreground block text-2xl font-bold sm:text-3xl">{stat.value}</span>
                                    <p className="text-muted-foreground mt-1 text-xs sm:text-sm">{stat.label}</p>
                                </div>
                            ))}
                        </motion.div>
                    </motion.div>

                    <motion.div
                        initial={reduceMotion ? false : { opacity: 0, y: 24, scale: 0.98 }}
                        animate={reduceMotion ? undefined : { opacity: 1, y: 0, scale: 1 }}
                        transition={{ duration: 0.55, delay: 0.2 }}
                        className="surface-panel relative overflow-hidden p-5 sm:p-6"
                    >
                        <div className="via-primary/60 absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent" />
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <p className="text-primary text-xs font-semibold tracking-[0.22em] uppercase">{t('landing.preview.eyebrow')}</p>
                                <h2 className="mt-2 text-2xl font-semibold">{t('landing.preview.title')}</h2>
                            </div>
                            <div className="bg-primary/10 text-primary flex h-12 w-12 items-center justify-center rounded-2xl">
                                <LayoutDashboard className="h-6 w-6" />
                            </div>
                        </div>

                        <div className="mt-6 grid gap-3 sm:grid-cols-3">
                            {previewSignals.map((signal) => (
                                <div key={signal.label} className="bg-background/35 rounded-2xl border border-white/10 p-4">
                                    <p className="text-muted-foreground text-xs">{signal.label}</p>
                                    <p className="mt-2 text-lg font-semibold">{signal.value}</p>
                                </div>
                            ))}
                        </div>

                        <div className="mt-6 space-y-3">
                            {[
                                { icon: ClipboardList, label: t('landing.preview.flow_order'), value: t('landing.preview.flow_order_value') },
                                { icon: ChefHat, label: t('landing.preview.flow_kitchen'), value: t('landing.preview.flow_kitchen_value') },
                                { icon: Receipt, label: t('landing.preview.flow_invoice'), value: t('landing.preview.flow_invoice_value') },
                                { icon: Package, label: t('landing.preview.flow_stock'), value: t('landing.preview.flow_stock_value') },
                            ].map((row) => (
                                <div key={row.label} className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-3">
                                    <div className="bg-primary/10 text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-xl">
                                        <row.icon className="h-5 w-5" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">{row.label}</p>
                                        <p className="text-muted-foreground truncate text-xs">{row.value}</p>
                                    </div>
                                    <ArrowRight className="text-muted-foreground h-4 w-4 rtl:rotate-180" />
                                </div>
                            ))}
                        </div>
                    </motion.div>
                </div>

                {!reduceMotion ? (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        transition={{ delay: 1.5 }}
                        className="absolute bottom-8 left-1/2 hidden -translate-x-1/2 sm:block"
                    >
                        <motion.div
                            animate={{ y: [0, 8, 0] }}
                            transition={{ duration: 1.5, repeat: Infinity }}
                            className="text-muted-foreground flex flex-col items-center gap-2"
                        >
                            <span className="text-xs tracking-wider uppercase">{t('landing.scroll')}</span>
                            <div className="border-muted-foreground/30 flex h-8 w-5 justify-center rounded-full border-2 pt-1">
                                <motion.div
                                    animate={{ y: [0, 8, 0] }}
                                    transition={{ duration: 1.5, repeat: Infinity }}
                                    className="bg-primary h-2 w-1 rounded-full"
                                />
                            </div>
                        </motion.div>
                    </motion.div>
                ) : null}
            </section>

            <section id="platform" className="py-24 lg:py-32">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <motion.div variants={fadeInUp} {...viewportMotion} className="mb-14 max-w-3xl">
                        <span className="text-primary text-sm font-medium tracking-wider uppercase">{t('landing.platform_eyebrow')}</span>
                        <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">{t('landing.platform_title')}</h2>
                        <p className="text-muted-foreground mt-4 text-lg">{t('landing.platform_description')}</p>
                    </motion.div>

                    <motion.div variants={staggerContainer} {...viewportMotion} className="grid gap-5 md:grid-cols-3">
                        {platformProof.map((proof) => (
                            <motion.div key={proof.title} variants={staggerItem} className="surface-panel p-6">
                                <div className="bg-primary/10 text-primary flex h-12 w-12 items-center justify-center rounded-2xl">
                                    <proof.icon className="h-6 w-6" />
                                </div>
                                <h3 className="mt-5 text-xl font-semibold">{proof.title}</h3>
                                <p className="text-muted-foreground mt-3 text-sm leading-6">{proof.description}</p>
                            </motion.div>
                        ))}
                    </motion.div>
                </div>
            </section>

            <section id="workflow" className="bg-card/45 py-24 lg:py-32">
                <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
                    <motion.div variants={fadeInUp} {...viewportMotion} className="relative">
                        <div className="aspect-[4/3] overflow-hidden rounded-3xl">
                            <img src={referenceAboutImage} alt="" className="h-full w-full object-cover" />
                        </div>
                        <div className="surface-panel absolute -end-3 -bottom-6 max-w-xs p-5 sm:-end-6">
                            <p className="text-primary text-xs font-semibold tracking-[0.2em] uppercase">{t('landing.workflow.callout_eyebrow')}</p>
                            <p className="text-muted-foreground mt-2 text-sm leading-6">{t('landing.workflow.callout')}</p>
                        </div>
                    </motion.div>

                    <motion.div variants={staggerContainer} {...viewportMotion}>
                        <motion.div variants={staggerItem}>
                            <span className="text-primary text-sm font-medium tracking-wider uppercase">{t('landing.workflow_eyebrow')}</span>
                            <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{t('landing.workflow_title')}</h2>
                            <p className="text-muted-foreground mt-4">{t('landing.workflow_description')}</p>
                        </motion.div>

                        <div className="mt-10 grid gap-4">
                            {workflowSteps.map((step, index) => (
                                <motion.div
                                    key={step.title}
                                    variants={staggerItem}
                                    className="group border-border/80 bg-background/35 flex gap-4 rounded-2xl border p-4"
                                >
                                    <div className="bg-primary text-primary-foreground flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-semibold">
                                        {index + 1}
                                    </div>
                                    <div>
                                        <p className="text-primary text-xs font-medium tracking-[0.18em] uppercase">{step.eyebrow}</p>
                                        <h3 className="mt-1 font-semibold">{step.title}</h3>
                                        <p className="text-muted-foreground mt-1 text-sm leading-6">{step.description}</p>
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    </motion.div>
                </div>
            </section>

            <section id="modules" className="py-24 lg:py-32">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <motion.div variants={fadeInUp} {...viewportMotion} className="mb-14 text-center">
                        <span className="text-primary text-sm font-medium tracking-wider uppercase">{t('landing.modules_eyebrow')}</span>
                        <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">{t('landing.modules_title')}</h2>
                        <p className="text-muted-foreground mx-auto mt-4 max-w-3xl">{t('landing.modules_description')}</p>
                    </motion.div>

                    <motion.div variants={staggerContainer} {...viewportMotion} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {platformModules.map((module) => (
                            <motion.div key={module.href} variants={staggerItem} whileHover={reduceMotion ? undefined : { y: -5 }}>
                                <Link
                                    href={module.href}
                                    className="group border-border/80 bg-card/70 hover:border-primary/50 hover:bg-card flex h-full flex-col rounded-2xl border p-5 transition"
                                >
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="bg-primary/10 text-primary flex h-11 w-11 items-center justify-center rounded-xl">
                                            <module.icon className="h-5 w-5" />
                                        </div>
                                        <ArrowRight className="text-muted-foreground h-4 w-4 opacity-0 transition group-hover:opacity-100 rtl:rotate-180" />
                                    </div>
                                    <h3 className="mt-5 font-semibold">{t(module.titleKey)}</h3>
                                    <p className="text-muted-foreground mt-2 flex-1 text-sm leading-6">{t(module.descriptionKey)}</p>
                                    <span className="text-primary mt-4 text-xs font-medium">{t(module.accessKey)}</span>
                                </Link>
                            </motion.div>
                        ))}
                    </motion.div>
                </div>
            </section>

            <section id="menu" className="bg-card/45 py-24 lg:py-32">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <motion.div
                        variants={fadeInUp}
                        {...viewportMotion}
                        className="mb-14 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
                    >
                        <div className="max-w-3xl">
                            <span className="text-primary text-sm font-medium tracking-wider uppercase">{t('landing.public_menu.eyebrow')}</span>
                            <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{t('landing.public_menu.title')}</h2>
                            <p className="text-muted-foreground mt-4">{publicStory}</p>
                        </div>
                        <Button asChild variant="outline" size="lg" className="gap-2 self-start lg:self-auto">
                            <Link href="/menu">
                                {t('landing.cta.public_menu')}
                                <ArrowRight className="h-5 w-5 rtl:rotate-180" />
                            </Link>
                        </Button>
                    </motion.div>

                    {popularItems.length > 0 ? (
                        <motion.div variants={staggerContainer} {...viewportMotion} className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {popularItems.map((item, index) => (
                                <motion.article
                                    key={item.id}
                                    variants={staggerItem}
                                    whileHover={reduceMotion ? undefined : { y: -8 }}
                                    className="group border-border bg-card relative overflow-hidden rounded-2xl border"
                                >
                                    <div className="aspect-[4/3] overflow-hidden">
                                        <img
                                            src={item.image_path || referenceDishImages[index % referenceDishImages.length]}
                                            alt={item.name}
                                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        />
                                        <div className="from-card absolute inset-0 bg-gradient-to-t via-transparent to-transparent" />
                                    </div>

                                    <div className="p-5">
                                        <h3 className="text-foreground font-semibold">{item.name}</h3>
                                        {item.description ? (
                                            <p className="text-muted-foreground mt-1 line-clamp-2 text-sm">{item.description}</p>
                                        ) : null}
                                        <div className="mt-4 flex items-center justify-between gap-3">
                                            <span className="text-primary text-lg font-semibold">
                                                {formatCurrency(Number(item.price), currency_code, loc)}
                                            </span>
                                            <Button asChild size="sm" variant="secondary" className="gap-1.5">
                                                <Link href="/menu">
                                                    {t('landing.public_menu.view_item')}
                                                    <ArrowRight className="h-4 w-4 rtl:rotate-180" />
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                </motion.article>
                            ))}
                        </motion.div>
                    ) : (
                        <div className="border-border/80 text-muted-foreground rounded-2xl border border-dashed px-6 py-10 text-center">
                            {t('landing.public_menu.empty')}
                        </div>
                    )}

                    <motion.div variants={staggerContainer} {...viewportMotion} className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {categories.slice(0, 6).map((category, index) => (
                            <motion.div
                                key={category.id}
                                variants={staggerItem}
                                whileHover={reduceMotion ? undefined : { scale: 1.02 }}
                                className="group relative h-44 overflow-hidden rounded-xl"
                            >
                                <img
                                    src={referenceCategoryImages[index % referenceCategoryImages.length]}
                                    alt=""
                                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                />
                                <div className="from-background via-background/50 absolute inset-0 bg-gradient-to-t to-transparent" />
                                <div className="absolute inset-0 flex flex-col justify-end p-6">
                                    <h3 className="text-foreground text-xl font-semibold">{category.name}</h3>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {t('landing.category_items', { count: category.item_count })}
                                    </p>
                                </div>
                            </motion.div>
                        ))}
                    </motion.div>
                </div>
            </section>

            <section id="contact" className="py-24 lg:py-32">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="surface-panel overflow-hidden p-8 sm:p-10 lg:p-12">
                        <div className="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                            <motion.div variants={fadeInUp} {...viewportMotion}>
                                <span className="text-primary text-sm font-medium tracking-wider uppercase">{t('landing.final_eyebrow')}</span>
                                <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{t('landing.final_title')}</h2>
                                <p className="text-muted-foreground mt-4 max-w-2xl">{t('landing.final_description')}</p>
                                <div className="mt-8 flex flex-wrap gap-4">
                                    <Button asChild size="lg" className="gap-2">
                                        <Link href="/login">
                                            <LockKeyhole className="h-5 w-5" />
                                            {t('landing.cta.login_workspace')}
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" size="lg" className="gap-2">
                                        <Link href="/dashboard">
                                            {t('landing.cta.dashboard')}
                                            <ArrowRight className="h-5 w-5 rtl:rotate-180" />
                                        </Link>
                                    </Button>
                                </div>
                            </motion.div>

                            <motion.div variants={staggerContainer} {...viewportMotion} className="grid gap-4">
                                {[
                                    { icon: Store, title: t('landing.final.branch'), value: branding.business_name || brandName },
                                    { icon: Clock, title: t('landing.hours_title'), value: hours },
                                    { icon: ShieldCheck, title: t('landing.final.security'), value: t('landing.final.security_value') },
                                ].map((item) => (
                                    <motion.div
                                        key={item.title}
                                        variants={staggerItem}
                                        className="flex gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                    >
                                        <div className="bg-primary/10 text-primary flex h-11 w-11 shrink-0 items-center justify-center rounded-xl">
                                            <item.icon className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium">{item.title}</p>
                                            <p className="text-muted-foreground mt-1 text-sm whitespace-pre-line">{item.value}</p>
                                        </div>
                                    </motion.div>
                                ))}
                            </motion.div>
                        </div>
                    </div>
                </div>
            </section>

            <footer className="border-border border-t py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col items-center justify-between gap-6 md:flex-row">
                        <Link href="/" className="flex items-center gap-2">
                            <div className="bg-primary text-primary-foreground flex h-10 w-10 items-center justify-center rounded-xl">
                                <Utensils className="h-5 w-5" />
                            </div>
                            <span className="text-xl font-semibold">{productName}</span>
                        </Link>

                        <div className="flex flex-wrap items-center justify-center gap-5 text-sm">
                            <Link href="/login" className="text-muted-foreground hover:text-foreground transition-colors">
                                {t('landing.cta.login_workspace')}
                            </Link>
                            <Link href="/dashboard" className="text-muted-foreground hover:text-foreground transition-colors">
                                {t('landing.cta.dashboard')}
                            </Link>
                            <Link href="/menu" className="text-muted-foreground hover:text-foreground transition-colors">
                                {t('landing.cta.public_menu')}
                            </Link>
                        </div>

                        <p className="text-muted-foreground text-sm">
                            &copy; {new Date().getFullYear()} {productName}. {t('landing.footer_rights')}
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
